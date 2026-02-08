

<?php $__env->startSection('content'); ?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Inventory</h1>
                <p class="mt-1 text-sm text-gray-600">Manage items, stock levels, and adjustments</p>
            </div>
            <div class="mt-4 sm:mt-0 flex gap-2 flex-wrap">
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('inventory.manage')): ?>
                <a href="<?php echo e(route('inventory.create')); ?>" class="inline-flex items-center px-4 py-2 rounded-md bg-primary-600 text-white hover:bg-primary-700 transition-colors">
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    New Item
                </a>
                <?php endif; ?>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('inventory.view-reorder')): ?>
                <a href="<?php echo e(route('inventory.reorder-report')); ?>" class="inline-flex items-center px-4 py-2 rounded-md bg-gray-200 text-gray-700 hover:bg-gray-300 transition-colors">
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Reorder Report
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600 mb-1">Total Items</p>
            <p class="text-3xl font-bold text-gray-900"><?php echo e($stats['total_items'] ?? 0); ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6 border-t-4 border-red-500">
            <p class="text-sm text-gray-600 mb-1">Out of Stock</p>
            <p class="text-3xl font-bold text-red-600"><?php echo e($stats['out_of_stock'] ?? 0); ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6 border-t-4 border-yellow-500">
            <p class="text-sm text-gray-600 mb-1">Low Stock</p>
            <p class="text-3xl font-bold text-yellow-600"><?php echo e($stats['low_stock'] ?? 0); ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6 border-t-4 border-green-500">
            <p class="text-sm text-gray-600 mb-1">Adequate Stock</p>
            <p class="text-3xl font-bold text-green-600"><?php echo e($stats['adequate_stock'] ?? 0); ?></p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <form method="GET" action="<?php echo e(route('inventory.index')); ?>" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Stock Status -->
                <div>
                    <label for="stock_status" class="block text-sm font-medium text-gray-700 mb-1">Stock Status</label>
                    <select name="stock_status" id="stock_status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">All Statuses</option>
                        <option value="out_of_stock" <?php echo e(request('stock_status') === 'out_of_stock' ? 'selected' : ''); ?>>Out of Stock</option>
                        <option value="low_stock" <?php echo e(request('stock_status') === 'low_stock' ? 'selected' : ''); ?>>Low Stock</option>
                        <option value="adequate" <?php echo e(request('stock_status') === 'adequate' ? 'selected' : ''); ?>>Adequate</option>
                        <option value="overstocked" <?php echo e(request('stock_status') === 'overstocked' ? 'selected' : ''); ?>>Overstocked</option>
                    </select>
                </div>

                <!-- Store Filter -->
                <div>
                    <label for="store" class="block text-sm font-medium text-gray-700 mb-1">Store</label>
                    <select name="store_id" id="store" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">All Stores</option>
                        <option value="store1" <?php echo e(request('store_id') ? 'selected' : ''); ?>>â€” filtered â€”</option>
                    </select>
                </div>

                <!-- Category Filter -->
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category" id="category" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">All Categories</option>
                        <option value="stationery" <?php echo e(request('category') === 'stationery' ? 'selected' : ''); ?>>Stationery</option>
                        <option value="ict" <?php echo e(request('category') === 'ict' ? 'selected' : ''); ?>>ICT</option>
                        <option value="maintenance" <?php echo e(request('category') === 'maintenance' ? 'selected' : ''); ?>>Maintenance</option>
                    </select>
                </div>

                <!-- Search -->
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-3 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" name="search" id="search" placeholder="Item name, code..." value="<?php echo e(request('search')); ?>" class="w-full pl-10 pr-4 py-2 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>
                </div>
            </div>

            <!-- Filter Actions -->
            <div class="flex gap-2 justify-end">
                <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md bg-primary-600 text-white hover:bg-primary-700 transition-colors">
                    Filter
                </button>
                <?php if(request()->hasAny(['stock_status', 'store_id', 'category', 'search'])): ?>
                <a href="<?php echo e(route('inventory.index')); ?>" class="inline-flex items-center px-4 py-2 rounded-md bg-gray-200 text-gray-700 hover:bg-gray-300 transition-colors">
                    Clear
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Inventory Items Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-300">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Item Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Item Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Store</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Qty On Hand</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Reorder Level</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Unit Cost</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-primary-600"><?php echo e($item->item_code); ?></td>
                        <td class="px-6 py-4 text-sm text-gray-900 max-w-xs">
                            <a href="<?php echo e(route('inventory.show', $item->id)); ?>" class="hover:text-primary-600"><?php echo e($item->item_name); ?></a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo e($item->store?->store_name ?? 'â€”'); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                            <?php echo e($item->quantity_on_hand ?? 0); ?>

                            <span class="text-xs text-gray-600"><?php echo e($item->unit_of_measure); ?></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo e($item->reorder_level ?? 0); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">KES <?php echo e(number_format($item->unit_cost, 2)); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $status = $item->getStockStatus();
                            $statusConfig = [
                                'out_of_stock' => ['bg-red-100', 'text-red-800', 'Out of Stock'],
                                'low_stock' => ['bg-yellow-100', 'text-yellow-800', 'Low Stock'],
                                'adequate' => ['bg-green-100', 'text-green-800', 'Adequate'],
                                'overstocked' => ['bg-primary-100', 'text-primary-800', 'Overstocked'],
                            ];
                            $config = $statusConfig[$status] ?? ['bg-gray-100', 'text-gray-800', 'Unknown'];
                            ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo e($config[0]); ?> <?php echo e($config[1]); ?>">
                                <?php echo e($config[2]); ?>

                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            <div class="relative x-data inline-block text-left" x-data="{ open: false }">
                                <button @click="open = !open" class="inline-flex items-center text-gray-400 hover:text-gray-600">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10.5 1.5H9.5V.5h1v1zm0 17H9.5v1h1v-1zm8-8.5v1h1v-1h-1zm-17 0v1H.5v-1h1zM5.5 5.5L4.793 4.793l-.707.707L4.793 5.5l.707.707.707-.707zm8 8L12.793 12.793l-.707.707.707.707.707-.707zm0-8L12.793 4.793l-.707.707.707.707.707-.707zm-8 8L4.793 12.793l-.707.707.707.707.707-.707z"/>
                                    </svg>
                                </button>
                                <div x-show="open" @click.away="open = false" class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10">
                                    <a href="<?php echo e(route('inventory.show', $item->id)); ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">View Details</a>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('inventory.issue')): ?>
                                    <a href="<?php echo e(route('inventory.issue-create', $item->id)); ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Issue Stock</a>
                                    <?php endif; ?>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('inventory.adjust')): ?>
                                    <a href="<?php echo e(route('inventory.adjustment-create', $item->id)); ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Adjust Stock</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <svg class="h-12 w-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m0 0l8 4m-8-4v10l8 4m0-10l8 4m-8-4v10M7 3l8 4"/>
                            </svg>
                            <p class="text-gray-600">No inventory items found</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if($items->hasPages()): ?>
    <div class="mt-6">
        <?php echo e($items->links()); ?>

    </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\laragon\www\procurement\resources\views/inventory/index.blade.php ENDPATH**/ ?>