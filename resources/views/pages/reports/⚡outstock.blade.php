<?php

use Livewire\Component;
use App\Models\Stock;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Barryvdh\DomPDF\Facade\Pdf;

new class extends Component
{
    public $branch_id = '';
    
    public function mount()
    {
        $user = Auth::user();
        // Auto-select branch for Sales Person
        if ($user && $user->role && $user->role->role_name === 'Sales Person' && $user->branch_id) {
            $this->branch_id = $user->branch_id;
        }
    }
    
    #[Computed]
    public function branches()
    {
        $user = Auth::user();
        
        // Admin sees all branches
        if ($user && $user->role && $user->role->role_name === 'Admin') {
            return Branch::orderBy('name')->get();
        }
        
        // Sales Person sees only their branch
        if ($user && $user->branch_id) {
            return Branch::where('id', $user->branch_id)->get();
        }
        
        return collect();
    }
    
    #[Computed]
    public function outOfStockData()
    {
        $user = Auth::user();
        
        // Get stocks with quantity <= 0
        $query = Stock::with(['product.category', 'branch'])
            ->where('quantity', '<=', 0);
        
        // Filter by branch
        if ($user && $user->role && $user->role->role_name === 'Sales Person' && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($this->branch_id) {
            $query->where('branch_id', $this->branch_id);
        }
        
        $outOfStock = $query->get();
        
        // Prepare data
        $stockData = $outOfStock->map(function($stock) {
            return [
                'product_name' => $stock->product->name,
                'category' => $stock->product->category->name ?? 'N/A',
                'branch_name' => $stock->branch->name,
                'buy_price' => $stock->buy_price,
                'sell_price' => $stock->sell_price,
                'quantity' => $stock->quantity,
                'unit' => $stock->product->unit,
            ];
        });
        
        return [
            'stocks' => $stockData,
            'summary' => [
                'total_products' => $stockData->count(),
            ]
        ];
    }
    
    public function downloadPdf()
    {
        $data = $this->outOfStockData;
        $user = Auth::user();
        $isAdmin = $user && $user->role && $user->role->role_name === 'Admin';
        
        $branchName = 'All Branches';
        if ($this->branch_id) {
            $branch = Branch::find($this->branch_id);
            $branchName = $branch ? $branch->name : 'All Branches';
        }
        
        $pdf = Pdf::loadView('pdf.outstock-report', [
            'data' => $data,
            'branchName' => $branchName,
            'isAdmin' => $isAdmin
        ])->setPaper('a4', 'landscape');
        
        return response()->streamDownload(function() use ($pdf) {
            echo $pdf->output();
        }, 'out-of-stock-report-' . date('Y-m-d') . '.pdf');
    }
};
?>

<div class="p-3 sm:p-4 md:p-6">
    
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-4 md:mb-6">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">Out of Stock Products</h1>
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
            <button wire:click="downloadPdf" class="flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors text-sm font-medium">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
                <span>Download PDF</span>
            </button>
        </div>
    </div>

    @php $data = $this->outOfStockData; @endphp
    
    @if($data['stocks']->count() === 0)
    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 mb-6">
        <p class="text-green-800 dark:text-green-200 text-center">
            <strong>Great!</strong> No products are currently out of stock
            @if($branch_id)
                in the selected branch
            @endif
        </p>
    </div>
    @endif
    
    <!-- Summary Card -->
    <div class="grid grid-cols-1 sm:grid-cols-1 gap-4 mb-6">
        <!-- Total Out of Stock -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-4 md:p-6 border border-gray-200 dark:border-zinc-700">
            <div class="flex items-center">
                <div class="shrink-0">
                    <svg class="w-12 h-12 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total Out of Stock Products</p>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $data['summary']['total_products'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Out of Stock Table -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md border border-gray-200 dark:border-zinc-700">
        <div class="p-4 md:p-6 border-b border-gray-200 dark:border-zinc-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Products List</h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-zinc-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Product Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Branch</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Buy Price</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Sell Price</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Quantity</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Unit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse($data['stocks'] as $index => $item)
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700">
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $index + 1 }}</td>
                            <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">{{ $item['product_name'] }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $item['category'] }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $item['branch_name'] }}</td>
                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">Tsh {{ number_format($item['buy_price'], 0) }}</td>
                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">Tsh {{ number_format($item['sell_price'], 0) }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 rounded-full text-xs font-bold">
                                    {{ $item['quantity'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $item['unit'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No out of stock products found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>