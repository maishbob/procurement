<!-- Sidebar component -->
<div class="flex grow flex-col gap-y-5 overflow-y-auto bg-gradient-to-b from-primary-900 to-primary-800 px-6 pb-4 ring-1 ring-white/10">
    <!-- Logo -->
    <a href="{{ route('dashboard') }}" class="flex h-16 shrink-0 items-center hover:opacity-80 transition-opacity">
        <img class="h-12 w-auto" src="/images/st_c_logo.png" alt="School Crest">
        <span class="ml-2 text-white font-bold text-sm">Procurement</span>
    </a>

    <nav class="flex flex-1 flex-col">
        <ul role="list" class="flex flex-1 flex-col gap-y-7">
            <li>
                <ul role="list" class="-mx-2 space-y-1">
                    <!-- Dashboard -->
                    <li>
                        <a href="{{ route('dashboard') }}" 
                           class="group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold {{ request()->routeIs('dashboard') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700' }}">
                            <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                            </svg>
                            Dashboard
                        </a>
                    </li>

                    <!-- Requisitions -->
                    @can('requisitions.view')
                    <li>
                        <a href="{{ route('requisitions.index') }}" 
                           class="group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold {{ request()->routeIs('requisitions.*') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700' }}">
                            <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                            Requisitions
                            @if(isset($pendingRequisitions) && $pendingRequisitions > 0)
                                <span class="ml-auto w-6 h-6 rounded-full bg-red-500 text-white text-xs flex items-center justify-center">{{ $pendingRequisitions }}</span>
                            @endif
                        </a>
                    </li>
                    @endcan

                    <!-- Procurement -->
                    @can('procurement.view')
                    <li>
                        <a href="{{ route('procurement.index') }}" 
                           class="group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold {{ request()->routeIs('procurement.*') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700' }}">
                            <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                            </svg>
                            Procurement
                        </a>
                    </li>
                    @endcan

                    <!-- Purchase Orders -->
                    @can('purchase-orders.view')
                    <li>
                        <a href="{{ route('purchase-orders.index') }}" 
                           class="group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold {{ request()->routeIs('purchase-orders.*') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700' }}">
                            <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                            </svg>
                            Purchase Orders
                        </a>
                    </li>
                    @endcan

                    <!-- GRN / Receiving -->
                    @can('grn.view')
                    <li>
                        <a href="{{ route('grn.index') }}" 
                           class="group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold {{ request()->routeIs('grn.*') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700' }}">
                            <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.125-.504 1.125-1.125V13.5m-9-3.75h3.375c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125M13.5 9.75h7.875c.621 0 1.125.504 1.125 1.125v7.5c0 .621-.504 1.125-1.125 1.125h-7.875M3 9.75h3.375" />
                            </svg>
                            Goods Received
                        </a>
                    </li>
                    @endcan

                    <!-- Inventory -->
                    @can('inventory.view')
                    <li>
                        <a href="{{ route('inventory.index') }}" 
                           class="group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold {{ request()->routeIs('inventory.*') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700' }}">
                            <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                            Inventory
                        </a>
                    </li>
                    @endcan

                    <!-- Suppliers -->
                    @can('suppliers.view')
                    <li>
                        <a href="{{ route('suppliers.index') }}" 
                           class="group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold {{ request()->routeIs('suppliers.*') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700' }}">
                            <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z" />
                            </svg>
                            Suppliers
                        </a>
                    </li>
                    @endcan

                    <!-- Finance -->
                    @can('finance.view')
                    <li x-data="{ open: {{ request()->routeIs('invoices.*', 'payments.*') ? 'true' : 'false' }} }">
                        <button @click="open = !open" 
                                class="group flex w-full gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold text-primary-200 hover:text-white hover:bg-primary-700">
                            <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                            </svg>
                            Finance
                            <svg class="ml-auto h-5 w-5 shrink-0 transition-transform" :class="{ 'rotate-90': open }" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </button>
                        <ul x-show="open" x-cloak class="mt-1 px-2 space-y-1">
                            <li>
                                <a href="{{ route('invoices.index') }}" 
                                   class="group flex gap-x-3 rounded-md p-2 pl-9 text-sm leading-6 font-semibold {{ request()->routeIs('invoices.*') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700' }}">
                                    Invoices
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('payments.index') }}" 
                                   class="group flex gap-x-3 rounded-md p-2 pl-9 text-sm leading-6 font-semibold {{ request()->routeIs('payments.*') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700' }}">
                                    Payments
                                </a>
                            </li>
                            <li>
                                <a href="#" 
                                   class="group flex gap-x-3 rounded-md p-2 pl-9 text-sm leading-6 font-semibold {{ request()->routeIs('wht-certificates.*') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700' }}">
                                    WHT Certificates
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endcan

                    <!-- Reports -->
                    @can('reports.generate')
                    <li>
                        <a href="{{ route('reports.index') }}" 
                           class="group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold {{ request()->routeIs('reports.*') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700' }}">
                            <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                            </svg>
                            Reports
                        </a>
                    </li>
                    @endcan
                </ul>
            </li>

            <!-- Admin Section -->
            @canany(['admin.users', 'admin.roles', 'admin.settings'])
            <li>
                <div class="text-xs font-semibold leading-6 text-primary-200">Administration</div>
                <ul role="list" class="-mx-2 mt-2 space-y-1">
                    @can('admin.users')
                    <li>
                        <a href="#" 
                           class="group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold {{ request()->routeIs('admin.users.*') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700' }}">
                            <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                            </svg>
                            Users
                        </a>
                    </li>
                    @endcan
                    
                    @can('admin.settings')
                    <li>
                        <a href="#" 
                           class="group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold {{ request()->routeIs('admin.settings') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700' }}">
                            <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Settings
                        </a>
                    </li>
                    @endcan
                </ul>
            </li>
            @endcanany

            <!-- User section at bottom -->
            <li class="-mx-6 mt-auto">
                <div class="flex items-center gap-x-4 px-6 py-3 text-sm font-semibold leading-6 text-white border-t border-primary-700">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-700 text-white font-bold">
                        {{ substr(auth()->user()->first_name, 0, 1) }}{{ substr(auth()->user()->last_name, 0, 1) }}
                    </div>
                    <div class="flex-1">
                        <span class="block">{{ auth()->user()->full_name }}</span>
                        <span class="block text-xs text-primary-200">{{ auth()->user()->roles_list }}</span>
                    </div>
                </div>
            </li>
        </ul>
    </nav>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>

