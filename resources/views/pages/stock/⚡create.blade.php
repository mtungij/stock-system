<?php

use App\Models\Branch;
use Livewire\Component;
use App\Models\Category;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
new class extends Component
{

   public $branch_id;
    public $category_id;
    public $name;
    public $unit;
    public $min_stock;
    public $buy_price;
    public $sell_price;
    public $quantity;

    public $branches;
    public $categories;
    


     public $search = '';
     public $filterBranch = '';

   
    public function mount()
    {
        $user = Auth::user();

        if ($user) {
            // If Admin (no branch), show all branches and categories
            if ($user->role && $user->role->role_name === 'Admin' && !$user->branch_id) {
                $this->branches = Branch::all();
                $this->categories = Category::all();
            } elseif ($user->branch) {
                $companyId = $user->branch->company_id;

                // If Sales Person, only show their branch
                if ($user->role && $user->role->role_name === 'Sales Person') {
                    $this->branches = Branch::where('id', $user->branch_id)->get();
                    $this->branch_id = $user->branch_id; // Pre-select their branch
                } else {
                    // Company-level admin sees all branches in company
                    $this->branches = Branch::where('company_id', $companyId)->get();
                }
                
                $this->categories = Category::where('company_id', $companyId)->get();
            } else {
                // fallback
                $this->branches = Branch::all();
                $this->categories = Category::all();
            }
        }
    }
    



    protected $rules = [
        'branch_id' => 'required|exists:branches,id',
        'category_id' => 'required|exists:categories,id',
        'name' => 'required|string|max:255',
        'unit' => 'required|string|max:50',
        'min_stock' => 'required|integer|min:0',
        'buy_price' => 'required|numeric|min:0',
        'sell_price' => 'required|numeric|min:0',
        'quantity' => 'required|integer|min:0',
    ];

   public function save()
    {

       if(Stock::where('branch_id', $this->branch_id)
        ->whereHas('product', fn($q) => $q->where('name', $this->name))
        ->exists()) {
        $this->addError('name', 'Product already exists for this branch');
        $this->dispatch('show-toast', type: 'error', message: 'Product already exists for this branch');
        return;
    }
        $this->validate();

        // Step 1: Create product
        $product = Product::create([
            'name' => $this->name,
            'category_id' => $this->category_id,
            'unit' => $this->unit,
            'min_stock' => $this->min_stock,
        ]);

        // Step 2: Create stock for branch
        Stock::create([
            'product_id' => $product->id,
            'branch_id' => $this->branch_id,
            'buy_price' => $this->buy_price,
            'sell_price' => $this->sell_price,
            'quantity' => $this->quantity,
        ]);

        $this->dispatch('show-toast', type: 'success', message: 'Product registered successfully!');
        $this->dispatch('reload-page');

        // Clear form
        $this->reset(['branch_id','category_id','name','unit','min_stock','buy_price','sell_price','quantity']);
    }

    public function exportExcel()
    {
        $stocks = $this->getStocksProperty();
        
        $filename = 'stocks-' . date('Y-m-d') . '.xlsx';
        
        return \Maatwebsite\Excel\Facades\Excel::download(
            new class($stocks) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
                private $stocks;
                
                public function __construct($stocks)
                {
                    $this->stocks = $stocks;
                }
                
                public function collection()
                {
                    return $this->stocks->map(function($stock) {
                        return [
                            'Product' => $stock->product->name,
                            'Category' => $stock->product->category->name,
                            'Branch' => $stock->branch->name,
                            'Buy Price' => number_format($stock->buy_price, 2),
                            'Sell Price' => number_format($stock->sell_price, 2),
                            'Quantity' => $stock->quantity,
                        ];
                    });
                }
                
                public function headings(): array
                {
                    return ['Product', 'Category', 'Branch', 'Buy Price', 'Sell Price', 'Quantity'];
                }
            },
            $filename
        );
    }

 #[Computed] 
    public function getStocksProperty()
{
    $user = Auth::user();
    
    $query = Stock::with(['product', 'branch'])
        ->whereHas('product', function($q) {
            $q->where('name', 'like', '%' . $this->search . '%');
        });

    if ($user) {
        // If user is Sales Person, only show their branch
        if ($user->role && $user->role->role_name === 'Sales Person' && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($user->branch_id) {
            // Company-level admin sees all branches in their company
            $companyId = $user->branch->company_id;
            $query->whereHas('branch', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
            
            // Filter by branch if selected (company admin only)
            if ($this->filterBranch) {
                $query->where('branch_id', $this->filterBranch);
            }
        }
        // If Admin with no branch (super admin), show all stocks (no filter)
    }

    // Filter by branch if selected
    if ($this->filterBranch) {
        $query->where('branch_id', $this->filterBranch);
    }

    return $query->latest()->get();
}

};
?>

<div class="w-full"
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
            
            Livewire.on('reload-page', () => {
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            });
        }
    }">

