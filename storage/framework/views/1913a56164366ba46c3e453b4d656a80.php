

<?php $__env->startSection('content'); ?>
<div class="container mx-auto p-4">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Annual Procurement Plans</h1>
        <a href="<?php echo e(route('annual-procurement-plans.create')); ?>" class="btn btn-primary">New Plan</a>
    </div>
    <table class="table-auto w-full bg-white shadow rounded">
        <thead>
            <tr>
                <th class="px-4 py-2">Fiscal Year</th>
                <th class="px-4 py-2">Description</th>
                <th class="px-4 py-2">Status</th>
                <th class="px-4 py-2">Submitted</th>
                <th class="px-4 py-2">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $plans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $plan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td class="border px-4 py-2"><?php echo e($plan->fiscal_year); ?></td>
                    <td class="border px-4 py-2"><?php echo e($plan->description); ?></td>
                    <td class="border px-4 py-2">
                        <span class="badge badge-<?php echo e($plan->status); ?>"><?php echo e(ucfirst($plan->status)); ?></span>
                    </td>
                    <td class="border px-4 py-2"><?php echo e($plan->submitted_at ? $plan->submitted_at->format('Y-m-d') : '-'); ?></td>
                    <td class="border px-4 py-2">
                        <a href="<?php echo e(route('annual-procurement-plans.show', $plan)); ?>" class="btn btn-sm btn-info">View</a>
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('update', $plan)): ?>
                            <a href="<?php echo e(route('annual-procurement-plans.edit', $plan)); ?>" class="btn btn-sm btn-warning">Edit</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="5" class="text-center py-4">No plans found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\laragon\www\procurement\resources\views/planning/index.blade.php ENDPATH**/ ?>