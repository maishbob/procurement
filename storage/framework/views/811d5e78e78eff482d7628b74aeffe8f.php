

<?php $__env->startSection('content'); ?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Purchase Orders</h1>
                <p class="mt-1 text-sm text-gray-600">Manage supplier purchase orders and deliveries</p>
            </div>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('purchase-orders.create')): ?>
            <div class="mt-4 sm:mt-0">
                <a href="<?php echo e(route('purchase-orders.create')); ?>" class="inline-flex items-center px-4 py-2 rounded-md bg-primary-600 text-white hover:bg-primary-700 transition-colors">
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    New PO
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <form method="GET" action="<?php echo e(route('purchase-orders.index')); ?>" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Status Filter -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">All Statuses</option>
                        <option value="draft" <?php echo e(request('status') === 'draft' ? 'selected' : ''); ?>>Draft</option>
                        <option value="issued" <?php echo e(request('status') === 'issued' ? 'selected' : ''); ?>>Issued</option>
                        <option value="acknowledged" <?php echo e(request('status') === 'acknowledged' ? 'selected' : ''); ?>>Acknowledged</option>
                        <option value="partial_receipt" <?php echo e(request('status') === 'partial_receipt' ? 'selected' : ''); ?>>Partial Receipt</option>
                        <option value="fully_received" <?php echo e(request('status') === 'fully_received' ? 'selected' : ''); ?>>Fully Received</option>
                        <option value="cancelled" <?php echo e(request('status') === 'cancelled' ? 'selected' : ''); ?>>Cancelled</option>
                    </select>
                </div>

                <!-- Receiving Status Filter -->
                <div>
                    <label for="receiving_status" class="block text-sm font-medium text-gray-700 mb-1">Receiving Status</label>
                    <select name="receiving_status" id="receiving_status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">All</option>
                        <option value="pending" <?php echo e(request('receiving_status') === 'pending' ? 'selected' : ''); ?>>Pending</option>
                        <option value="partially_received" <?php echo e(request('receiving_status') === 'partially_received' ? 'selected' : ''); ?>>Partially Received</option>
                        <option value="fully_received" <?php echo e(request('receiving_status') === 'fully_received' ? 'selected' : ''); ?>>Fully Received</option>
                    </select>
                </div>

                <!-- Supplier Filter -->
                <div>
                    <label for="supplier" class="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
                    <select name="supplier_id" id="supplier" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">All Suppliers</option>
                        <option value="active" <?php echo e(request('supplier_id') ? 'selected' : ''); ?>>â€” filtered â€”</option>
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
                        <input type="text" name="search" id="search" placeholder="PO #, supplier..." value="<?php echo e(request('search')); ?>" class="w-full pl-10 pr-4 py-2 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>
                </div>
            </div>

            <!-- Filter Actions -->
            <div class="flex gap-2 justify-end">
                <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md bg-primary-600 text-white hover:bg-primary-700 transition-colors">
                    Search
                </button>
                <?php if(request()->hasAny(['status', 'receiving_status', 'supplier_id', 'from_date', 'search'])): ?>
                <a href="<?php echo e(route('purchase-orders.index')); ?>" class="inline-flex items-center px-4 py-2 rounded-md bg-gray-200 text-gray-700 hover:bg-gray-300 transition-colors">
                    Clear
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- POs Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-300">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">PO Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Supplier</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Delivery Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Receiving</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php $__empty_1 = true; $__currentLoopData = $purchaseOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $po): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="<?php echo e(route('purchase-orders.show', $po->id)); ?>" class="text-primary-600 hover:text-primary-700"><?php echo e($po->po_number); ?></a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo e($po->created_at->format('d M Y')); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo e($po->supplier->business_name); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">KES <?php echo e(number_format($po->total_amount, 2)); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo e($po->delivery_date->format('d M Y')); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $statusColors = [
                                'draft' => 'bg-gray-100 text-gray-800',
                                'issued' => 'bg-primary-100 text-primary-800',
                                'acknowledged' => 'bg-green-100 text-green-800',
                                'partial_receipt' => 'bg-yellow-100 text-yellow-800',
                                'fully_received' => 'bg-green-100 text-green-800',
                                'cancelled' => 'bg-red-100 text-red-800',
                            ];
                            ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo e($statusColors[$po->status] ?? 'bg-gray-100 text-gray-800'); ?>">
                                <?php echo e(str_replace('_', ' ', ucwords($po->status))); ?>

                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $receivingStatus = $po->getReceivingStatus();
                            ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                <?php echo e($receivingStatus === 'pending' ? 'bg-red-100 text-red-800' : ''); ?>

                                <?php echo e($receivingStatus === 'partially_received' ? 'bg-yellow-100 text-yellow-800' : ''); ?>

                                <?php echo e($receivingStatus === 'fully_received' ? 'bg-green-100 text-green-800' : ''); ?>">
                                <?php echo e(str_replace('_', ' ', ucwords($receivingStatus))); ?>

                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            <div class="relative x-data inline-block text-left" x-data="{ open: false }">
                                <button @click="open = !open" class="inline-flex items-center text-gray-400 hover:text-gray-600">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M5.5 13a3.5 3.5 0 01-.369-6.98 4 4 0 117.753-1.3A4.5 4.5 0 1113.5 13H11V9.413l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13H5.5z"/>
                                    </svg>
                                </button>
                                <div x-show="open" @click.away="open = false" class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10">
                                    <a href="<?php echo e(route('purchase-orders.show', $po->id)); ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">View</a>
                                    <?php if($po->status === 'draft'): ?>
                                    <a href="<?php echo e(route('purchase-orders.edit', $po->id)); ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Edit</a>
                                    <?php endif; ?>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('purchase-orders.issue')): ?>
                                    <?php if($po->status === 'draft'): ?>
                                    <form action="<?php echo e(route('purchase-orders.issue', $po->id)); ?>" method="POST" class="inline">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-primary-700 hover:bg-primary-50">Issue PO</button>
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
                            <p class="text-gray-600 mb-4">No purchase orders found</p>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('purchase-orders.create')): ?>
                            <a href="<?php echo e(route('purchase-orders.create')); ?>" class="inline-flex items-center px-4 py-2 rounded-md bg-primary-600 text-white hover:bg-primary-700">
                                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Create New PO
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if($purchaseOrders->hasPages()): ?>
    <div class="mt-6">
        <?php echo e($purchaseOrders->links()); ?>

    </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\laragon\www\procurement\resources\views/purchase-orders/index.blade.php ENDPATH**/ ?>