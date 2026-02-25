<!-- Sidebar component -->
<div class="flex grow flex-col overflow-y-auto bg-gradient-to-b from-primary-900 to-primary-800 px-6 pb-4 ring-1 ring-white/10 pt-0">
    <!-- Logo -->
    <a href="<?php echo e(route('dashboard')); ?>" class="flex shrink-0 items-center hover:opacity-80 transition-opacity py-3">
        <img class="h-14 w-auto" src="/images/st_c_logo.png" alt="School Crest">
        <span class="ml-2 text-white font-bold text-sm">Procurement</span>
    </a>

    <nav class="flex flex-1 flex-col">
        <ul role="list" class="flex flex-1 flex-col gap-y-7">
            <li>
                <ul role="list" class="-mx-2 space-y-1">
                    <!-- Dashboard -->
                    <li>
                        <a href="<?php echo e(route('dashboard')); ?>" 
                           class="group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold <?php echo e(request()->routeIs('dashboard') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700'); ?>">
                            <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                            </svg>
                            Dashboard
                        </a>
                    </li>

                    <!-- Planning / APP -->
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('manage_annual_procurement_plans')): ?>
                    <li>
                        <a href="<?php echo e(route('annual-procurement-plans.index')); ?>"
                           class="group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold <?php echo e(request()->routeIs('annual-procurement-plans.*') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700'); ?>">
                            <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2" />
                                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5" fill="none" />
                            </svg>
                            Annual Procurement Plans
                        </a>
                    </li>
                    <?php endif; ?>

                    <!-- Always show Requisitions for HODs and anyone with requisitions.view or requisitions.approve-hod -->
                    <?php if(auth()->user() && (auth()->user()->hasRole(['hod', 'depthead', 'department head']) || auth()->user()->can('requisitions.view') || auth()->user()->can('requisitions.approve-hod'))): ?>
                    <li x-data="{ open: <?php echo e(request()->routeIs('requisitions.*', 'procurement.*', 'purchase-orders.*', 'grn.*', 'suppliers.*') ? 'true' : 'false'); ?> }">
                        <button @click="open = !open" 
                                class="group flex w-full gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold <?php echo e(request()->routeIs('requisitions.*', 'procurement.*', 'purchase-orders.*', 'grn.*', 'suppliers.*') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700'); ?>">
                            <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                            </svg>
                            Procurement
                            <?php if(isset($pendingRequisitions) && $pendingRequisitions > 0): ?>
                                <span class="ml-auto mr-2 w-6 h-6 rounded-full bg-red-500 text-white text-xs flex items-center justify-center"><?php echo e($pendingRequisitions); ?></span>
                            <?php endif; ?>
                            <svg class="ml-auto h-5 w-5 shrink-0 transition-transform" :class="{ 'rotate-90': open }" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </button>
                        <ul x-show="open" x-cloak class="mt-1 px-2 space-y-1">
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('requisitions.view')): ?>
                            <li>
                                <a href="<?php echo e(route('requisitions.index')); ?>"
                                   class="group flex gap-x-3 rounded-md p-2 pl-9 text-sm leading-6 font-semibold <?php echo e(request()->routeIs('requisitions.index') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700'); ?>">
                                    Requisitions
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('requisitions.approve-hod')): ?>
                            <li>
                                <a href="<?php echo e(route('requisitions.pending-approval')); ?>"
                                   class="group flex items-center gap-x-3 rounded-md p-2 pl-9 text-sm leading-6 font-semibold <?php echo e(request()->routeIs('requisitions.pending-approval') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700'); ?>">
                                    Pending Approvals
                                    <?php if(isset($pendingRequisitions) && $pendingRequisitions > 0): ?>
                                        <span class="ml-auto w-5 h-5 rounded-full bg-red-500 text-white text-xs flex items-center justify-center"><?php echo e($pendingRequisitions); ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('purchase-orders.view')): ?>
                            <li>
                                <a href="<?php echo e(route('purchase-orders.index')); ?>" 
                                   class="group flex gap-x-3 rounded-md p-2 pl-9 text-sm leading-6 font-semibold <?php echo e(request()->routeIs('purchase-orders.*') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700'); ?>">
                                    Purchase Orders
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('grn.view')): ?>
                            <li>
                                <a href="<?php echo e(route('grn.index')); ?>" 
                                   class="group flex gap-x-3 rounded-md p-2 pl-9 text-sm leading-6 font-semibold <?php echo e(request()->routeIs('grn.*') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700'); ?>">
                                    Goods Received
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('suppliers.view')): ?>
                            <li>
                                <a href="<?php echo e(route('suppliers.index')); ?>"
                                   class="group flex gap-x-3 rounded-md p-2 pl-9 text-sm leading-6 font-semibold <?php echo e(request()->routeIs('suppliers.index', 'suppliers.show', 'suppliers.create', 'suppliers.edit') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700'); ?>">
                                    Suppliers
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo e(route('suppliers.asl.index')); ?>"
                                   class="group flex gap-x-3 rounded-md p-2 pl-9 text-sm leading-6 font-semibold <?php echo e(request()->routeIs('suppliers.asl.*') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700'); ?>">
                                    Approved Supplier List
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <!-- Inventory -->
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('inventory.view')): ?>
                    <li>
                        <a href="<?php echo e(route('inventory.index')); ?>" 
                           class="group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold <?php echo e(request()->routeIs('inventory.*') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700'); ?>">
                            <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                            Inventory
                        </a>
                    </li>
                    <?php endif; ?>

                    <!-- Finance Group -->
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['invoices.view', 'budget.view'])): ?>
                    <li x-data="{ open: <?php echo e(request()->routeIs('budgets.*', 'invoices.*', 'payments.*') ? 'true' : 'false'); ?> }">
                        <button @click="open = !open" 
                                class="group flex w-full gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold <?php echo e(request()->routeIs('budgets.*', 'invoices.*', 'payments.*') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700'); ?>">
                            <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                            </svg>
                            Finance
                            <svg class="ml-auto h-5 w-5 shrink-0 transition-transform" :class="{ 'rotate-90': open }" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </button>
                        <ul x-show="open" x-cloak class="mt-1 px-2 space-y-1">
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('budget.view')): ?>
                            <li>
                                <a href="<?php echo e(route('budgets.setup')); ?>" 
                                   class="group flex gap-x-3 rounded-md p-2 pl-9 text-sm leading-6 font-semibold <?php echo e(request()->routeIs('budgets.setup', 'budgets.department-setup') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700'); ?>">
                                    Budget Setup
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo e(route('budgets.dashboard')); ?>" 
                                   class="group flex gap-x-3 rounded-md p-2 pl-9 text-sm leading-6 font-semibold <?php echo e(request()->routeIs('budgets.dashboard') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700'); ?>">
                                    Budget Dashboard
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo e(route('budgets.index')); ?>" 
                                   class="group flex gap-x-3 rounded-md p-2 pl-9 text-sm leading-6 font-semibold <?php echo e(request()->routeIs('budgets.index', 'budgets.show', 'budgets.create', 'budgets.edit') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700'); ?>">
                                    Budget Lines
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo e(route('budgets.pending')); ?>" 
                                   class="group flex gap-x-3 rounded-md p-2 pl-9 text-sm leading-6 font-semibold <?php echo e(request()->routeIs('budgets.pending', 'budgets.show-approval', 'budgets.approve', 'budgets.reject') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700'); ?>">
                                    Pending Approvals
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('invoices.view')): ?>
                            <li>
                                <a href="<?php echo e(route('invoices.index')); ?>" 
                                   class="group flex gap-x-3 rounded-md p-2 pl-9 text-sm leading-6 font-semibold <?php echo e(request()->routeIs('invoices.*') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700'); ?>">
                                    Invoices
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo e(route('payments.index')); ?>" 
                                   class="group flex gap-x-3 rounded-md p-2 pl-9 text-sm leading-6 font-semibold <?php echo e(request()->routeIs('payments.*') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700'); ?>">
                                    Payments
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo e(route('payments.wht-list')); ?>" 
                                   class="group flex gap-x-3 rounded-md p-2 pl-9 text-sm leading-6 font-semibold <?php echo e(request()->routeIs('payments.wht-list', 'payments.wht-certificate', 'payments.wht-bulk-download') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700'); ?>">
                                    WHT Certificates
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <!-- CAPA / Quality -->
                    <li>
                        <a href="<?php echo e(route('capa.index')); ?>"
                           class="group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold <?php echo e(request()->routeIs('capa.*') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700'); ?>">
                            <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            CAPA / Quality
                        </a>
                    </li>

                    <!-- Reports -->
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['reports.procurement', 'reports.financial', 'reports.inventory', 'reports.compliance', 'reports.audit'])): ?>
                    <li x-data="{ open: <?php echo e(request()->routeIs('reports.*') ? 'true' : 'false'); ?> }">
                        <button @click="open = !open"
                                class="group flex w-full gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold <?php echo e(request()->routeIs('reports.*') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700'); ?>">
                            <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                            </svg>
                            Reports
                            <svg class="ml-auto h-5 w-5 shrink-0 transition-transform" :class="{ 'rotate-90': open }" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </button>
                        <ul x-show="open" x-cloak class="mt-1 px-2 space-y-1">
                            <li>
                                <a href="<?php echo e(route('reports.dashboard')); ?>"
                                   class="group flex gap-x-3 rounded-md p-2 pl-9 text-sm leading-6 font-semibold <?php echo e(request()->routeIs('reports.dashboard') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700'); ?>">
                                    KPI Dashboard
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo e(route('reports.index')); ?>"
                                   class="group flex gap-x-3 rounded-md p-2 pl-9 text-sm leading-6 font-semibold <?php echo e(request()->routeIs('reports.index') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700'); ?>">
                                    All Reports
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
            </li>

            <!-- Admin Section -->
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['users.manage', 'roles.manage', 'system.configure', 'departments.manage'])): ?>
            <li x-data="{ open: <?php echo e(request()->routeIs('admin.users.*', 'admin.settings.*', 'departments.*') ? 'true' : 'false'); ?> }">
                <button @click="open = !open" 
                        class="group flex w-full gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold <?php echo e(request()->routeIs('admin.users.*', 'admin.settings.*', 'departments.*') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700'); ?>">
                    <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Administration
                    <svg class="ml-auto h-5 w-5 shrink-0 transition-transform" :class="{ 'rotate-90': open }" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </button>
                <ul x-show="open" x-cloak class="mt-1 px-2 space-y-1">
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('users.manage')): ?>
                    <li>
                        <a href="<?php echo e(route('admin.users.index')); ?>" 
                           class="group flex gap-x-3 rounded-md p-2 pl-9 text-sm leading-6 font-semibold <?php echo e(request()->routeIs('admin.users.*') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700'); ?>">
                            <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                            </svg>
                            Users
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('departments.manage')): ?>
                    <li>
                        <a href="<?php echo e(route('departments.index')); ?>" 
                           class="group flex gap-x-3 rounded-md p-2 pl-9 text-sm leading-6 font-semibold <?php echo e(request()->routeIs('departments.*') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700'); ?>">
                            <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            Departments
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('system.configure')): ?>
                    <li>
                        <a href="<?php echo e(route('admin.settings.index')); ?>" 
                           class="group flex gap-x-3 rounded-md p-2 pl-9 text-sm leading-6 font-semibold <?php echo e(request()->routeIs('admin.settings.*') ? 'bg-primary-700 text-white' : 'text-primary-200 hover:text-white hover:bg-primary-700'); ?>">
                            <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Settings
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>

            <!-- User section at bottom -->
            <li class="-mx-6 mt-auto">
                <div class="flex items-center gap-x-4 px-6 py-3 text-sm font-semibold leading-6 text-white border-t border-primary-700">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-700 text-white font-bold">
                        <?php echo e(auth()->user()->initials); ?>

                    </div>
                    <div class="flex-1">
                        <span class="block"><?php echo e(auth()->user()->name); ?></span>
                        <span class="block text-xs text-primary-200"><?php echo e(auth()->user()->roles_list); ?></span>
                    </div>
                </div>
            </li>
        </ul>
    </nav>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>

<?php /**PATH C:\laragon\www\procurement\resources\views/layouts/partials/sidebar.blade.php ENDPATH**/ ?>