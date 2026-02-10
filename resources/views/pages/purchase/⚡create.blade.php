<?php

use Livewire\Component;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Stock;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

new class extends Component
{
    public $supplier_id;
    public $branch_id;
    public $product_id;
    public $quantity = 1;
    public $buy_price = 0;
    public $cart = [];
    public $search = '';
    
    public function mount()
    {
        $user = Auth::user();
        
        // Auto-detect branch for Sales Person
        if ($user && $user->role && $user->role->role_name === 'Sales Person' && $user->branch_id) {
            $this->branch_id = $user->branch_id;
        }
    }
    
    public function addToCart()
    {
        $this->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'branch_id' => 'required|exists:branches,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'buy_price' => 'required|numeric|min:0',
        ]);
        
        // Check if product already in cart
        $found = false;
        foreach ($this->cart as $key => $item) {
            if ($item['product_id'] == $this->product_id) {
                $this->cart[$key]['quantity'] += $this->quantity;
                $this->cart[$key]['subtotal'] = $this->cart[$key]['quantity'] * $this->cart[$key]['buy_price'];
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $product = Product::with(['category', 'stocks' => function($query) {
                $query->where('branch_id', $this->branch_id);
            }])->find($this->product_id);
            
            $stock = $product->stocks->first();
            $currentSellPrice = $stock ? $stock->sell_price : null;
            
            // Use existing sell price or calculate 30% markup
            $sellPrice = $currentSellPrice ? $currentSellPrice : ($this->buy_price * 1.3);
            
            $this->cart[] = [
                'product_id' => $this->product_id,
                'product_name' => $product->name,
                'category' => $product->category->name,
                'quantity' => $this->quantity,
                'buy_price' => $this->buy_price,
                'sell_price' => $sellPrice,
                'current_sell_price' => $currentSellPrice,
                'subtotal' => $this->quantity * $this->buy_price,
            ];
        }
        
        // Reset form
        $this->reset(['product_id', 'quantity', 'buy_price']);
        $this->quantity = 1;
        $this->buy_price = 0;
    }
    
    public function removeFromCart($index)
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);
    }
    
    public function updateCartQuantity($index, $quantity)
    {
        if ($quantity < 1) {
            return;
        }
        
        $this->cart[$index]['quantity'] = $quantity;
        $this->cart[$index]['subtotal'] = $quantity * $this->cart[$index]['buy_price'];
    }
    
    public function updateCartPrice($index, $price)
    {
        if ($price < 0) {
            return;
        }
        
        $this->cart[$index]['buy_price'] = $price;
        $this->cart[$index]['subtotal'] = $this->cart[$index]['quantity'] * $price;
    }
    
    public function completePurchase()
    {
        if (empty($this->cart)) {
            session()->flash('error', 'Cart is empty!');
            return;
        }
        
        $this->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'branch_id' => 'required|exists:branches,id',
        ]);
        
        DB::beginTransaction();
        
        try {
            // Generate invoice number
            $lastPurchase = Purchase::whereDate('created_at', today())->latest()->first();
            $invoiceNo = 'PUR-' . date('Ymd') . '-' . str_pad(($lastPurchase ? $lastPurchase->id + 1 : 1), 4, '0', STR_PAD_LEFT);
            
            // Calculate total
            $totalAmount = array_sum(array_column($this->cart, 'subtotal'));
            
            // Create purchase
            $purchase = Purchase::create([
                'supplier_id' => $this->supplier_id,
                'branch_id' => $this->branch_id,
                'invoice_no' => $invoiceNo,
                'purchase_date' => now(),
                'total_amount' => $totalAmount,
            ]);
            
            // Create purchase items and increase stock
            foreach ($this->cart as $item) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['buy_price'],
                ]);
                
                // Find or create stock for this product in the branch
                $stock = Stock::where('branch_id', $this->branch_id)
                             ->where('product_id', $item['product_id'])
                             ->first();
                
                if ($stock) {
                    // Update existing stock
                    $stock->increment('quantity', $item['quantity']);
                    // Update buy price and sell price
                    $stock->update([
                        'buy_price' => $item['buy_price'],
                        'sell_price' => $item['sell_price'],
                    ]);
                } else {
                    // Create new stock entry
                    Stock::create([
                        'product_id' => $item['product_id'],
                        'branch_id' => $this->branch_id,
                        'buy_price' => $item['buy_price'],
                        'sell_price' => $item['sell_price'],
                        'quantity' => $item['quantity'],
                    ]);
                }
            }
            
            DB::commit();
            
            session()->flash('message', 'Purchase completed successfully! Invoice: ' . $invoiceNo);
            $this->reset(['cart', 'supplier_id', 'product_id', 'quantity', 'buy_price']);
            $this->quantity = 1;
            $this->buy_price = 0;
            
            // Keep branch_id if Sales Person
            $user = Auth::user();
            if ($user && $user->role && $user->role->role_name === 'Sales Person' && $user->branch_id) {
                $this->branch_id = $user->branch_id;
            } else {
                $this->branch_id = null;
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to complete purchase: ' . $e->getMessage());
        }
    }
    
    #[Computed]
    public function getSuppliersProperty()
    {
        return Supplier::orderBy('name')->get();
    }
    
    #[Computed]
    public function getBranchesProperty()
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
    public function getProductsProperty()
    {
        if (!$this->branch_id) {
            return Product::with('category')->orderBy('name')->get()->map(function($product) {
                $product->current_sell_price = null;
                return $product;
            });
        }
        
        return Product::with(['category', 'stocks' => function($query) {
            $query->where('branch_id', $this->branch_id);
        }])->orderBy('name')->get()->map(function($product) {
            $stock = $product->stocks->first();
            $product->current_sell_price = $stock ? $stock->sell_price : null;
            return $product;
        });
    }
    
    #[Computed]
    public function getPurchasesProperty()
    {
        $user = Auth::user();
        
        $query = Purchase::with(['items.product', 'supplier', 'branch']);
        
        // Filter by branch for Sales Person
        if ($user && $user->role && $user->role->role_name === 'Sales Person' && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }
        
        if ($this->search) {
            $query->where('invoice_no', 'like', '%' . $this->search . '%');
        }
        
        return $query->latest()->limit(50)->get();
    }
    
    public function getCartTotal()
    {
        return array_sum(array_column($this->cart, 'subtotal'));
    }
};
?>

