

<?php $__env->startSection('content'); ?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Goods Received Notes</h1>
                <p class="mt-1 text-sm text-gray-600">Record and inspect incoming deliveries</p>
            </div>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('grn.create')): ?>
            <div class="mt-4 sm:mt-0">
                <a href="<?php echo e(route('grn.create')); ?>" class="inline-flex items-center px-4 py-2 rounded-md bg-primary-600 text-white hover:bg-primary-700 transition-colors">
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    New GRN
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <form method="GET" action="<?php echo e(route('grn.index')); ?>" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Inspection Status -->
                <div>
                    <label for="inspection_status" class="block text-sm font-medium text-gray-700 mb-1">Inspection</label>
                    <select name="inspection_status" id="inspection_status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">All</option>
                        <option value="pending" <?php echo e(request('inspection_status') === 'pending' ? 'selected' : ''); ?>>Pending</option>
                        <option value="passed" <?php echo e(request('inspection_status') === 'passed' ? 'selected' : ''); ?>>Passed</option>
                        <option value="rejected" <?php echo e(request('inspection_status') === 'rejected' ? 'selected' : ''); ?>>Rejected</option>
                    </select>
                </div>

                <!-- Quality Check -->
                <div>
                    <label for="quality_check" class="block text-sm font-medium text-gray-700 mb-1">Quality Check</label>
                    <select name="quality_check" id="quality_check" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">All</option>
                        <option value="passed" <?php echo e(request('quality_check') === 'passed' ? 'selected' : ''); ?>>Passed</option>
                        <option value="failed" <?php echo e(request('quality_check') === 'failed' ? 'selected' : ''); ?>>Failed</option>
                    </select>
                </div>

                <!-- PO Status -->
                <div>
                    <label for="po_status" class="block text-sm font-medium text-gray-700 mb-1">Referenced PO</label>
                    <select name="po_id" id="po_status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">All POs</option>
                        <option value="active" <?php echo e(request('po_id') ? 'selected' : ''); ?>>â€” filtered â€”</option>
                    </select>
                </div>

                <!-- Date Range -->
                <div>
                    <label for="from_date" class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                    <input type="date" name="from_date" id="from_date" value="<?php echo e(request('from_date')); ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>

                <!-- Search -->
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-3 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" name="search" id="search" placeholder="GRN #, PO #..." value="<?php echo e(request('search')); ?>" class="w-full pl-10 pr-4 py-2 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>
                </div>
            </div>

            <!-- Filter Actions -->
            <div class="flex gap-2 justify-end">
                <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md bg-primary-600 text-white hover:bg-primary-700 transition-colors">
                    Filter
                </button>
                <?php if(request()->hasAny(['inspection_status', 'quality_check', 'po_id', 'from_date', 'search'])): ?>
                <a href="<?php echo e(route('grn.index')); ?>" class="inline-flex items-center px-4 py-2 rounded-md bg-gray-200 text-gray-700 hover:bg-gray-300 transition-colors">
                    Clear
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- GRNs Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-300">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">GRN Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">PO Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Supplier</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Items</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Inspection</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Quality</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php $__empty_1 = true; $__currentLoopData = $grns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $grn): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="<?php echo e(route('grn.show', $grn->id)); ?>" class="text-primary-600 hover:text-primary-700"><?php echo e($grn->grn_number); ?></a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo e($grn->purchaseOrder->po_number); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo e($grn->created_at->format('d M Y')); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo e($grn->purchaseOrder->supplier->business_name); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo e($grn->grnItems->count()); ?> items</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $inspectionStatus = $grn->inspection_status ?? 'pending';
                            $inspectionColors = [
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'passed' => 'bg-green-100 text-green-800',
                                'rejected' => 'bg-red-100 text-red-800',
                            ];
                            ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo e($inspectionColors[$inspectionStatus] ?? 'bg-gray-100 text-gray-800'); ?>">
                                <?php echo e(str_replace('_', ' ', ucwords($inspectionStatus))); ?>

                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if($grn->quality_check_passed): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    âœ“ Passed
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    Pending
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            <div class="relative x-data inline-block text-left" x-data="{ open: false }">
                                <button @click="open = !open" class="inline-flex items-center text-gray-400 hover:text-gray-600">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10.5 1.5H9.5V.5h1v1zm0 17H9.5v1h1v-1zm8-8.5v1h1v-1h-1zm-17 0v1H.5v-1h1zM5.5 5.5L4.793 4.793l-.707.707L4.793 5.5l.707.707.707-.707zm8 8L12.793 12.793l-.707.707.707.707.707-.707zm0-8L12.793 4.793l-.707.707.707.707.707-.707zm-8 8L4.793 12.793l-.707.707.707.707.707-.707z"/>
                                    </svg>
                                </button>
                                <div x-show="open" @click.away="open = false" class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10">
                                    <a href="<?php echo e(route('grn.show', $grn->id)); ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">View Details</a>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('grn.inspect')): ?>
                                    <?php if($grn->inspection_status === 'pending'): ?>
                                    <a href="<?php echo e(route('grn.inspect', $grn->id)); ?>" class="block px-4 py-2 text-sm text-primary-700 hover:bg-primary-50">Inspect Delivery</a>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('grn.post')): ?>
                                    <?php if($grn->inspection_status === 'passed' && !$grn->posted_to_inventory): ?>
                                    <form action="<?php echo e(route('grn.post', $grn->id)); ?>" method="POST" class="inline">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-green-700 hover:bg-green-50" onclick="return confirm('Post to inventory?')">Post to Inventory</button>
                                    </form>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <svg class="h-12 w-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="text-gray-600">No GRNs found</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if($grns->hasPages()): ?>
    <div class="mt-6">
        <?php echo e($grns->links()); ?>

    </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\laragon\www\procurement\resources\views/grn/index.blade.php ENDPATH**/ ?>