<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
   <body class="min-h-screen bg-white dark:bg-zinc-800">
      <header class="sticky top-0 z-50 w-full border-b border-default bg-white/90 backdrop-blur dark:border-default dark:bg-zinc-900/90 lg:pl-64">
         <div class="mx-auto flex h-14 max-w-screen-2xl items-center gap-3 px-3 sm:px-4 lg:px-6">
            <button data-drawer-target="separator-sidebar" data-drawer-toggle="separator-sidebar" aria-controls="separator-sidebar" type="button" class="text-heading bg-transparent box-border border border-transparent hover:bg-neutral-secondary-medium focus:ring-4 focus:ring-neutral-tertiary font-medium leading-5 rounded-base text-sm p-2 focus:outline-none inline-flex sm:hidden">
               <span class="sr-only">Open sidebar</span>
               <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M5 7h14M5 12h14M5 17h10"/>
               </svg>
            </button>

          

            <div class="ml-auto flex items-center gap-2">
               <div class="hidden items-center gap-2 sm:flex">
                  <button type="button" class="relative inline-flex h-9 w-9 items-center justify-center rounded-base border border-default text-heading hover:bg-neutral-tertiary" aria-label="Notifications">
                  <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24">
                     <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M12 5a6 6 0 0 0-6 6v3l-2 2h16l-2-2v-3a6 6 0 0 0-6-6Zm0 14a2 2 0 0 0 2-2h-4a2 2 0 0 0 2 2Z"/>
                  </svg>
                  <span class="absolute -right-1 -top-1 inline-flex h-2.5 w-2.5 rounded-full bg-danger-soft border border-danger-subtle"></span>
                  </button>

                  <button type="button" class="relative inline-flex h-9 w-9 items-center justify-center rounded-base border border-default text-heading hover:bg-neutral-tertiary" aria-label="Messages">
                  <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24">
                     <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 6h14a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H9l-4 3v-3H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Z"/>
                  </svg>
                  <span class="absolute -right-1 -top-1 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-danger-soft px-1 text-[10px] font-semibold text-fg-danger-strong">3</span>
                  </button>
               </div>

               <flux:dropdown position="bottom" align="end" class="sm:hidden">
                  <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" aria-label="Open menu" />
                  <flux:menu>
                     <flux:menu.item icon="bell">Notifications</flux:menu.item>
                     <flux:menu.item icon="chat-bubble-left-right">Messages</flux:menu.item>
                     <flux:menu.separator />
                     <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                        {{ __('Settings') }}
                     </flux:menu.item>
                     <flux:menu.separator />
                     <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer">
                           {{ __('Log Out') }}
                        </flux:menu.item>
                     </form>
                  </flux:menu>
               </flux:dropdown>

               <flux:dropdown position="bottom" align="end">
                  <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

                  <flux:menu>
                     <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                           <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                              <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />
                              <div class="grid flex-1 text-start text-sm leading-tight">
                                 <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                 <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                              </div>
                           </div>
                        </div>
                     </flux:menu.radio.group>

                     <flux:menu.separator />

                     <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                           {{ __('Settings') }}
                        </flux:menu.item>
                     </flux:menu.radio.group>

                     <flux:menu.separator />

                     <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer" data-test="logout-button">
                           {{ __('Log Out') }}
                        </flux:menu.item>
                     </form>
                  </flux:menu>
               </flux:dropdown>
            </div>
         </div>
      </header>

