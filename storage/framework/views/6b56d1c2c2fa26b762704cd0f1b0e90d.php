

<?php $__env->startSection('title', 'Dashboard'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-8">
    <!-- Page header -->
    <div>
        <h1 class="text-4xl font-bold text-gray-900">Welcome back, <?php echo e(auth()->user()->first_name); ?>!</h1>
        <p class="mt-2 text-lg text-gray-600">Here's what's happening in your procurement system today.</p>
    </div>

    <!-- Stats cards -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Pending Approvals -->
        <div class="rounded-lg bg-white p-6 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Pending Approvals</p>
                    <p class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-gray-900"><?php echo e($stats['pending_approvals'] ?? 12); ?></span>
                        <span class="text-sm font-semibold text-red-600">Urgent</span>
                    </p>
                </div>
                <div class="rounded-lg bg-red-100 p-3">
                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Active Requisitions -->
        <div class="rounded-lg bg-white p-6 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Active Requisitions</p>
                    <p class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-gray-900"><?php echo e($stats['active_requisitions'] ?? 28); ?></span>
                        <span class="text-sm font-semibold text-green-600">+4.5%</span>
                    </p>
                </div>
                <div class="rounded-lg bg-green-100 p-3">
                    <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75m9-7.5a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Budget Utilization -->
        <div class="rounded-lg bg-white p-6 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Budget Utilization</p>
                    <p class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-gray-900"><?php echo e($stats['budget_utilization'] ?? 72); ?>%</span>
                        <span class="text-sm font-semibold text-amber-600">Warning</span>
                    </p>
                </div>
                <div class="rounded-lg bg-amber-100 p-3">
                    <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Low Stock Items -->
        <div class="rounded-lg bg-white p-6 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Low Stock Items</p>
                    <p class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-gray-900"><?php echo e($stats['low_stock_items'] ?? 7); ?></span>
                        <span class="text-sm font-semibold text-orange-600">Action</span>
                    </p>
                </div>
                <div class="rounded-lg bg-orange-100 p-3">
                    <svg class="h-6 w-6 text-orange-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Two column layout -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Recent requisitions -->
        <div class="rounded-lg bg-white p-6 shadow-sm">
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900">Recent Requisitions</h3>
            </div>
            <div class="space-y-4">
                <?php $__empty_1 = true; $__currentLoopData = $recentRequisitions ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $requisition): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="border border-gray-200 rounded-lg p-4 hover:border-primary-300 transition-colors">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-primary-600"><?php echo e($requisition->requisition_number ?? 'N/A'); ?></p>
                            <p class="mt-1 text-sm text-gray-600"><?php echo e($requisition->purpose ?? 'No purpose'); ?></p>
                            <p class="mt-2 text-xs text-gray-500">Department</p>
                        </div>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ml-4 <?php echo e($requisition->status_color ?? 'bg-gray-100 text-gray-700'); ?>">
                            <?php echo e($requisition->status_label ?? 'Draft'); ?>

                        </span>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="text-center py-8">
                    <p class="text-sm text-gray-500">No recent requisitions</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pending actions -->
        <div class="rounded-lg bg-white p-6 shadow-sm">
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900">Pending Your Action</h3>
            </div>
            <div class="space-y-4">
                <?php $__empty_1 = true; $__currentLoopData = $pendingActions ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $action): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="border border-amber-200 bg-amber-50 rounded-lg p-4">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 pt-0.5">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-amber-200">
                                <svg class="h-4 w-4 text-amber-700" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900"><?php echo e($action['title'] ?? 'Approval Required'); ?></p>
                            <p class="mt-1 text-sm text-gray-700"><?php echo e($action['description'] ?? 'Requisition awaiting your approval'); ?></p>
                            <a href="<?php echo e($action['url'] ?? '#'); ?>" class="mt-3 inline-flex items-center text-sm font-semibold text-primary-600 hover:text-primary-700">
                                Review <span class="ml-1">→</span>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="text-sm font-medium text-gray-900">All caught up!</h3>
                    <p class="text-sm text-gray-500">No pending actions at the moment.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Charts row -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Budget chart -->
        <div class="rounded-lg bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Budget Overview</h3>
            <div class="space-y-5">
                <?php $__empty_1 = true; $__currentLoopData = $budgetLines ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $budget): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-gray-700"><?php echo e($budget['name'] ?? 'Department Budget'); ?></span>
                        <span class="text-sm font-semibold text-gray-900"><?php echo e($budget['percentage'] ?? 0); ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full <?php echo e($budget['color'] ?? 'bg-primary-600'); ?>" style="width: <?php echo e($budget['percentage'] ?? 0); ?>%"></div>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="text-center py-8">
                    <p class="text-sm text-gray-500">No budget data available</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Activity feed -->
        <div class="rounded-lg bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Recent Activity</h3>
            <div class="space-y-4">
                <?php $__empty_1 = true; $__currentLoopData = $recentActivity ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $activity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="flex gap-3">
                    <div class="flex-shrink-0">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-<?php echo e($activity['color'] ?? 'gray'); ?>-100">
                            <svg class="h-4 w-4 text-<?php echo e($activity['color'] ?? 'gray'); ?>-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-700"><?php echo e($activity['description'] ?? 'Activity description'); ?></p>
                        <p class="mt-1 text-xs text-gray-500"><?php echo e($activity['time'] ?? 'Just now'); ?></p>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="text-center py-8">
                    <p class="text-sm text-gray-500">No recent activity</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\laragon\www\procurement\resources\views/dashboard/index.blade.php ENDPATH**/ ?>