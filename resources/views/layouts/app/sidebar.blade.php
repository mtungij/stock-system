<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
   <body class="min-h-screen bg-white dark:bg-zinc-800">
      <header class="sticky top-0 z-50 w-full border-b border-default bg-white/90 backdrop-blur dark:border-default dark:bg-zinc-900/90 sm:pl-64">
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
   <div class="h-full px-3 py-4 overflow-y-auto bg-neutral-primary-soft border-e border-default">
      <ul class="space-y-2 font-medium">
       <li>
            <a href="#" class="flex items-center px-2 py-1.5 text-body rounded-base hover:bg-primary/10 hover:text-primary group">
               <svg class="shrink-0 w-5 h-5 transition duration-75 group-hover:text-primary" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M5 7h14M5 12h14M5 17h10"/>
               </svg>
               <span class="flex-1 ms-3 whitespace-nowrap">Kanban</span>
  
            </a>
         </li>
     
         <li>
            <a href="{{ route('dashboard') }}" class="flex items-center px-2 py-1.5 text-body rounded-base hover:bg-primary/10 hover:text-primary group">
               <svg class="shrink-0 w-5 h-5 transition duration-75 group-hover:text-primary" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M5 7h14M5 12h14M5 17h10"/>
               </svg>
               <span class="flex-1 ms-3 whitespace-nowrap">Dashboard</span>
               <span class="bg-neutral-secondary-medium border border-default-medium text-heading text-xs font-medium px-1.5 py-0.5 rounded-sm"></span>
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
                     <a href="{{ url('/sell') }}" class="pl-10 flex items-center px-2 py-1.5 text-body rounded-base hover:bg-primary/10 hover:text-primary group">Sales</a>
                  </li>
                  <li>
                     <a href="#" class="pl-10 flex items-center px-2 py-1.5 text-body rounded-base hover:bg-primary/10 hover:text-primary group">Invoice</a>
                  </li>
            </ul>
         </li>
         <li>
            <a href="#" class="flex items-center px-2 py-1.5 text-body rounded-base hover:bg-primary/10 hover:text-primary group">
               <svg class="shrink-0 w-5 h-5 transition duration-75 group-hover:text-primary" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 13h3.439a.991.991 0 0 1 .908.6 3.978 3.978 0 0 0 7.306 0 .99.99 0 0 1 .908-.6H20M4 13v6a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-6M4 13l2-9h12l2 9M9 7h6m-7 3h8"/></svg>
               <span class="flex-1 ms-3 whitespace-nowrap">Inbox</span>
               <span class="inline-flex items-center justify-center w-4.5 h-4.5 ms-2 text-xs font-medium text-fg-danger-strong bg-danger-soft border border-danger-subtle rounded-full">2</span>
            </a>
         </li>
         
         <!-- Reports Dropdown -->
         <li>
            <button type="button" class="flex items-center w-full justify-between px-2 py-1.5 text-body rounded-base hover:bg-primary/10 hover:text-primary group" aria-controls="dropdown-reports" data-collapse-toggle="dropdown-reports">
                  <svg class="shrink-0 w-5 h-5 transition duration-75 group-hover:text-primary" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                     <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15v4m6-6v6m6-4v4m6-6v6M3 11l6-5 6 5 5.5-5.5"/>
                  </svg>
                  <span class="flex-1 ms-3 text-left rtl:text-right whitespace-nowrap">Reports</span>
                  <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24">
                     <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/></svg>
            </button>
            <ul id="dropdown-reports" class="hidden py-2 space-y-2">
                  <li>
                     <a href="{{ url('/reports/sales-report') }}" class="pl-10 flex items-center px-2 py-1.5 text-body rounded-base hover:bg-primary/10 hover:text-primary group">Today Sales Report</a>
                  </li>
                  <li>
                     <a href="{{ url('/reports/outstock') }}" class="pl-10 flex items-center px-2 py-1.5 text-body rounded-base hover:bg-primary/10 hover:text-primary group">Out of Stock</a>
                  </li>
                  <li>
                     <a href="{{ url('/reports/branch-performance') }}" class="pl-10 flex items-center px-2 py-1.5 text-body rounded-base hover:bg-primary/10 hover:text-primary group">Branch Performance</a>
                  </li>
            </ul>
         </li>
         
         <li>
            <a href="{{ url('/user/create') }}" class="flex items-center px-2 py-1.5 text-body rounded-base hover:bg-primary/10 hover:text-primary group">
               <svg class="shrink-0 w-5 h-5 transition duration-75 group-hover:text-primary" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 19h4a1 1 0 0 0 1-1v-1a3 3 0 0 0-3-3h-2m-2.236-4a3 3 0 1 0 0-4M3 18v-1a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1Zm8-10a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
               <span class="flex-1 ms-3 whitespace-nowrap">Users</span>
            </a>
         </li>
         <li>
            <a href="{{ url('/categories/create') }}" class="flex items-center px-2 py-1.5 text-body rounded-base hover:bg-primary/10 hover:text-primary group">
               <svg class="shrink-0 w-5 h-5 transition duration-75 group-hover:text-primary" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10V6a3 3 0 0 1 3-3v0a3 3 0 0 1 3 3v4m3-2 .917 11.923A1 1 0 0 1 17.92 21H6.08a1 1 0 0 1-.997-1.077L6 8h12Z"/></svg>
               <span class="flex-1 ms-3 whitespace-nowrap">Categories</span>
            </a>
         </li>
         <li>
            <a href="{{ url('/branch/create') }}" class="flex items-center px-2 py-1.5 text-body rounded-base hover:bg-primary/10 hover:text-primary group">
               <svg class="shrink-0 w-5 h-5 transition duration-75 group-hover:text-primary" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 4h12M6 4v16M6 4H5m13 0v16m0-16h1m-1 16H6m12 0h1M6 20H5M9 7h1v1H9V7Zm5 0h1v1h-1V7Zm-5 4h1v1H9v-1Zm5 0h1v1h-1v-1Zm-3 4h2a1 1 0 0 1 1 1v4h-4v-4a1 1 0 0 1 1-1Z"/></svg>
               <span class="flex-1 ms-3 whitespace-nowrap">Branch Register</span>
            </a>
         </li>
         <li>
            <a href="#" class="flex items-center px-2 py-1.5 text-body rounded-base hover:bg-primary/10 hover:text-primary group">
               <svg class="shrink-0 w-5 h-5 transition duration-75 group-hover:text-primary" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12H4m12 0-4 4m4-4-4-4m3-4h2a3 3 0 0 1 3 3v10a3 3 0 0 1-3 3h-2"/></svg>
               <span class="flex-1 ms-3 whitespace-nowrap">Sign In</span>
            </a>
         </li>
      </ul>
      <ul class="space-y-2 font-medium border-t border-default pt-4 mt-4">
         <li>
            <a href="#" class="flex items-center px-2 py-1.5 text-body rounded-base hover:bg-primary/10 hover:text-primary group">
               <svg class="shrink-0 w-5 h-5 transition duration-75 group-hover:text-primary" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19V4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v13H7a2 2 0 0 0-2 2Zm0 0a2 2 0 0 0 2 2h12M9 3v14m7 0v4"/></svg>
               <span class="flex-1 ms-3 whitespace-nowrap">Documentation</span>
            </a>
         </li>
         <li>
            <a href="#" class="flex items-center px-2 py-1.5 text-body rounded-base hover:bg-primary/10 hover:text-primary group">
               <svg class="shrink-0 w-5 h-5 transition duration-75 group-hover:text-primary" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m13.46 8.291 3.849-3.849a1.5 1.5 0 0 1 2.122 0l.127.127a1.5 1.5 0 0 1 0 2.122l-3.84 3.838a4 4 0 0 0-2.258-2.238Zm0 0a4 4 0 0 1 2.263 2.238l3.662-3.662a8.961 8.961 0 0 1 0 10.27l-3.676-3.676m-2.25-5.17 3.678-3.676a8.961 8.961 0 0 0-10.27 0l3.662 3.662a4 4 0 0 0-2.238 2.258L4.615 6.863a8.96 8.96 0 0 0 0 10.27l3.662-3.662a4 4 0 0 0 2.258 2.238l-3.672 3.676a8.96 8.96 0 0 0 10.27 0l-3.662-3.662a4.001 4.001 0 0 0 2.238-2.262m0 0 3.849 3.848a1.5 1.5 0 0 1 0 2.122l-.127.126a1.499 1.499 0 0 1-2.122 0l-3.838-3.838a4 4 0 0 0 2.238-2.258Zm.29-1.461a4 4 0 1 1-8 0 4 4 0 0 1 8 0Zm-7.718 1.471-3.84 3.838a1.5 1.5 0 0 0 0 2.122l.128.126a1.5 1.5 0 0 0 2.122 0l3.848-3.848a4 4 0 0 1-2.258-2.238Zm2.248-5.19L6.69 4.442a1.5 1.5 0 0 0-2.122 0l-.127.127a1.5 1.5 0 0 0 0 2.122l3.849 3.848a4 4 0 0 1 2.238-2.258Z"/></svg>
               <span class="flex-1 ms-3 whitespace-nowrap">Support</span>
            </a>
         </li>
         <li>
            <a href="#" class="flex items-center px-2 py-1.5 text-body rounded-base hover:bg-primary/10 hover:text-primary group">
               <svg class="shrink-0 w-5 h-5 transition duration-75 group-hover:text-primary" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m10.051 8.102-3.778.322-1.994 1.994a.94.94 0 0 0 .533 1.6l2.698.316m8.39 1.617-.322 3.78-1.994 1.994a.94.94 0 0 1-1.595-.533l-.4-2.652m8.166-11.174a1.366 1.366 0 0 0-1.12-1.12c-1.616-.279-4.906-.623-6.38.853-1.671 1.672-5.211 8.015-6.31 10.023a.932.932 0 0 0 .162 1.111l.828.835.833.832a.932.932 0 0 0 1.111.163c2.008-1.102 8.35-4.642 10.021-6.312 1.475-1.478 1.133-4.77.855-6.385Zm-2.961 3.722a1.88 1.88 0 1 1-3.76 0 1.88 1.88 0 0 1 3.76 0Z"/></svg>
               <span class="flex-1 ms-3 whitespace-nowrap">PRO version</span>
            </a>
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
