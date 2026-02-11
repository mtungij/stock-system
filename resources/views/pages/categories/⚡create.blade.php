<?php

use Livewire\Component;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

new class extends Component
{
    public $name;
    public $editingCategoryId = null;
    public $search = '';

    protected $rules = [
        'name' => 'required|string|max:255',
    ];

    public function save()
    {
        if ($this->editingCategoryId) {
            $this->update();
            return;
        }

        $this->validate();

        $user = Auth::user();
        $companyId = $user && $user->branch ? $user->branch->company_id : null;

        // Check for duplicate
        if (Category::where('company_id', $companyId)->where('name', $this->name)->exists()) {
            $this->addError('name', 'Category already exists');
            return;
        }

        Category::create([
            'name' => $this->name,
            'company_id' => $companyId,
        ]);

        session()->flash('message', 'Category created successfully!');
        $this->reset(['name']);
        $this->dispatch('close-modal');
    }

    public function edit($categoryId)
    {
        $category = Category::findOrFail($categoryId);
        
        $this->editingCategoryId = $categoryId;
        $this->name = $category->name;
        // Description removed
    }

    public function update()
    {
        $this->validate();
        
        $category = Category::findOrFail($this->editingCategoryId);
        $user = Auth::user();
        $companyId = $user && $user->branch ? $user->branch->company_id : null;

        // Check for duplicate (excluding current)
        if (Category::where('company_id', $companyId)
            ->where('name', $this->name)
            ->where('id', '!=', $this->editingCategoryId)
            ->exists()) {
            $this->addError('name', 'Category already exists');
            return;
        }
        
        $category->update([
            'name' => $this->name,
        ]);
        
        session()->flash('message', 'Category updated successfully!');
        $this->reset(['editingCategoryId', 'name']);
        $this->dispatch('close-modal');
    }

    public function delete($categoryId)
    {
        $category = Category::findOrFail($categoryId);
        $category->delete();
        
        session()->flash('message', 'Category deleted successfully.');
    }

    public function resetForm()
    {
        $this->reset(['editingCategoryId', 'name']);
        $this->resetValidation();
    }

    #[Computed]
    public function getCategoriesProperty()
    {
        $user = Auth::user();
        
        $query = Category::query();

        // If admin without branch, show all categories
        if ($user && $user->role && $user->role->role_name === 'Admin' && !$user->branch_id) {
            // Super admin sees all
        } elseif ($user && $user->branch) {
            $companyId = $user->branch->company_id;
            $query->where('company_id', $companyId);
        }

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        return $query->latest()->get();
    }
};
?>

<div class="w-full">

<!-- Success Message -->
@if (session()->has('message'))
    <div class="mb-4 p-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400" role="alert">
        <span class="font-medium">Success!</span> {{ session('message') }}
    </div>
@endif

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
                            <input type="text" wire:model.live="search" id="simple-search" class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Search categories" required="">
                        </div>
                    </form>
                </div>
                <div class="w-full md:w-auto flex flex-col md:flex-row space-y-2 md:space-y-0 items-stretch md:items-center justify-end md:space-x-3 flex-shrink-0">
                    <button id="defaultModalButton" wire:click="resetForm" data-modal-target="defaultModal" data-modal-toggle="defaultModal" class="flex items-center justify-center text-white bg-primary hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary dark:hover:bg-primary-700 dark:focus:ring-primary-800" type="button">
                        <svg class="h-3.5 w-3.5 mr-2" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path clip-rule="evenodd" fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" />
                        </svg>
                        Add Category
                    </button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-primary-700 dark:text-primary-200">Category Name</th>
                            <!-- Description column removed -->
                            <th scope="col" class="px-4 py-3 text-primary-700 dark:text-primary-200">Created</th>
                            <th scope="col" class="px-4 py-3">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->categories as $category)
                        <tr class="border-b dark:border-gray-700" wire:key="category-{{$category->id}}">
                            <th scope="row" class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white">{{$category->name}}</th>
                            <!-- Description cell removed -->
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{$category->created_at->format('M d, Y')}}</td>
                            <td class="px-4 py-3 flex items-center">
                                <button
                                    wire:click="edit({{ $category->id }})" 
                                    data-modal-target="defaultModal"
                                    data-modal-toggle="defaultModal"
                                    type="button"
                                    class="inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 dark:bg-blue-500 dark:hover:bg-blue-600 dark:focus:ring-blue-800"
                                >
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                    </svg>
                                    Edit
                                </button>

                                <button
                                    wire:click="delete({{ $category->id }})"
                                    wire:confirm="Are you sure you want to delete this category?"
                                    type="button"
                                    class="ml-2 inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:ring-red-300 dark:bg-red-500 dark:hover:bg-red-600 dark:focus:ring-red-800"
                                >
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    Delete
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr class="border-b dark:border-gray-700">
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                @if($search)
                                    No categories found matching "{{ $search }}"
                                @else
                                    No categories available yet.
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="defaultModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-modal md:h-full" wire:ignore.self>
        <div class="relative p-4 w-full max-w-2xl h-full md:h-auto">
            <!-- Modal content -->
            <div class="relative p-4 bg-white rounded-lg shadow dark:bg-gray-800 sm:p-5 max-h-[90vh] overflow-y-auto">
                <!-- Modal header -->
                <div class="flex justify-between items-center pb-4 mb-4 rounded-t border-b sm:mb-5 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $editingCategoryId ? 'Edit Category' : 'Add Category' }}
                    </h3>
                    <button type="button" wire:click="resetForm" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-toggle="defaultModal" data-modal-hide="defaultModal">
                        <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <!-- Modal body -->
                <form wire:submit.prevent="save">
                    <div class="grid gap-4 mb-4">
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Category Name</label>
                            <input type="text" wire:model="name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Enter category name">
                            @error('name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <!-- Description input removed -->
                    </div>
                    <button type="submit" class="text-white inline-flex items-center bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
                        <svg class="mr-1 -ml-1 w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path></svg>
                        {{ $editingCategoryId ? 'Update Category' : 'Add Category' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
  
</div>

<script>
    // Close modal event listener
    document.addEventListener('livewire:init', () => {
        Livewire.on('close-modal', () => {
            const closeButton = document.querySelector('[data-modal-hide="defaultModal"]');
            if (closeButton) closeButton.click();
        });
    });
</script>
