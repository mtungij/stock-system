<?php

use Livewire\Component;
use App\Models\StockAdjustment;
use App\Models\Stock;
use App\Models\Product;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

new class extends Component
{
    public $product_id;
    public $branch_id;
    public $adjustment_type = 'manual_correction';
    public $action = 'decrease';
    public $quantity = 1;
    public $reason;
    public $search = '';
    public $current_stock_quantity = null;
    
    public function mount()
    {
        $user = Auth::user();
        
        // Auto-detect branch for Sales Person
        if ($user && $user->role && $user->role->role_name === 'Sales Person' && $user->branch_id) {
            $this->branch_id = $user->branch_id;
        }
    }
    
    public function updatedProductId()
    {
        // Update current stock quantity when product changes
        if ($this->product_id && $this->branch_id) {
            $stock = Stock::where('product_id', $this->product_id)
                ->where('branch_id', $this->branch_id)
                ->first();
            $this->current_stock_quantity = $stock ? $stock->quantity : 0;
        } else {
            $this->current_stock_quantity = null;
        }
    }
    
    public function updatedBranchId()
    {
        // Reset product selection when branch changes
        $this->product_id = null;
        $this->current_stock_quantity = null;
        
        // Update current stock quantity when branch changes
        if ($this->product_id && $this->branch_id) {
            $stock = Stock::where('product_id', $this->product_id)
                ->where('branch_id', $this->branch_id)
                ->first();
            $this->current_stock_quantity = $stock ? $stock->quantity : 0;
        }
    }
    
    #[Computed]
    public function products()
    {
        $query = Product::with(['category', 'stocks' => function($q) {
            if ($this->branch_id) {
                $q->where('branch_id', $this->branch_id);
            }
        }]);
        
        // If branch is selected, only show products that have stock in that branch
        if ($this->branch_id) {
            $query->whereHas('stocks', function($q) {
                $q->where('branch_id', $this->branch_id);
            });
        }
        
        return $query->orderBy('name')->get();
    }
    
    #[Computed]
    public function branches()
    {
        return Branch::orderBy('name')->get();
    }
    
    #[Computed]
    public function stocks()
    {
        $user = Auth::user();
        $query = Stock::with(['product', 'branch']);
        
        // Filter by branch for Sales Person
        if ($user && $user->role && $user->role->role_name === 'Sales Person' && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($this->branch_id) {
            $query->where('branch_id', $this->branch_id);
        }
        
        return $query->orderBy('quantity', 'desc')->get();
    }
    
    #[Computed]
    public function adjustments()
    {
        $user = Auth::user();
        $query = StockAdjustment::with(['stock.product', 'stock.branch', 'user']);
        
        // Filter by branch for Sales Person
        if ($user && $user->role && $user->role->role_name === 'Sales Person' && $user->branch_id) {
            $query->whereHas('stock', function($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            });
        }
        
        // Search functionality
        if ($this->search) {
            $query->where(function($q) {
                $q->whereHas('stock.product', function($q2) {
                    $q2->where('name', 'like', '%' . $this->search . '%');
                })
                ->orWhere('adjustment_type', 'like', '%' . $this->search . '%')
                ->orWhere('reason', 'like', '%' . $this->search . '%');
            });
        }
        
        return $query->orderBy('created_at', 'desc')->get();
    }
    
    public function saveAdjustment()
    {
        $this->validate([
            'product_id' => 'required|exists:products,id',
            'branch_id' => 'required|exists:branches,id',
            'adjustment_type' => 'required|in:damaged,expired,manual_correction,stock_count',
            'action' => 'required|in:increase,decrease',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:1000',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Find or create stock for this product-branch combination
            $stock = Stock::where('product_id', $this->product_id)
                ->where('branch_id', $this->branch_id)
                ->first();
            
            if (!$stock) {
                // Create new stock entry if doesn't exist (for increase operations)
                if ($this->action === 'decrease') {
                    $this->dispatch('show-toast', type: 'error', message: 'Stock does not exist for this product in selected branch!');
                    return;
                }
                
                $stock = Stock::create([
                    'product_id' => $this->product_id,
                    'branch_id' => $this->branch_id,
                    'quantity' => 0,
                    'buy_price' => 0,
                    'sell_price' => 0,
                ]);
            }
            
            $quantity_before = $stock->quantity;
            
            // Calculate new quantity
            if ($this->action === 'increase') {
                $quantity_after = $quantity_before + $this->quantity;
            } else {
                $quantity_after = $quantity_before - $this->quantity;
                
                // Prevent negative stock
                if ($quantity_after < 0) {
                    $this->dispatch('show-toast', type: 'error', message: 'Insufficient stock! Current quantity: ' . $quantity_before);
                    return;
                }
            }
            
            // Create adjustment record
            StockAdjustment::create([
                'stock_id' => $stock->id,
                'user_id' => Auth::id(),
                'adjustment_type' => $this->adjustment_type,
                'action' => $this->action,
                'quantity' => $this->quantity,
                'quantity_before' => $quantity_before,
                'quantity_after' => $quantity_after,
                'reason' => $this->reason,
            ]);
            
            // Update stock quantity
            $stock->update(['quantity' => $quantity_after]);
            
            DB::commit();
            
            // Reset form
            $this->reset(['product_id', 'quantity', 'reason']);
            $this->quantity = 1;
            $this->adjustment_type = 'manual_correction';
            $this->action = 'decrease';
            $this->current_stock_quantity = null;
            
            $this->dispatch('show-toast', type: 'success', message: 'Stock adjustment saved successfully!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('show-toast', type: 'error', message: 'Error saving adjustment: ' . $e->getMessage());
        }
    }
    
    public function with(): array
    {
        return [];
    }
}

?>

<div class="p-3 sm:p-4 md:p-6" 
    x-data="{
        init() {
            Livewire.on('show-toast', (event) => {
                const type = event.type;
                const message = event.message;
                
                const config = type === 'success' 
                    ? {
                        background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
                        icon: '✓'
                    }
                    : {
                        background: 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)',
                        icon: '✕'
                    };
                
                const toastContent = document.createElement('div');
                toastContent.style.display = 'flex';
                toastContent.style.alignItems = 'center';
                toastContent.style.gap = '12px';
                
                const iconSpan = document.createElement('span');
                iconSpan.style.fontSize = '20px';
                iconSpan.style.fontWeight = 'bold';
                iconSpan.textContent = config.icon;
                
                const messageSpan = document.createElement('span');
                messageSpan.style.fontSize = '14px';
                messageSpan.style.fontWeight = '500';
                messageSpan.textContent = message;
                
                toastContent.appendChild(iconSpan);
                toastContent.appendChild(messageSpan);
                
                window.Toastify({
                    node: toastContent,
                    duration: 4000,
                    gravity: 'top',
                    position: 'right',
                    close: true,
                    stopOnFocus: true,
                    offset: {
                        x: 20,
                        y: 20
                    },
                    style: {
                        background: config.background,
                        borderRadius: '12px',
                        padding: '16px 20px',
                        boxShadow: '0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.2)',
                        fontSize: '14px',
                        fontWeight: '500',
                        minWidth: '320px',
                        maxWidth: '420px'
                    }
                }).showToast();
            });
        }
    }">
    <div class="flex justify-between items-center mb-4 md:mb-6">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">Stock Adjustments</h1>
    </div>

    <!-- Adjustment Form -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-4 md:p-6 border border-gray-200 dark:border-zinc-700 mb-4 md:mb-6">
            <h2 class="text-base sm:text-lg font-semibold mb-3 md:mb-4 text-gray-900 dark:text-white">New Adjustment</h2>
            
            <form wire:submit.prevent="saveAdjustment" class="space-y-4">
                <!-- Branch Selection (for Admin or when not auto-detected) -->
                @php
                    $user = Auth::user();
                    $isSalesPerson = $user && $user->role && $user->role->role_name === 'Sales Person' && $user->branch_id;
                @endphp
                
                @if(!$isSalesPerson)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Select Branch First</label>
                        <select wire:model.live="branch_id" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 bg-white dark:bg-zinc-800 text-gray-900 dark:text-gray-100">
                            <option value="">-- Select Branch --</option>
                            @foreach($this->branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id') <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span> @enderror
                    </div>
                @endif

                <!-- Product Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Select Product</label>
                    <select wire:model.live="product_id" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 bg-white dark:bg-zinc-800 text-gray-900 dark:text-gray-100 disabled:opacity-50 disabled:cursor-not-allowed" {{ !$isSalesPerson && !$this->branch_id ? 'disabled' : '' }}>
                        <option value="">{{ !$isSalesPerson && !$this->branch_id ? '-- Select Branch First --' : '-- Select Product --' }}</option>
                        @foreach($this->products as $product)
                            @php
                                $productStock = $product->stocks->where('branch_id', $this->branch_id)->first();
                                $currentQty = $productStock ? $productStock->quantity : 0;
                            @endphp
                            <option value="{{ $product->id }}">
                                {{ $product->name }} - {{ $product->category->category_name }} (Stock: {{ $currentQty }})
                            </option>
                        @endforeach
                    </select>
                    @error('product_id') <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Current Stock Display -->
                @if($current_stock_quantity !== null)
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Current Stock:</span>
                            <span class="text-lg font-bold {{ $current_stock_quantity <= 5 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                {{ $current_stock_quantity }} units
                            </span>
                        </div>
                    </div>
                @endif

                <!-- Adjustment Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Adjustment Type</label>
                    <select wire:model="adjustment_type" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 bg-white dark:bg-zinc-800 text-gray-900 dark:text-gray-100">
                        <option value="manual_correction">Manual Correction</option>
                        <option value="damaged">Damaged Items</option>
                        <option value="expired">Expired Items</option>
                        <option value="stock_count">Stock Count Difference</option>
                    </select>
                    @error('adjustment_type') <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Action -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Action</label>
                    <div class="flex gap-4">
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" wire:model="action" value="increase" class="mr-2 text-blue-600 focus:ring-blue-500">
                            <span class="text-green-600 dark:text-green-400 font-medium">Increase</span>
                        </label>
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" wire:model="action" value="decrease" class="mr-2 text-blue-600 focus:ring-blue-500">
                            <span class="text-red-600 dark:text-red-400 font-medium">Decrease</span>
                        </label>
                    </div>
                    @error('action') <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Quantity -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Quantity</label>
                    <input 
                        type="number" 
                        wire:model="quantity" 
                        min="1"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 bg-white dark:bg-zinc-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500"
                        placeholder="Enter quantity"
                    >
                    @error('quantity') <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Reason -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Reason (Optional)</label>
                    <textarea 
                        wire:model="reason" 
                        rows="3"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 bg-white dark:bg-zinc-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500"
                        placeholder="Explain the reason for this adjustment"
                    ></textarea>
                    @error('reason') <span class="text-red-500 dark:text-red-400 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg transition"
                >
                    Save Adjustment
                </button>
            </form>
    </div>

    <!-- Adjustment History -->
    <div class="mt-4 md:mt-6 bg-white dark:bg-zinc-800 rounded-lg shadow-md p-4 md:p-6 border border-gray-200 dark:border-zinc-700">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-4">
            <h2 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white">Adjustment History</h2>
            
            <!-- Search -->
            <input 
                type="text" 
                wire:model.live="search" 
                placeholder="Search adjustments..."
                class="px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 bg-white dark:bg-zinc-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 w-full sm:w-64 text-sm"
            >
        </div>

        <!-- Desktop/Tablet Table View -->
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-zinc-700">
                    <tr>
                        <th class="px-2 sm:px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                        <th class="px-2 sm:px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Product</th>
                        <th class="px-2 sm:px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Branch</th>
                        <th class="px-2 sm:px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Type</th>
                        <th class="px-2 sm:px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Action</th>
                        <th class="px-2 sm:px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Qty</th>
                        <th class="px-2 sm:px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Before</th>
                        <th class="px-2 sm:px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">After</th>
                        <th class="px-2 sm:px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">User</th>
                        <th class="px-2 sm:px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Reason</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse($this->adjustments as $adjustment)
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700">
                            <td class="px-2 sm:px-4 py-2 text-xs sm:text-sm text-gray-900 dark:text-gray-300 whitespace-nowrap">{{ $adjustment->created_at->format('Y-m-d H:i') }}</td>
                            <td class="px-2 sm:px-4 py-2 text-xs sm:text-sm text-gray-900 dark:text-gray-300">{{ $adjustment->stock->product->name }}</td>
                            <td class="px-2 sm:px-4 py-2 text-xs sm:text-sm text-gray-900 dark:text-gray-300">{{ $adjustment->stock->branch->name }}</td>
                            <td class="px-2 sm:px-4 py-2 text-xs sm:text-sm">
                                <span class="px-1.5 sm:px-2 py-0.5 sm:py-1 text-xs rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                    {{ ucfirst(str_replace('_', ' ', $adjustment->adjustment_type)) }}
                                </span>
                            </td>
                            <td class="px-2 sm:px-4 py-2 text-xs sm:text-sm text-center">
                                <span class="px-1.5 sm:px-2 py-0.5 sm:py-1 text-xs rounded-full {{ $adjustment->action === 'increase' ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300' }}">
                                    {{ ucfirst($adjustment->action) }}
                                </span>
                            </td>
                            <td class="px-2 sm:px-4 py-2 text-xs sm:text-sm text-center font-semibold text-gray-900 dark:text-gray-300">{{ $adjustment->quantity }}</td>
                            <td class="px-2 sm:px-4 py-2 text-xs sm:text-sm text-center text-gray-900 dark:text-gray-300">{{ $adjustment->quantity_before }}</td>
                            <td class="px-2 sm:px-4 py-2 text-xs sm:text-sm text-center text-gray-900 dark:text-gray-300">{{ $adjustment->quantity_after }}</td>
                            <td class="px-2 sm:px-4 py-2 text-xs sm:text-sm text-gray-900 dark:text-gray-300">{{ $adjustment->user->name }}</td>
                            <td class="px-2 sm:px-4 py-2 text-xs sm:text-sm text-gray-900 dark:text-gray-300 max-w-xs truncate">{{ $adjustment->reason ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-2 sm:px-4 py-4 sm:py-8 text-center text-gray-500 dark:text-gray-400 text-xs sm:text-sm">No adjustments found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View -->
        <div class="sm:hidden space-y-3">
            @forelse($this->adjustments as $adjustment)
                <div class="border border-gray-200 dark:border-zinc-700 rounded-lg p-4 bg-white dark:bg-zinc-800">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $adjustment->stock->product->name }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $adjustment->stock->branch->name }}</p>
                        </div>
                        <span class="px-2 py-1 text-xs rounded-full {{ $adjustment->action === 'increase' ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300' }}">
                            {{ ucfirst($adjustment->action) }}
                        </span>
                    </div>
                    
                    <div class="space-y-1 text-sm">
                        <p><span class="text-gray-600 dark:text-gray-400">Type:</span> <span class="text-gray-900 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $adjustment->adjustment_type)) }}</span></p>
                        <p><span class="text-gray-600 dark:text-gray-400">Quantity:</span> <span class="font-semibold text-gray-900 dark:text-gray-300">{{ $adjustment->quantity }}</span></p>
                        <p><span class="text-gray-600 dark:text-gray-400">Before/After:</span> <span class="text-gray-900 dark:text-gray-300">{{ $adjustment->quantity_before }} → {{ $adjustment->quantity_after }}</span></p>
                        <p><span class="text-gray-600 dark:text-gray-400">User:</span> <span class="text-gray-900 dark:text-gray-300">{{ $adjustment->user->name }}</span></p>
                        <p><span class="text-gray-600 dark:text-gray-400">Date:</span> <span class="text-gray-900 dark:text-gray-300">{{ $adjustment->created_at->format('Y-m-d H:i') }}</span></p>
                        @if($adjustment->reason)
                            <p><span class="text-gray-600 dark:text-gray-400">Reason:</span> <span class="text-gray-900 dark:text-gray-300">{{ $adjustment->reason }}</span></p>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-center text-gray-500 dark:text-gray-400 py-8">No adjustments found</p>
            @endforelse
        </div>
    </div>
</div>