<aside id="separator-sidebar" class="fixed top-14 left-0 z-40 w-64 h-[calc(100vh-3.5rem)] transition-transform -translate-x-full sm:top-0 sm:h-screen sm:translate-x-0" aria-label="Sidebar">
      <div class="h-full px-3 py-4 mt-10 overflow-y-auto border-e border-default flex flex-col transition-colors duration-300"
         :class="{'bg-white text-gray-900': !$store.darkMode, 'bg-zinc-900 text-zinc-100': $store.darkMode, 'shadow-lg': true}"
         x-data="{ tab: 'menu' }">
      <!-- Sidebar header (if any) remains at the top -->
      <!-- Move tab bar to be always visible below header -->
       <div class="mb-3 flex rounded-base p-1 w-full max-w-full mt-0"
          :class="{'bg-primary/10': !$store.darkMode, 'bg-primary/20': $store.darkMode}">
         <button type="button"
            @click="tab = 'menu'"
            :class="tab === 'menu' ? 'bg-primary text-white shadow-lg' : 'text-primary hover:bg-primary/20 hover:text-primary'"
            class="w-1/2 rounded-base px-2 py-2 text-base font-medium transition focus:outline-none flex-1 min-w-0 text-center flex items-center justify-center gap-2"
            style="min-width: 0;">
            <svg class="w-5 h-5 transition-colors duration-150" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            <span class="truncate">Menu</span>
         </button>
         <button type="button"
            @click="tab = 'reports'"
            :class="tab === 'reports' ? 'bg-primary text-white shadow-lg' : 'text-primary hover:bg-primary/20 hover:text-primary'"
            class="w-1/2 rounded-base px-2 py-2 text-base font-medium transition focus:outline-none flex-1 min-w-0 text-center flex items-center justify-center gap-2"
            style="min-width: 0;">
            <svg class="w-5 h-5 transition-colors duration-150" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 17v-6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v6m4 0v-4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v4"/></svg>
            <span class="truncate">Reports</span>
         </button>
      </div>

      <ul class="space-y-2 font-medium" x-show="tab === 'menu'">
         <!-- <li>
            @php $user = auth()->user(); @endphp
            <a href="{{ $user && $user->role && $user->role->role_name === 'Sales Person' ? route('pages.dashboard.saleperson-dashboard') : route('dashboard') }}"
               class="flex items-center px-2 py-1.5 text-body rounded-base group transition-colors duration-200"
               :class="request()->routeIs($user && $user->role && $user->role->role_name === 'Sales Person' ? 'pages.dashboard.saleperson-dashboard' : 'dashboard') ? 'bg-primary/10 text-primary font-semibold ring-2 ring-primary scale-105' : 'hover:bg-primary/10 hover:text-primary'"
               <svg class="shrink-0 w-5 h-5 transition duration-75 group-hover:text-primary" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M5 7h14M5 12h14M5 17h10"/>
               </svg>
               <span class="flex-1 ms-3 whitespace-nowrap">Dashboard</span>
               <span class="bg-neutral-secondary-medium border border-default-medium text-heading text-xs font-medium px-1.5 py-0.5 rounded-sm">@if(isset($dashboardBadge)){{$dashboardBadge}}@endif</span>
            </a>
         </li> -->

           <li>
            <a href="{{ url('/categories/create') }}"
               class="flex items-center px-2 py-1.5 text-body rounded-base group transition-colors duration-200"
               :class="request()->is('categories/create') ? 'bg-primary/10 text-primary font-semibold ring-2 ring-primary scale-105' : 'hover:bg-primary/10 hover:text-primary'"
               <svg class="shrink-0 w-5 h-5 transition duration-75 group-hover:text-primary" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10V6a3 3 0 0 1 3-3v0a3 3 0 0 1 3 3v4m3-2 .917 11.923A1 1 0 0 1 17.92 21H6.08a1 1 0 0 1-.997-1.077L6 8h12Z"/></svg>
               <span class="flex-1 ms-3 whitespace-nowrap">Categories</span>
            </a>
         </li>
         <li>
            <a href="{{ url('/branch/create') }}"
               class="flex items-center px-2 py-1.5 text-body rounded-base group transition-colors duration-200"
               :class="request()->is('branch/create') ? 'bg-primary/10 text-primary font-semibold ring-2 ring-primary scale-105' : 'hover:bg-primary/10 hover:text-primary'"
               <svg class="shrink-0 w-5 h-5 transition duration-75 group-hover:text-primary" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 4h12M6 4v16M6 4H5m13 0v16m0-16h1m-1 16H6m12 0h1M6 20H5M9 7h1v1H9V7Zm5 0h1v1h-1V7Zm-5 4h1v1H9v-1Zm5 0h1v1h-1v-1Zm-3 4h2a1 1 0 0 1 1 1v4h-4v-4a1 1 0 0 1 1-1Z"/></svg>
               <span class="flex-1 ms-3 whitespace-nowrap">Branch Register</span>
            </a>
         </li>
             <li>
            <button type="button" class="flex items-center w-full justify-between px-2 py-1.5 text-body rounded-base hover:bg-primary/10 hover:text-primary group" aria-controls="dropdown-example" data-collapse-toggle="dropdown-example">
                  <svg class="shrink-0 w-5 h-5 transition duration-75 group-hover:text-primary" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24">
                     <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4h1.5L9 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8.5-3h9.25L19 7H7.312"/></svg>
                  <span class="flex-1 ms-3 text-left rtl:text-right whitespace-nowrap">Stock</span>
                  <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24">
                     <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/></svg>
            </button>
            <ul id="dropdown-example" class="hidden py-2 space-y-2">
                  <li>
                     <a href="{{ url('/stock/create') }}" class="pl-10 flex items-center px-2 py-1.5 text-body rounded-base hover:bg-primary/10 hover:text-primary group">Register Stock</a>
                  </li>
                  <li>
                     <a href="{{ url('/supplier/create') }}" class="pl-10 flex items-center px-2 py-1.5 text-body rounded-base hover:bg-primary/10 hover:text-primary group">Suppliers</a>
                  </li>
                  <li>
                     <a href="{{ url('/purchase/create') }}" class="pl-10 flex items-center px-2 py-1.5 text-body rounded-base hover:bg-primary/10 hover:text-primary group">Purchase</a>
                  </li>
                  <li>
                     <a href="{{ url('/stock/adjust') }}" class="pl-10 flex items-center px-2 py-1.5 text-body rounded-base hover:bg-primary/10 hover:text-primary group">Stock Adjustment</a>
                  </li>
                
                  <li>
                     <a href="#" class="pl-10 flex items-center px-2 py-1.5 text-body rounded-base hover:bg-primary/10 hover:text-primary group">Invoice</a>
                  </li>
            </ul>
         </li>
         <li>
            <a href="{{ url('/sell') }}"
               class="flex items-center px-2 py-1.5 text-body rounded-base group transition-colors duration-200"
               :class="request()->is('sell') ? 'bg-primary/10 text-primary font-semibold ring-2 ring-primary scale-105' : 'hover:bg-primary/10 hover:text-primary'"
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
  <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 7.5h-.75A2.25 2.25 0 0 0 4.5 9.75v7.5a2.25 2.25 0 0 0 2.25 2.25h7.5a2.25 2.25 0 0 0 2.25-2.25v-7.5a2.25 2.25 0 0 0-2.25-2.25h-.75m0-3-3-3m0 0-3 3m3-3v11.25m6-2.25h.75a2.25 2.25 0 0 1 2.25 2.25v7.5a2.25 2.25 0 0 1-2.25 2.25h-7.5a2.25 2.25 0 0 1-2.25-2.25v-.75" />