<!-- Modal toggle -->


<!-- Main modal -->



    <div>
        <!-- Start coding here -->
        <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 border-t-4 border-primary">
            <div class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4 p-4">
                <div class="w-full">
                    <form class="flex items-center">
                        <label for="simple-search" class="sr-only">Search</label>
                        <div class="relative w-full">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <input type="text" wire:model.live="search" id="simple-search" class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Search" required="">
                        </div>
                    </form>
                </div>
                <div class="w-full md:w-auto flex flex-col md:flex-row space-y-2 md:space-y-0 items-stretch md:items-center justify-end md:space-x-3 flex-shrink-0">
                    
                    <!-- Branch Filter -->
                    <select wire:model.live="filterBranch" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 px-4 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{$branch->id}}">{{$branch->name}}</option>
                        @endforeach
                    </select>

                    <!-- Export Buttons -->
                    <a href="{{ route('stock.pdf', ['branch' => $filterBranch]) }}" target="_blank" class="flex items-center justify-center text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-red-700 dark:hover:bg-red-800 dark:focus:ring-red-800">
                        <svg class="h-4 w-4 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"/>
                        </svg>
                        PDF
                    </a>
                    
                    <button wire:click="exportExcel" class="flex items-center justify-center text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-green-700 dark:hover:bg-green-800 dark:focus:ring-green-800">
                        <svg class="h-4 w-4 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"/>
                        </svg>
                        Excel
                    </button>

                  <div class="flex justify-center m-5">
    <button id="defaultModalButton" data-modal-target="defaultModal" data-modal-toggle="defaultModal" class="block text-white bg-primary hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary dark:hover:bg-primary-700 dark:focus:ring-primary-800" type="button">
    Create Stock
    </button>
</div>
                    
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-primary-700 dark:text-primary-200 ">Product name</th>
                            <th scope="col" class="px-4 py-3 text-primary-700 dark:text-primary-200">Category</th>
                            <th scope="col" class="px-4 py-3 text-primary-700 dark:text-primary-200">Branch</th>
                            <th scope="col" class="px-4 py-3 text-primary-700 dark:text-primary-200">Buy Price</th>
                            <th scope="col" class="px-4 py-3 text-primary-700 dark:text-primary-200">Sell Price</th>
                               <th scope="col" class="px-4 py-3 text-primary-700 dark:text-primary-200">Quantity</th>
                            <th scope="col" class="px-4 py-3">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->stocks as $stock)
                        <tr class="border-b dark:border-gray-700" wire:key="stock-{{$stock->id}}">
                            <th scope="row" class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white uppercase">{{$stock->product->name}}</th>
                            <td class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white uppercase">{{$stock->product->category->name}}</td>
                            <td class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white uppercase">{{$stock->branch->name}}</td>
                            <td class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white">{{ number_format($stock->buy_price)}}</td>
                            <td class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white">{{ number_format($stock->sell_price)}}</td>
                             <td class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white">{{ number_format($stock->quantity)}}</td>
                            <!-- <td class="px-4 py-3 flex items-center ">
                             
    <button
    wire:click="edit({{ $stock->id }})"
    type="button"
    class="inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium
           text-white
           bg-blue-600 hover:bg-blue-700
           focus:ring-4 focus:ring-blue-300
           dark:bg-blue-500 dark:hover:bg-blue-600
           dark:focus:ring-blue-800"
