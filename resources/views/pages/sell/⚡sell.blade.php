<?php

use Livewire\Component;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

new class extends Component
{
    public $branch_id;
    public $product_id;
    public $quantity = 1;
    public $cart = [];
    public $search = '';
    
    public function mount()
    {
        $user = Auth::user();
        
        // Auto-detect branch
        if ($user && $user->branch_id) {
            $this->branch_id = $user->branch_id;
        }
    }
    
    public function addToCart()
    {
        $this->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);
        
        // Get stock for this product in user's branch
        $stock = Stock::where('branch_id', $this->branch_id)
                     ->where('product_id', $this->product_id)
                     ->first();
        
        if (!$stock) {
            $this->addError('product_id', 'Product not available in your branch.');
            return;
        }
        
        // Check if already in cart
        $existingCartQty = 0;
        foreach ($this->cart as $item) {
            if ($item['product_id'] == $this->product_id) {
                $existingCartQty = $item['quantity'];
            }
        }
        
        // Check stock availability
        if (($this->quantity + $existingCartQty) > $stock->quantity) {
            $this->addError('quantity', 'Insufficient stock. Available: ' . ($stock->quantity - $existingCartQty));
            return;
        }
        
        // Check if product already in cart
        $found = false;
        foreach ($this->cart as $key => $item) {
            if ($item['product_id'] == $this->product_id) {
                $this->cart[$key]['quantity'] += $this->quantity;
                $this->cart[$key]['subtotal'] = $this->cart[$key]['quantity'] * $this->cart[$key]['price'];
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $product = Product::with('category')->find($this->product_id);
            $this->cart[] = [
                'product_id' => $this->product_id,
                'product_name' => $product->name,
                'category' => $product->category->name,
                'quantity' => $this->quantity,
                'price' => $stock->sell_price,
                'subtotal' => $this->quantity * $stock->sell_price,
            ];
        }
        
        // Reset form
        $this->reset(['product_id', 'quantity']);
        $this->quantity = 1;
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
        
        $item = $this->cart[$index];
        
        // Check stock availability
        $stock = Stock::where('branch_id', $this->branch_id)
                     ->where('product_id', $item['product_id'])
                     ->first();
        
        if ($quantity > $stock->quantity) {
            session()->flash('error', 'Insufficient stock for ' . $item['product_name'] . '. Available: ' . $stock->quantity);
            return;
        }
        
        $this->cart[$index]['quantity'] = $quantity;
        $this->cart[$index]['subtotal'] = $quantity * $this->cart[$index]['price'];
    }
    
    public function completeSale()
    {
        if (empty($this->cart)) {
            session()->flash('error', 'Cart is empty!');
            return;
        }
        
        DB::beginTransaction();
        
        try {
            // Generate invoice number
            $lastSale = Sale::whereDate('created_at', today())->latest()->first();
            $invoiceNo = 'INV-' . date('Ymd') . '-' . str_pad(($lastSale ? $lastSale->id + 1 : 1), 4, '0', STR_PAD_LEFT);
            
            // Calculate total
            $totalAmount = array_sum(array_column($this->cart, 'subtotal'));
            
            // Create sale
            $sale = Sale::create([
                'branch_id' => $this->branch_id,
                'user_id' => Auth::id(),
                'invoice_no' => $invoiceNo,
                'sale_date' => now(),
                'total_amount' => $totalAmount,
            ]);
            
            // Create sale items and reduce stock
            foreach ($this->cart as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
                
                // Reduce stock
                $stock = Stock::where('branch_id', $this->branch_id)
                             ->where('product_id', $item['product_id'])
                             ->first();
                
                if ($stock) {
                    $stock->decrement('quantity', $item['quantity']);
                }
            }
            
            DB::commit();
            
            session()->flash('message', 'Sale completed successfully! Invoice: ' . $invoiceNo);
            $this->reset(['cart', 'product_id', 'quantity']);
            $this->quantity = 1;
            
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to complete sale: ' . $e->getMessage());
        }
    }
    
    #[Computed]
    public function getAvailableProductsProperty()
    {
        if (!$this->branch_id) {
            return collect();
        }
        
        return Stock::with(['product.category'])
            ->where('branch_id', $this->branch_id)
            ->where('quantity', '>', 0)
            ->get()
            ->map(function($stock) {
                return [
                    'id' => $stock->product_id,
                    'name' => $stock->product->name,
                    'category' => $stock->product->category->name,
                    'available' => $stock->quantity,
                    'price' => $stock->sell_price,
                ];
            });
    }
    
    #[Computed]
    public function getSalesProperty()
    {
        $query = Sale::with(['items.product', 'user', 'branch'])
                    ->where('branch_id', $this->branch_id);
        
        if ($this->search) {
            $query->where('invoice_no', 'like', '%' . $this->search . '%');
        }
        
        return $query->latest()->limit(50)->get();
    }
    
    public function getCartTotal()
    {
        return array_sum(array_column($this->cart, 'subtotal'));
    }
    
    public function exportPDF()
    {
        $sales = $this->getSalesProperty();
        
        // Prepare data for PDF
        $salesData = [];
        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                $salesData[] = [
                    'product' => $item->product->name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $item->quantity * $item->price,
                    'seller' => $sale->user->name,
                    'date' => $sale->sale_date->format('M d, Y H:i'),
                ];
            }
        }
        
        $pdf = Pdf::loadView('exports.sales-pdf', [
            'salesData' => $salesData,
            'branch' => Auth::user()->branch->name ?? 'All Branches',
            'exportDate' => now()->format('M d, Y H:i'),
        ]);
        
        return response()->streamDownload(function() use ($pdf) {
            echo $pdf->output();
        }, 'sales-report-' . date('Ymd-His') . '.pdf');
    }
    
    public function exportExcel()
    {
        $sales = $this->getSalesProperty();
        
        // Prepare data for Excel
        $salesData = collect();
        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                $salesData->push([
                    'product' => $item->product->name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $item->quantity * $item->price,
                    'seller' => $sale->user->name,
                    'date' => $sale->sale_date->format('M d, Y H:i'),
                ]);
            }
        }
        
        return Excel::download(new class($salesData) implements FromCollection, WithHeadings, WithMapping {
            private $salesData;
            
            public function __construct($salesData)
            {
                $this->salesData = $salesData;
            }
            
            public function collection()
            {
                return $this->salesData;
            }
            
            public function headings(): array
            {
                return ['Product', 'Quantity', 'Price', 'Subtotal', 'Seller', 'Date'];
            }
            
            public function map($row): array
            {
                return [
                    $row['product'],
                    $row['quantity'],
                    'Tsh ' . number_format($row['price'], 2),
                    'Tsh ' . number_format($row['subtotal'], 2),
                    $row['seller'],
                    $row['date'],
                ];
            }
        }, 'sales-report-' . date('Ymd-His') . '.xlsx');
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
    
    <!-- Left: Product Selection -->
    <div class="lg:col-span-2 order-2 lg:order-1">
        <div class="bg-white dark:bg-gray-800 shadow-lg sm:rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 border-t-4 border-t-primary transition-shadow hover:shadow-xl">
            <div class="p-4 sm:p-5 lg:p-6">
                <!-- Header with Badge -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4 sm:mb-5">
                    <h3 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-6 h-6 sm:w-7 sm:h-7 mr-2 text-primary-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"></path>
                        </svg>
                        <span class="hidden sm:inline">New Sale</span>
                        <span class="sm:hidden">Sale</span>
                    </h3>
                    
                    <!-- Branch Badge -->
                    <div class="inline-flex items-center px-3 py-1.5 bg-blue-100 dark:bg-blue-900 rounded-full">
                        <svg class="w-4 h-4 mr-1.5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                        </svg>
                        <span class="text-xs sm:text-sm font-semibold text-blue-900 dark:text-blue-300 truncate max-w-[150px] sm:max-w-none">
                            {{ Auth::user()->branch->name ?? 'N/A' }}
                        </span>
                    </div>
                </div>

                <!-- Product Selection Form -->
                <form wire:submit.prevent="addToCart" class="space-y-4 sm:space-y-5">
                    <div>
                        <label class="block mb-2 text-sm sm:text-base font-medium text-gray-900 dark:text-white">Select Product</label>
                        <select wire:model="product_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-xs sm:text-sm rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 sm:p-3 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white transition-colors">
                            <option value="">Choose a product</option>
                            @foreach($this->availableProducts as $product)
                                <option value="{{ $product['id'] }}">
                                    {{ $product['name'] }} - {{ $product['category'] }} ({{ $product['available'] }}) - Tsh {{ number_format($product['price'], 0) }}
                                </option>
                            @endforeach
                        </select>
                        @error('product_id') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block mb-2 text-sm sm:text-base font-medium text-gray-900 dark:text-white">Quantity</label>
                        <input type="number" wire:model="quantity" min="1" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 sm:p-3 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white transition-colors" placeholder="Enter quantity">
                        @error('quantity') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <button type="submit" class="w-full text-white bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-semibold rounded-lg text-sm sm:text-base px-5 py-3 sm:py-3.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 transition-all transform active:scale-95">
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
        <div class="bg-white dark:bg-gray-800 shadow-lg sm:rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 border-t-4 border-t-green-600 lg:sticky lg:top-4 transition-shadow hover:shadow-xl">
            <div class="p-4 sm:p-5 lg:p-6">
                <!-- Cart Header -->
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-6 h-6 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"></path>
                        </svg>
                        Cart
                    </h3>
                    <span class="inline-flex items-center justify-center w-7 h-7 sm:w-8 sm:h-8 text-xs sm:text-sm font-bold text-white bg-green-600 rounded-full">
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
                                    </div>
                                    <button wire:click="removeFromCart({{ $index }})" class="flex-shrink-0 p-1.5 text-red-600 hover:text-white hover:bg-red-600 dark:text-red-400 dark:hover:bg-red-500 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                        </svg>
                                    </button>
                                </div>
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                    <div class="flex items-center space-x-2">
                                        <input type="number" wire:change="updateCartQuantity({{ $index }}, $event.target.value)" value="{{ $item['quantity'] }}" min="1" class="w-16 sm:w-20 text-sm p-1.5 sm:p-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-primary-500">
                                        <span class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">× Tsh {{ number_format($item['price'], 0) }}</span>
                                    </div>
                                    <span class="text-sm sm:text-base font-bold text-gray-900 dark:text-white whitespace-nowrap">Tsh {{ number_format($item['subtotal'], 0) }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <div class="border-t-2 border-gray-300 dark:border-gray-600 pt-4">
                        <div class="flex justify-between items-center mb-4 sm:mb-5">
                            <span class="text-base sm:text-lg font-bold text-gray-900 dark:text-white">Total:</span>
                            <span class="text-xl sm:text-2xl lg:text-3xl font-bold text-green-600 dark:text-green-400">Tsh {{ number_format($this->getCartTotal(), 0) }}</span>
                        </div>
                        
                        <button wire:click="completeSale" class="w-full text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:outline-none focus:ring-green-300 font-bold rounded-lg text-sm sm:text-base px-5 py-3 sm:py-4 text-center dark:bg-green-500 dark:hover:bg-green-600 shadow-lg hover:shadow-xl transition-all transform active:scale-95">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2 inline" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            Complete Sale
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Sales History -->
<div class="mt-4 sm:mt-6 lg:mt-8">
    <div class="bg-white dark:bg-gray-800 shadow-lg sm:rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 border-t-4 border-t-primary">
        <div class="flex flex-col space-y-3 p-4 sm:p-5 lg:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center justify-between w-full">
                    <h3 class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-6 h-6 mr-2 text-primary-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path>
                        </svg>
                        Recent Sales
                    </h3>
                    
                    <!-- Export Buttons -->
                    <div class="flex gap-2">
                        <button wire:click="exportPDF" class="inline-flex items-center px-3 py-2 text-xs sm:text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:ring-4 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 transition-colors">
                            <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="hidden sm:inline">PDF</span>
                        </button>
                        <button wire:click="exportExcel" class="inline-flex items-center px-3 py-2 text-xs sm:text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 focus:ring-4 focus:ring-green-300 dark:bg-green-600 dark:hover:bg-green-700 transition-colors">
                            <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="hidden sm:inline">Excel</span>
                        </button>
                    </div>
                </div>
                
                <!-- Search Box -->
                <div class="w-full sm:w-auto sm:min-w-[250px] lg:min-w-[300px]">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input type="text" wire:model.live="search" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2.5 sm:p-3 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white transition-colors" placeholder="Search invoice...">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Mobile Card View -->
        <div class="block lg:hidden divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($this->sales as $sale)
                <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors" wire:key="sale-mobile-{{$sale->id}}">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <div class="font-bold text-gray-900 dark:text-white text-base">{{ $sale->invoice_no }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $sale->sale_date->format('M d, Y H:i') }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold text-green-600 dark:text-green-400">Tsh {{ number_format($sale->total_amount, 0) }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $sale->items->count() }} item(s)</div>
                        </div>
                    </div>
                    <div class="flex items-center mt-3 pt-3 border-t border-gray-200 dark:border-gray-600">
                        <svg class="w-4 h-4 mr-1.5 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $sale->user->name }}</span>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center">
                    <svg class="w-16 h-16 mx-auto mb-3 text-gray-400 opacity-50" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400 text-sm font-medium">No sales yet</p>
                    <p class="text-gray-400 dark:text-gray-500 text-xs mt-1">Start making sales to see them here</p>
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
                        <th scope="col" class="px-6 py-4 font-semibold">Items</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Total</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Sold By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($this->sales as $sale)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors" wire:key="sale-{{$sale->id}}">
                        <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">{{ $sale->invoice_no }}</td>
                        <td class="px-6 py-4 text-gray-700 dark:text-gray-300">{{ $sale->sale_date->format('M d, Y H:i') }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                {{ $sale->items->count() }} item(s)
                            </span>
                        </td>
                        <td class="px-6 py-4 font-bold text-green-600 dark:text-green-400">Tsh {{ number_format($sale->total_amount, 0) }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 w-8 h-8 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center mr-2">
                                    <span class="text-xs font-semibold text-primary-700 dark:text-primary-300">{{ substr($sale->user->name, 0, 1) }}</span>
                                </div>
                                <span class="text-gray-900 dark:text-white font-medium">{{ $sale->user->name }}</span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <svg class="w-16 h-16 mx-auto mb-3 text-gray-400 opacity-50" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path>
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400 font-medium">No sales yet</p>
                            <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Start making sales to see them here</p>
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