</svg>

               <span class="flex-1 ms-3 whitespace-nowrap">Sell</span>
               <!-- <span class="inline-flex items-center justify-center w-4.5 h-4.5 ms-2 text-xs font-medium text-fg-danger-strong bg-danger-soft border border-danger-subtle rounded-full">2</span> -->
            </a>
         </li>
         
         <li>
            <a href="{{ url('/user/create') }}"
               class="flex items-center px-2 py-1.5 text-body rounded-base group transition-colors duration-200"
               :class="request()->is('user/create') ? 'bg-primary/10 text-primary font-semibold ring-2 ring-primary scale-105' : 'hover:bg-primary/10 hover:text-primary'"
               <svg class="shrink-0 w-5 h-5 transition duration-75 group-hover:text-primary" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 19h4a1 1 0 0 0 1-1v-1a3 3 0 0 0-3-3h-2m-2.236-4a3 3 0 1 0 0-4M3 18v-1a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1Zm8-10a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
               <span class="flex-1 ms-3 whitespace-nowrap">Users</span>
            </a>
         </li>
      </ul>

      <ul class="space-y-2 font-medium" x-show="tab === 'reports'" x-cloak>
         <li>
            <a href="{{ url('/reports/sales-report') }}"
               class="flex items-center px-2 py-1.5 text-body rounded-base group transition-colors duration-200"
               :class="request()->is('reports/sales-report') ? 'bg-primary/10 text-primary font-semibold ring-2 ring-primary scale-105' : 'hover:bg-primary/10 hover:text-primary'">Today Sales Report</a>
         </li>
         <li>
            <a href="{{ url('/reports/outstock') }}"
               class="flex items-center px-2 py-1.5 text-body rounded-base group transition-colors duration-200"
               :class="request()->is('reports/outstock') ? 'bg-primary/10 text-primary font-semibold ring-2 ring-primary scale-105' : 'hover:bg-primary/10 hover:text-primary'">Out of Stock</a>
         </li>
         <li>
            <a href="{{ url('/reports/fastmoving') }}"
               class="flex items-center px-2 py-1.5 text-body rounded-base group transition-colors duration-200"
               :class="request()->is('reports/fastmoving') ? 'bg-primary/10 text-primary font-semibold ring-2 ring-primary scale-105' : 'hover:bg-primary/10 hover:text-primary'">Fast Moving Products</a>
         </li>
         <li>
            <a href="{{ url('/reports/dead-stock') }}"
               class="flex items-center px-2 py-1.5 text-body rounded-base group transition-colors duration-200"
               :class="request()->is('reports/dead-stock') ? 'bg-primary/10 text-primary font-semibold ring-2 ring-primary scale-105' : 'hover:bg-primary/10 hover:text-primary'">Dead Stock</a>
         </li>
         <li>
            <a href="{{ url('/reports/branch-performance') }}"
               class="flex items-center px-2 py-1.5 text-body rounded-base group transition-colors duration-200"
               :class="request()->is('reports/branch-performance') ? 'bg-primary/10 text-primary font-semibold ring-2 ring-primary scale-105' : 'hover:bg-primary/10 hover:text-primary'">Branch Performance</a>
         </li>
      </ul>
    
   </div>
</aside>

      <main class="sm:ml-64">
         {{ $slot }}
      </main>

      @fluxScripts

    </body>
</html>