<div class="w-full px-2 sm:px-4 py-4 max-w-7xl mx-auto">

<!-- Success/Error Messages -->
@if (session()->has('message'))
    <div class="mb-4 p-3 sm:p-4 text-xs sm:text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400 animate-fade-in" role="alert">
        <span class="font-medium">Success!</span> {{ session('message') }}
    </div>
@endif

@if (session()->has('error'))
    <div class="mb-4 p-3 sm:p-4 text-xs sm:text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 animate-fade-in" role="alert">
        <span class="font-medium">Error!</span> {{ session('error') }}
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-3 sm:gap-4 lg:gap-6">
    
    <!-- Left: Purchase Form -->
    <div class="lg:col-span-2 order-2 lg:order-1">
        <div class="bg-white dark:bg-gray-800 shadow-lg sm:rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 border-t-4 border-t-blue-600 transition-shadow hover:shadow-xl">
            <div class="p-4 sm:p-5 lg:p-6">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4 sm:mb-5">
                    <h3 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-6 h-6 sm:w-7 sm:h-7 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"></path>
                        </svg>
                        <span class="hidden sm:inline">New Purchase</span>
                        <span class="sm:hidden">Purchase</span>
                    </h3>
                </div>

                <!-- Purchase Form -->
                <form wire:submit.prevent="addToCart" class="space-y-4 sm:space-y-5">
                    <!-- Supplier Selection -->
                    <div>
                        <label class="block mb-2 text-sm sm:text-base font-medium text-gray-900 dark:text-white">Select Supplier</label>
                        <select wire:model="supplier_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-xs sm:text-sm rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 sm:p-3 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white transition-colors">
                            <option value="">Choose a supplier</option>
                            @foreach($this->suppliers as $supplier)
                                <option value="{{ $supplier->id }}">
                                    {{ $supplier->name }} @if($supplier->phone) - {{ $supplier->phone }} @endif
                                </option>
                            @endforeach
                        </select>
                        @error('supplier_id') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Branch Selection -->
                    <div>
                        <label class="block mb-2 text-sm sm:text-base font-medium text-gray-900 dark:text-white">Branch</label>
                        <select wire:model="branch_id" @if(Auth::user()->role && Auth::user()->role->role_name === 'Sales Person') disabled @endif class="bg-gray-50 border border-gray-300 text-gray-900 text-xs sm:text-sm rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 sm:p-3 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white transition-colors">
                            <option value="">Choose a branch</option>
                            @foreach($this->branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Product Selection -->
                    <div>
                        <label class="block mb-2 text-sm sm:text-base font-medium text-gray-900 dark:text-white">Select Product</label>
                        <select wire:model="product_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-xs sm:text-sm rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 sm:p-3 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white transition-colors">
                            <option value="">Choose a product</option>
                            @foreach($this->products as $product)
                                <option value="{{ $product->id }}">
                                    {{ $product->name }} - {{ $product->category->name }}
                                    @if($product->current_sell_price)
                                        (Current: Tsh {{ number_format($product->current_sell_price, 0) }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('product_id') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Quantity -->
                        <div>
                            <label class="block mb-2 text-sm sm:text-base font-medium text-gray-900 dark:text-white">Quantity</label>
                            <input type="number" wire:model="quantity" min="1" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5 sm:p-3 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white transition-colors" placeholder="Enter quantity">
                            @error('quantity') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Buy Price -->
                        <div>
                            <label class="block mb-2 text-sm sm:text-base font-medium text-gray-900 dark:text-white">Buy Price (Tsh)</label>
                            <input type="number" wire:model="buy_price" min="0" step="0.01" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5 sm:p-3 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white transition-colors" placeholder="Enter buy price">
                            @error('buy_price') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <button type="submit" class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-semibold rounded-lg text-sm sm:text-base px-5 py-3 sm:py-3.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 transition-all transform active:scale-95">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 inline" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"></path>
                        </svg>
                        <span class="hidden sm:inline">Add to Cart</span>
                        <span class="sm:hidden">Add</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Right: Cart -->
    <div class="lg:col-span-1 order-1 lg:order-2">
        <div class="bg-white dark:bg-gray-800 shadow-lg sm:rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 border-t-4 border-t-purple-600 lg:sticky lg:top-4 transition-shadow hover:shadow-xl">
            <div class="p-4 sm:p-5 lg:p-6">
                <!-- Cart Header -->
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-6 h-6 mr-2 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"></path>
                        </svg>
                        Cart
                    </h3>
                    <span class="inline-flex items-center justify-center w-7 h-7 sm:w-8 sm:h-8 text-xs sm:text-sm font-bold text-white bg-purple-600 rounded-full">
                        {{ count($cart) }}
                    </span>
                </div>
                
                @if(empty($cart))
                    <div class="text-center py-8 sm:py-12 text-gray-500 dark:text-gray-400">
                        <svg class="w-16 h-16 sm:w-20 sm:h-20 mx-auto mb-3 opacity-40" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"></path>
                        </svg>
                        <p class="text-sm sm:text-base font-medium">Cart is empty</p>
                        <p class="text-xs sm:text-sm mt-1">Add products to start</p>
                    </div>
                @else
                    <div class="space-y-2 sm:space-y-3 mb-4 max-h-[300px] sm:max-h-[400px] overflow-y-auto custom-scrollbar">
                        @foreach($cart as $index => $item)
                            <div class="p-3 sm:p-4 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex-1 min-w-0 pr-2">
                                        <h4 class="text-sm sm:text-base font-semibold text-gray-900 dark:text-white truncate">{{ $item['product_name'] }}</h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $item['category'] }}</p>
                                        @if(isset($item['current_sell_price']) && $item['current_sell_price'])
                                            <p class="text-xs text-blue-600 dark:text-blue-400 mt-0.5">Current: Tsh {{ number_format($item['current_sell_price'], 0) }}</p>
                                        @endif
                                    </div>
                                    <button wire:click="removeFromCart({{ $index }})" class="flex-shrink-0 p-1.5 text-red-600 hover:text-white hover:bg-red-600 dark:text-red-400 dark:hover:bg-red-500 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                        </svg>
                                    </button>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex items-center space-x-2">
                                        <label class="text-xs text-gray-600 dark:text-gray-400">Qty:</label>
                                        <input type="number" wire:change="updateCartQuantity({{ $index }}, $event.target.value)" value="{{ $item['quantity'] }}" min="1" class="w-20 text-sm p-1.5 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <label class="text-xs text-gray-600 dark:text-gray-400">Buy:</label>
                                        <input type="number" wire:change="updateCartPrice({{ $index }}, $event.target.value)" value="{{ $item['buy_price'] }}" min="0" step="0.01" class="flex-1 text-sm p-1.5 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <label class="text-xs text-gray-600 dark:text-gray-400">Sell:</label>
                                        <span class="flex-1 text-sm p-1.5 bg-green-50 dark:bg-green-900/20 rounded-lg font-medium text-green-700 dark:text-green-400">Tsh {{ number_format($item['sell_price'], 0) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center pt-2 border-t border-gray-200 dark:border-gray-600">
                                        <span class="text-xs text-gray-600 dark:text-gray-400">Subtotal:</span>
                                        <span class="text-sm sm:text-base font-bold text-gray-900 dark:text-white">Tsh {{ number_format($item['subtotal'], 0) }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <div class="border-t-2 border-gray-300 dark:border-gray-600 pt-4">
                        <div class="flex justify-between items-center mb-4 sm:mb-5">
                            <span class="text-base sm:text-lg font-bold text-gray-900 dark:text-white">Total:</span>
                            <span class="text-xl sm:text-2xl lg:text-3xl font-bold text-purple-600 dark:text-purple-400">Tsh {{ number_format($this->getCartTotal(), 0) }}</span>
                        </div>
                        
                        <button wire:click="completePurchase" class="w-full text-white bg-cyan-600 hover:bg-cyan-700 focus:ring-4 focus:outline-none focus:ring-cyan-300 font-bold rounded-lg text-sm sm:text-base px-5 py-3 sm:py-4 text-center dark:bg-cyan-500 dark:hover:bg-cyan-600 shadow-lg hover:shadow-xl transition-all transform active:scale-95">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 inline" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            Complete Purchase
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Purchase History -->
<div class="mt-4 sm:mt-6 lg:mt-8">
    <div class="bg-white dark:bg-gray-800 shadow-lg sm:rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 border-t-4 border-t-blue-600">
        <div class="flex flex-col space-y-3 p-4 sm:p-5 lg:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <h3 class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-900 dark:text-white flex items-center">
                    <svg class="w-6 h-6 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path>
                    </svg>
                    Recent Purchases
                </h3>
                
                <!-- Search Box -->
                <div class="w-full sm:w-auto sm:min-w-[250px] lg:min-w-[300px]">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input type="text" wire:model.live="search" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2.5 sm:p-3 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white transition-colors" placeholder="Search invoice...">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Mobile Card View -->
        <div class="block lg:hidden divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($this->purchases as $purchase)
                <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors" wire:key="purchase-mobile-{{$purchase->id}}">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <div class="font-bold text-gray-900 dark:text-white text-base">{{ $purchase->invoice_no }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $purchase->purchase_date->format('M d, Y') }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold text-purple-600 dark:text-purple-400">Tsh {{ number_format($purchase->total_amount, 0) }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $purchase->items->count() }} item(s)</div>
                        </div>
                    </div>
                    <div class="flex flex-col gap-2 mt-3 pt-3 border-t border-gray-200 dark:border-gray-600">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1.5 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path>
                            </svg>
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $purchase->supplier->name }}</span>
                        </div>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-1.5 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $purchase->branch->name }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center">
                    <svg class="w-16 h-16 mx-auto mb-3 text-gray-400 opacity-50" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400 text-sm font-medium">No purchases yet</p>
                    <p class="text-gray-400 dark:text-gray-500 text-xs mt-1">Create your first purchase above</p>
                </div>
            @endforelse
        </div>

        <!-- Desktop Table View -->
        <div class="hidden lg:block overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-4 font-semibold">Invoice</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Date</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Supplier</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Branch</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Items</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($this->purchases as $purchase)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors" wire:key="purchase-{{$purchase->id}}">
                        <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">{{ $purchase->invoice_no }}</td>
                        <td class="px-6 py-4 text-gray-700 dark:text-gray-300">{{ $purchase->purchase_date->format('M d, Y') }}</td>
                        <td class="px-6 py-4 text-gray-700 dark:text-gray-300">{{ $purchase->supplier->name }}</td>
                        <td class="px-6 py-4 text-gray-700 dark:text-gray-300">{{ $purchase->branch->name }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                {{ $purchase->items->count() }} item(s)
                            </span>
                        </td>
                        <td class="px-6 py-4 font-bold text-purple-600 dark:text-purple-400">Tsh {{ number_format($purchase->total_amount, 0) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <svg class="w-16 h-16 mx-auto mb-3 text-gray-400 opacity-50" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path>
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400 font-medium">No purchases yet</p>
                            <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Create your first purchase above</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.05);
        border-radius: 3px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(0, 0, 0, 0.2);
        border-radius: 3px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(0, 0, 0, 0.3);
    }
    
    @keyframes fade-in {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .animate-fade-in {
        animation: fade-in 0.3s ease-out;
    }
</style>

</div>