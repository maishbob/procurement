

<?php $__env->startSection('title', 'Approved Supplier List'); ?>

<?php $__env->startSection('content'); ?>
<div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center mb-6">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Approved Supplier List (ASL)</h1>
            <p class="mt-1 text-sm text-gray-500">Manage which suppliers are approved to participate in procurement processes.</p>
        </div>
    </div>

    <?php if(session('success')): ?>
        <div class="mb-4 rounded-md bg-green-50 p-4">
            <p class="text-sm font-medium text-green-800"><?php echo e(session('success')); ?></p>
        </div>
    <?php endif; ?>
    <?php if(session('error')): ?>
        <div class="mb-4 rounded-md bg-red-50 p-4">
            <p class="text-sm font-medium text-red-800"><?php echo e(session('error')); ?></p>
        </div>
    <?php endif; ?>

    
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-5 mb-6">
        <?php $__currentLoopData = ['not_applied' => ['label'=>'Not Applied','color'=>'gray'], 'pending_review' => ['label'=>'Pending Review','color'=>'yellow'], 'approved' => ['label'=>'Approved','color'=>'green'], 'suspended' => ['label'=>'Suspended','color'=>'orange'], 'removed' => ['label'=>'Removed','color'=>'red']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s => $meta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e(route('suppliers.asl.index', ['status' => $s])); ?>"
           class="rounded-lg bg-white p-4 shadow text-center hover:shadow-md transition <?php echo e($status === $s ? 'ring-2 ring-primary-500' : ''); ?>">
            <p class="text-2xl font-bold text-<?php echo e($meta['color']); ?>-600"><?php echo e($counts[$s] ?? 0); ?></p>
            <p class="text-xs text-gray-500 mt-1"><?php echo e($meta['label']); ?></p>
        </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    
    <div class="mb-4 flex gap-2 flex-wrap">
        <a href="<?php echo e(route('suppliers.asl.index')); ?>"
           class="px-3 py-1.5 text-sm rounded-md <?php echo e(!$status ? 'bg-primary-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'); ?>">
            All
        </a>
        <?php $__currentLoopData = ['pending_review' => 'Pending Review', 'approved' => 'Approved', 'suspended' => 'Suspended', 'not_applied' => 'Not Applied', 'removed' => 'Removed']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e(route('suppliers.asl.index', ['status' => $s])); ?>"
           class="px-3 py-1.5 text-sm rounded-md <?php echo e($status === $s ? 'bg-primary-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'); ?>">
            <?php echo e($label); ?>

        </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    
    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 rounded-lg">
        <table class="min-w-full divide-y divide-gray-300">
            <thead class="bg-gray-50">
                <tr>
                    <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900">Supplier</th>
                    <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">KRA PIN</th>
                    <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">ASL Status</th>
                    <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Onboarding</th>
                    <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Review Due</th>
                    <th class="relative py-3.5 pl-3 pr-4"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                <?php $__empty_1 = true; $__currentLoopData = $suppliers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $supplier): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="hover:bg-gray-50">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3">
                        <div class="font-medium text-gray-900"><?php echo e($supplier->display_name ?? $supplier->business_name); ?></div>
                        <div class="text-xs text-gray-500"><?php echo e($supplier->supplier_code); ?></div>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500"><?php echo e($supplier->kra_pin ?? '—'); ?></td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                        <?php
                            $colors = ['approved'=>'green','pending_review'=>'yellow','suspended'=>'orange','removed'=>'red','not_applied'=>'gray'];
                            $labels = ['approved'=>'Approved','pending_review'=>'Pending Review','suspended'=>'Suspended','removed'=>'Removed','not_applied'=>'Not Applied'];
                            $c = $colors[$supplier->asl_status] ?? 'gray';
                        ?>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-<?php echo e($c); ?>-100 text-<?php echo e($c); ?>-800">
                            <?php echo e($labels[$supplier->asl_status] ?? $supplier->asl_status); ?>

                        </span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                        <?php $ob = $supplier->onboarding_status; $obColors = ['approved'=>'green','under_review'=>'blue','incomplete'=>'yellow','expired'=>'red']; ?>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-<?php echo e($obColors[$ob] ?? 'gray'); ?>-100 text-<?php echo e($obColors[$ob] ?? 'gray'); ?>-800">
                            <?php echo e(ucfirst(str_replace('_', ' ', $ob))); ?>

                        </span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                        <?php echo e($supplier->asl_review_due_at ? $supplier->asl_review_due_at->format('d/m/Y') : '—'); ?>

                    </td>
                    <td class="whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium space-x-2">
                        <a href="<?php echo e(route('suppliers.asl.review', $supplier)); ?>" class="text-primary-600 hover:text-primary-900">Review</a>
                        <?php if($supplier->asl_status === 'not_applied' || $supplier->asl_status === 'removed'): ?>
                            <form method="POST" action="<?php echo e(route('suppliers.asl.submit', $supplier)); ?>" class="inline">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="text-blue-600 hover:text-blue-900">Submit for Review</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="6" class="py-8 text-center text-sm text-gray-500">No suppliers found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-4"><?php echo e($suppliers->withQueryString()->links()); ?></div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\laragon\www\procurement\resources\views/suppliers/asl/index.blade.php ENDPATH**/ ?>