>
    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
    </svg>
    Edit
</button>

<button
    wire:click="delete({{ $stock->id }})"
    wire:confirm="Are you sure you want to delete this stock?"
    type="button"
    class="ml-2 inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium
           text-white
           bg-red-600 hover:bg-red-700
           focus:ring-4 focus:ring-red-300
           dark:bg-red-500 dark:hover:bg-red-600
           dark:focus:ring-red-800"
>
    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
    </svg>
    Delete
</button>
</td> -->
                        </tr>
                        @empty
  <tr class="border-b dark:border-gray-700">
        <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
            @if($search)
                No stocks found matching "{{ $search }}"
            @else
                No stocks available yet.
            @endif
        </td>
    </tr>
   @endforelse
                    </tbody>
                </table>
            </div>
            <nav class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-3 md:space-y-0 p-4" aria-label="Table navigation">
                <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                    Showing
                    <span class="font-semibold text-gray-900 dark:text-white">1-10</span>
                    of
                    <span class="font-semibold text-gray-900 dark:text-white">1000</span>
                </span>
                <ul class="inline-flex items-stretch -space-x-px">
                    <li>
                        <a href="#" class="flex items-center justify-center h-full py-1.5 px-3 ml-0 text-gray-500 bg-white rounded-l-lg border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                            <span class="sr-only">Previous</span>
                            <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center justify-center text-sm py-2 px-3 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">1</a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center justify-center text-sm py-2 px-3 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">2</a>
                    </li>
                    <li>
                        <a href="#" aria-current="page" class="flex items-center justify-center text-sm z-10 py-2 px-3 leading-tight text-primary-600 bg-primary-50 border border-primary-300 hover:bg-primary-100 hover:text-primary-700 dark:border-gray-700 dark:bg-gray-700 dark:text-white">3</a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center justify-center text-sm py-2 px-3 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">...</a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center justify-center text-sm py-2 px-3 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">100</a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center justify-center h-full py-1.5 px-3 leading-tight text-gray-500 bg-white rounded-r-lg border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                            <span class="sr-only">Next</span>
                            <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>

    <div id="defaultModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-modal md:h-full">
    <div class="relative p-4 w-full max-w-2xl h-full md:h-auto">
        <!-- Modal content -->
        <div class="relative p-4 bg-white rounded-lg shadow dark:bg-gray-800 sm:p-5">
            <!-- Modal header -->
            <div class="flex justify-between items-center pb-4 mb-4 rounded-t border-b sm:mb-5 dark:border-gray-600">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Add Stock
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-toggle="defaultModal">
                    <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            <!-- Modal body -->
            <form wire:submit.prevent="save">
                <div class="grid gap-4 mb-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Branch</label>
                        <select wire:model="branch_id" @if(Auth::user()->role && Auth::user()->role->role_name === 'Sales Person') disabled @endif class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                            <option value="">Select Branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Category</label>
                        <select wire:model="category_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Product Name</label>
                        <input type="text" wire:model="name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Product name">
                        @error('name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Unit</label>
                        <input type="text" wire:model="unit" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Unit (e.g. pcs, kg)">
                        @error('unit') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Minimum Stock</label>
                        <input type="number" wire:model="min_stock" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="0">
                        @error('min_stock') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Buy Price</label>
                        <input type="number" wire:model="buy_price" step="0.01" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="0">
                        @error('buy_price') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Sell Price</label>
                        <input type="number" wire:model="sell_price" step="0.01" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="0">
                        @error('sell_price') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Quantity</label>
                        <input type="number" wire:model="quantity" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="0">
                        @error('quantity') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>
                <button type="submit" class="text-white inline-flex items-center bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
                    <svg class="mr-1 -ml-1 w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path></svg>
                    Add stock
                </button>
            </form>



            
        </div>
    </div>
</div>
  
</div>

