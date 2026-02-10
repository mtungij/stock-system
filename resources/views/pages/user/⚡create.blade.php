<?php

use Livewire\Component;
use App\Models\User;
use App\Models\Branch;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

new class extends Component
{
    public $name;
    public $email;
    public $phone;
    public $branch_id;
    public $role_id;
    public $password;
    public $password_confirmation;
    public $editingUserId = null;
    public $search = '';

    public $branches;
    public $roles;

    public function mount()
    {
        $user = Auth::user();
        
        // If Admin without branch, show all branches
        if ($user->role && $user->role->role_name === 'Admin' && !$user->branch_id) {
            $this->branches = Branch::all();
        } elseif ($user->branch_id) {
            // If Sales Person, only show their branch
            if ($user->role && $user->role->role_name === 'Sales Person') {
                $this->branches = Branch::where('id', $user->branch_id)->get();
            } else {
                // Company admin sees all branches in company
                $companyId = $user->branch->company_id;
                $this->branches = Branch::where('company_id', $companyId)->get();
            }
        } else {
            $this->branches = Branch::all();
        }
        
        $this->roles = Role::all();
    }

    protected function rules()
    {
        // Get the selected role to determine branch requirement
        $selectedRole = Role::find($this->role_id);
        $isSalesPerson = $selectedRole && $selectedRole->role_name === 'Sales Person';
        
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email' . ($this->editingUserId ? ',' . $this->editingUserId : ''),
            'phone' => 'nullable|string|max:20',
            'branch_id' => $isSalesPerson ? 'required|exists:branches,id' : 'nullable|exists:branches,id',
            'role_id' => 'required|exists:roles,id',
        ];

        if (!$this->editingUserId) {
            $rules['password'] = 'required|string|min:8|confirmed';
        } elseif ($this->password) {
            $rules['password'] = 'string|min:8|confirmed';
        }

        return $rules;
    }

    public function save()
    {
        if ($this->editingUserId) {
            $this->update();
            return;
        }

        $this->validate();

        // For Sales Person, force their branch_id
        $user = Auth::user();
        if ($user->role && $user->role->role_name === 'Sales Person') {
            $this->branch_id = $user->branch_id;
        }

        User::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'branch_id' => $this->branch_id,
            'role_id' => $this->role_id,
            'password' => Hash::make($this->password),
        ]);

        session()->flash('message', 'User created successfully!');
        $this->reset(['name', 'email', 'phone', 'branch_id', 'role_id', 'password', 'password_confirmation']);
        $this->dispatch('close-modal');
    }

    public function edit($userId)
    {
        $user = User::findOrFail($userId);
        
        $this->editingUserId = $userId;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone;
        $this->branch_id = $user->branch_id;
        $this->role_id = $user->role_id;
        $this->password = '';
        $this->password_confirmation = '';
    }

    public function update()
    {
        $this->validate();
        
        $user = User::findOrFail($this->editingUserId);
        
        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'branch_id' => $this->branch_id,
            'role_id' => $this->role_id,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }
        
        $user->update($data);
        
        session()->flash('message', 'User updated successfully!');
        $this->reset(['editingUserId', 'name', 'email', 'phone', 'branch_id', 'role_id', 'password', 'password_confirmation']);
        $this->dispatch('close-modal');
    }

    public function delete($userId)
    {
        $user = User::findOrFail($userId);
        $user->delete();
        
        session()->flash('message', 'User deleted successfully.');
    }

    public function resetForm()
    {
        $this->reset(['editingUserId', 'name', 'email', 'phone', 'branch_id', 'role_id', 'password', 'password_confirmation']);
        $this->resetValidation();
    }

    #[Computed]
    public function getUsersProperty()
    {
        $user = Auth::user();
        
        $query = User::with(['branch', 'role']);

        if ($user) {
            // If Admin without branch, show all users
            if ($user->role && $user->role->role_name === 'Admin' && !$user->branch_id) {
                // Super admin sees all users
            } elseif ($user->branch_id) {
                // If Sales Person, only show users from their branch
                if ($user->role && $user->role->role_name === 'Sales Person') {
                    $query->where('branch_id', $user->branch_id);
                } else {
                    // Company admin sees all users in company
                    $companyId = $user->branch->company_id;
                    $query->whereHas('branch', fn($q) => $q->where('company_id', $companyId));
                }
            }
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('phone', 'like', '%' . $this->search . '%');
            });
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
                            <input type="text" wire:model.live="search" id="simple-search" class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Search users" required="">
                        </div>
                    </form>
                </div>
                <div class="w-full md:w-auto flex flex-col md:flex-row space-y-2 md:space-y-0 items-stretch md:items-center justify-end md:space-x-3 shrink-0">
                    <button id="defaultModalButton" wire:click="resetForm" data-modal-target="defaultModal" data-modal-toggle="defaultModal" class="flex items-center justify-center text-white bg-primary hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary dark:hover:bg-primary-700 dark:focus:ring-primary-800" type="button">
                        <svg class="h-3.5 w-3.5 mr-2" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path clip-rule="evenodd" fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" />
                        </svg>
                        Add User
                    </button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-4 py-3">Name</th>
                            <th scope="col" class="px-4 py-3">Email</th>
                            <th scope="col" class="px-4 py-3">Phone</th>
                            <th scope="col" class="px-4 py-3">Branch</th>
                            <th scope="col" class="px-4 py-3">Role</th>
                            <th scope="col" class="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->users as $user)
                        <tr class="border-b dark:border-gray-700" wire:key="user-{{$user->id}}">
                            <th scope="row" class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white">{{$user->name}}</th>
                            <td class="px-4 py-3">{{$user->email}}</td>
                            <td class="px-4 py-3">{{$user->phone ?? '-'}}</td>
                            <td class="px-4 py-3">{{$user->branch->name ?? '-'}}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                    {{$user->role->role_name ?? '-'}}
                                </span>
                            </td>
                            <td class="px-4 py-3 flex items-center">
                                <button
                                    wire:click="edit({{ $user->id }})"
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
                                    wire:click="delete({{ $user->id }})"
                                    wire:confirm="Are you sure you want to delete this user?"
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
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                @if($search)
                                    No users found matching "{{ $search }}"
                                @else
                                    No users available yet.
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
            <div class="relative p-4 bg-white rounded-lg shadow dark:bg-gray-800 sm:p-5">
                <!-- Modal header -->
                <div class="flex justify-between items-center pb-4 mb-4 rounded-t border-b sm:mb-5 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $editingUserId ? 'Edit User' : 'Add User' }}
                    </h3>
                    <button wire:click="resetForm" type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-toggle="defaultModal" data-modal-hide="defaultModal">
                        <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <!-- Modal body -->
                <form wire:submit.prevent="save">
                    <div class="grid gap-4 mb-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Name</label>
                            <input type="text" wire:model="name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Full name">
                            @error('name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Email</label>
                            <input type="email" wire:model="email" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="user@example.com">
                            @error('email') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Phone</label>
                            <input type="text" wire:model="phone" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="+1234567890">
                            @error('phone') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Branch <span class="text-xs font-normal text-gray-500">(optional for Admin)</span></label>
                            <select wire:model="branch_id" @if(Auth::user()->role && Auth::user()->role->role_name === 'Sales Person') disabled @endif class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                <option value="">Select Branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            @error('branch_id') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Role</label>
                            <select wire:model="role_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                <option value="">Select Role</option>
                                @foreach($roles as $role)
                                    @if(Auth::user()->role && Auth::user()->role->role_name === 'Sales Person')
                                        @if($role->role_name === 'Sales Person')
                                            <option value="{{ $role->id }}">{{ $role->role_name }}</option>
                                        @endif
                                    @else
                                        <option value="{{ $role->id }}">{{ $role->role_name }}</option>
                                    @endif
                                @endforeach
                            </select>
                            @error('role_id') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Password {{ $editingUserId ? '(leave blank to keep current)' : '' }}</label>
                            <input type="password" wire:model="password" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="••••••••">
                            @error('password') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Confirm Password</label>
                            <input type="password" wire:model="password_confirmation" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="••••••••">
                        </div>
                    </div>
                    <button type="submit" class="text-white inline-flex items-center bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
                        <svg class="mr-1 -ml-1 w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path></svg>
                        {{ $editingUserId ? 'Update User' : 'Add User' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
  
</div>

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('close-modal', () => {
            const modal = document.getElementById('defaultModal');
            if (modal) {
                const backdrop = document.querySelector('[modal-backdrop]');
                modal.classList.add('hidden');
                if (backdrop) backdrop.remove();
            }
        });
    });
</script>