<?php

use Livewire\Component;
use App\Models\SaleItem;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

new class extends Component
{
    public $startDate;
    public $endDate;
    public $branch_id = '';

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->toDateString();
        $this->endDate = Carbon::now()->toDateString();

        $user = Auth::user();
        if ($user && $user->role && $user->role->role_name === 'Sales Person' && $user->branch_id) {
            $this->branch_id = $user->branch_id;
        }
    }

    #[Computed]
    public function branches()
    {
        $user = Auth::user();

        if ($user && $user->role && $user->role->role_name === 'Admin') {
            return Branch::orderBy('name')->get();
        }

        if ($user && $user->branch_id) {
            return Branch::where('id', $user->branch_id)->get();
        }

        return collect();
    }

    #[Computed]
    public function fastMovingData()
    {
        $user = Auth::user();
        $startDate = $this->startDate;
        $endDate = $this->endDate;

        $query = SaleItem::select(
                'product_id',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(quantity * price) as total_sales')
            )
            ->with(['product.category'])
            ->whereHas('sale', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('sale_date', [$startDate, $endDate]);
            });

        if ($user && $user->role && $user->role->role_name === 'Sales Person' && $user->branch_id) {
            $query->whereHas('sale', function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            });
        } elseif ($this->branch_id) {
            $branchId = $this->branch_id;
            $query->whereHas('sale', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        $items = $query
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->get();

        $products = $items->map(function ($item) {
            return [
                'product_name' => $item->product->name ?? 'N/A',
                'category' => $item->product->category->name ?? 'N/A',
                'unit' => $item->product->unit ?? 'N/A',
                'total_quantity' => (int) $item->total_quantity,
                'total_sales' => (float) $item->total_sales,
            ];
        });

        return [
            'products' => $products,
            'summary' => [
                'total_products' => $products->count(),
                'total_quantity' => $products->sum('total_quantity'),
                'total_sales' => $products->sum('total_sales'),
            ],
        ];
    }

    public function downloadPdf()
    {
        $data = $this->fastMovingData;
        $startDate = Carbon::parse($this->startDate)->format('M d, Y');
        $endDate = Carbon::parse($this->endDate)->format('M d, Y');

        $branchName = 'All Branches';
        if ($this->branch_id) {
            $branch = Branch::find($this->branch_id);
            $branchName = $branch ? $branch->name : 'All Branches';
        }

        $pdf = Pdf::loadView('pdf.fastmoving-report', [
            'data' => $data,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'branchName' => $branchName,
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'fastmoving-report-' . $this->startDate . '-to-' . $this->endDate . '.pdf');
    }
};
?>

<div class="p-3 sm:p-4 md:p-6">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-4 md:mb-6">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">Fast Moving Products</h1>
        <div class="flex items-center gap-2">
            @php
                $user = Auth::user();
                $isAdmin = $user && $user->role && $user->role->role_name === 'Admin';
            @endphp
            @if($isAdmin)
                <select wire:model.live="branch_id" class="px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 bg-white dark:bg-zinc-700 text-gray-900 dark:text-gray-100 text-sm">
                    <option value="">All Branches</option>
                    @foreach($this->branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            @endif
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

    @php $data = $this->fastMovingData; @endphp

    @if($data['products']->count() === 0)
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-6">
            <p class="text-yellow-800 dark:text-yellow-200 text-center">
                <strong>No fast moving products found</strong> for the selected period
            </p>
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-4 md:p-6 border border-gray-200 dark:border-zinc-700">
            <div class="flex items-center">
                <div class="shrink-0">
                    <svg class="w-12 h-12 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total Sales</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">Tsh {{ number_format($data['summary']['total_sales'], 0) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-4 md:p-6 border border-gray-200 dark:border-zinc-700">
            <div class="flex items-center">
                <div class="shrink-0">
                    <svg class="w-12 h-12 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3z"></path>
                        <path d="M16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total Quantity</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $data['summary']['total_quantity'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-4 md:p-6 border border-gray-200 dark:border-zinc-700">
            <div class="flex items-center">
                <div class="shrink-0">
                    <svg class="w-12 h-12 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Products</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $data['summary']['total_products'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md border border-gray-200 dark:border-zinc-700">
        <div class="p-4 md:p-6 border-b border-gray-200 dark:border-zinc-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Fast Moving Products List</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-zinc-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Product</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Category</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Quantity</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total Sales</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Unit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse($data['products'] as $index => $item)
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700">
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $index + 1 }}</td>
                            <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">{{ $item['product_name'] }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $item['category'] }}</td>
                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ $item['total_quantity'] }}</td>
                            <td class="px-4 py-3 text-right text-green-600 dark:text-green-400 font-medium">Tsh {{ number_format($item['total_sales'], 0) }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $item['unit'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No fast moving products found</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($data['products']->count() > 0)
                    <tfoot class="bg-gray-50 dark:bg-zinc-700 border-t-2 border-gray-300 dark:border-zinc-600">
                        <tr class="font-bold">
                            <td class="px-4 py-3 text-gray-900 dark:text-white" colspan="3">TOTAL</td>
                            <td class="px-4 py-3 text-right text-gray-900 dark:text-white">{{ $data['summary']['total_quantity'] }}</td>
                            <td class="px-4 py-3 text-right text-green-600 dark:text-green-400">Tsh {{ number_format($data['summary']['total_sales'], 0) }}</td>
                            <td class="px-4 py-3"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>