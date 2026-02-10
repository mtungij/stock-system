<?php

use Livewire\Component;
use App\Models\Branch;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\SaleItem;
use App\Models\Stock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

new class extends Component
{
    public $startDate;
    public $endDate;
    
    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->toDateString();
        $this->endDate = Carbon::now()->toDateString();
    }
    
    #[Computed]
    public function branchPerformanceData()
    {
        $startDate = $this->startDate;
        $endDate = $this->endDate;
        
        $branches = Branch::orderBy('name')->get();
        
        $performanceData = $branches->map(function($branch) use ($startDate, $endDate) {
            // Total Sales
            $totalSales = Sale::where('branch_id', $branch->id)
                ->whereBetween('sale_date', [$startDate, $endDate])
                ->sum('total_amount');
            
            // Total Purchases
            $totalPurchases = Purchase::where('branch_id', $branch->id)
                ->whereBetween('purchase_date', [$startDate, $endDate])
                ->sum('total_amount');
            
            // Profit (Sales - Purchases)
            $profit = $totalSales - $totalPurchases;
            
            // Products Sold (total quantity)
            $productsSold = SaleItem::whereHas('sale', function($q) use ($branch, $startDate, $endDate) {
                    $q->where('branch_id', $branch->id)
                      ->whereBetween('sale_date', [$startDate, $endDate]);
                })->sum('quantity');
            
            // Current Stock Value (quantity * buy_price)
            $currentStockValue = Stock::where('branch_id', $branch->id)
                ->get()
                ->sum(function($stock) {
                    return $stock->quantity * $stock->buy_price;
                });
            
            return [
                'branch_name' => $branch->name,
                'total_sales' => $totalSales,
                'total_purchases' => $totalPurchases,
                'profit' => $profit,
                'products_sold' => $productsSold,
                'current_stock_value' => $currentStockValue,
            ];
        });
        
        // Calculate totals
        $totals = [
            'total_sales' => $performanceData->sum('total_sales'),
            'total_purchases' => $performanceData->sum('total_purchases'),
            'profit' => $performanceData->sum('profit'),
            'products_sold' => $performanceData->sum('products_sold'),
            'current_stock_value' => $performanceData->sum('current_stock_value'),
        ];
        
        return [
            'branches' => $performanceData,
            'totals' => $totals,
        ];
    }
    
    public function downloadPdf()
    {
        $data = $this->branchPerformanceData;
        $startDate = Carbon::parse($this->startDate)->format('M d, Y');
        $endDate = Carbon::parse($this->endDate)->format('M d, Y');
        
        $pdf = Pdf::loadView('pdf.branch-performance', [
            'data' => $data,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ])->setPaper('a4', 'landscape');
        
        return response()->streamDownload(function() use ($pdf) {
            echo $pdf->output();
        }, 'branch-performance-' . date('Y-m-d') . '.pdf');
    }
};
?>

<div class="p-3 sm:p-4 md:p-6">
    
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-4 md:mb-6">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">Branch Performance Report</h1>
        <div class="flex items-center gap-2">
            <input type="date" wire:model.live="startDate" class="px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 bg-white dark:bg-zinc-700 text-gray-900 dark:text-gray-100 text-sm">
            <span class="text-gray-500 dark:text-gray-400">to</span>
            <input type="date" wire:model.live="endDate" class="px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 bg-white dark:bg-zinc-700 text-gray-900 dark:text-gray-100 text-sm">
            <button wire:click="downloadPdf" class="flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors text-sm font-medium">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
                <span>Download PDF</span>
            </button>
        </div>
    </div>

    @php $data = $this->branchPerformanceData; @endphp
    
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <!-- Total Sales -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-4 border border-gray-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Total Sales</p>
                    <p class="text-lg font-bold text-green-600 dark:text-green-400">Tsh {{ number_format($data['totals']['total_sales'], 0) }}</p>
                </div>
                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd" />
                </svg>
            </div>
        </div>

        <!-- Total Purchases -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-4 border border-gray-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Total Purchases</p>
                    <p class="text-lg font-bold text-orange-600 dark:text-orange-400">Tsh {{ number_format($data['totals']['total_purchases'], 0) }}</p>
                </div>
                <svg class="w-8 h-8 text-orange-600 dark:text-orange-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3z"></path>
                    <path d="M16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"></path>
                </svg>
            </div>
        </div>

        <!-- Profit -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-4 border border-gray-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Total Profit</p>
                    <p class="text-lg font-bold text-blue-600 dark:text-blue-400">Tsh {{ number_format($data['totals']['profit'], 0) }}</p>
                </div>
                <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd" />
                </svg>
            </div>
        </div>

        <!-- Products Sold -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-4 border border-gray-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Products Sold</p>
                    <p class="text-lg font-bold text-purple-600 dark:text-purple-400">{{ number_format($data['totals']['products_sold'], 0) }}</p>
                </div>
                <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path>
                </svg>
            </div>
        </div>

        <!-- Current Stock Value -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-4 border border-gray-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Stock Value</p>
                    <p class="text-lg font-bold text-indigo-600 dark:text-indigo-400">Tsh {{ number_format($data['totals']['current_stock_value'], 0) }}</p>
                </div>
                <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Performance Table -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md border border-gray-200 dark:border-zinc-700">
        <div class="p-4 md:p-6 border-b border-gray-200 dark:border-zinc-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Branch Performance Details</h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-zinc-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Branch</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total Sales</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total Purchases</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Profit</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Products Sold</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Current Stock Value</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse($data['branches'] as $branch)
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700">
                            <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">{{ $branch['branch_name'] }}</td>
                            <td class="px-4 py-3 text-right text-green-600 dark:text-green-400 font-medium">Tsh {{ number_format($branch['total_sales'], 0) }}</td>
                            <td class="px-4 py-3 text-right text-orange-600 dark:text-orange-400 font-medium">Tsh {{ number_format($branch['total_purchases'], 0) }}</td>
                            <td class="px-4 py-3 text-right font-bold {{ $branch['profit'] >= 0 ? 'text-blue-600 dark:text-blue-400' : 'text-red-600 dark:text-red-400' }}">
                                Tsh {{ number_format($branch['profit'], 0) }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ number_format($branch['products_sold'], 0) }}</td>
                            <td class="px-4 py-3 text-right text-indigo-600 dark:text-indigo-400 font-medium">Tsh {{ number_format($branch['current_stock_value'], 0) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No branch data found</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($data['branches']->count() > 0)
                <tfoot class="bg-gray-50 dark:bg-zinc-700 border-t-2 border-gray-300 dark:border-zinc-600">
                    <tr class="font-bold">
                        <td class="px-4 py-3 text-gray-900 dark:text-white">TOTAL</td>
                        <td class="px-4 py-3 text-right text-green-600 dark:text-green-400">Tsh {{ number_format($data['totals']['total_sales'], 0) }}</td>
                        <td class="px-4 py-3 text-right text-orange-600 dark:text-orange-400">Tsh {{ number_format($data['totals']['total_purchases'], 0) }}</td>
                        <td class="px-4 py-3 text-right text-blue-600 dark:text-blue-400">Tsh {{ number_format($data['totals']['profit'], 0) }}</td>
                        <td class="px-4 py-3 text-right text-gray-900 dark:text-white">{{ number_format($data['totals']['products_sold'], 0) }}</td>
                        <td class="px-4 py-3 text-right text-indigo-600 dark:text-indigo-400">Tsh {{ number_format($data['totals']['current_stock_value'], 0) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>