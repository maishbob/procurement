

<?php $__env->startSection('content'); ?>
<div class="container mx-auto p-4 max-w-xl">
    <?php if($errors->any()): ?>
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
            <ul class="list-disc pl-5">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>
    <h1 class="text-2xl font-bold mb-4">New Annual Procurement Plan</h1>
    <form method="POST" action="<?php echo e(route('annual-procurement-plans.store')); ?>">
        <?php echo csrf_field(); ?>
        <div class="mb-4">
            <label class="block font-semibold mb-1">Fiscal Year</label>
            <input type="text" name="fiscal_year" class="form-input w-full" required>
        </div>
        <div class="mb-4">
            <label class="block font-semibold mb-1">Description</label>
            <textarea name="description" class="form-textarea w-full"></textarea>
        </div>
        <div class="mb-4">
            <label class="block font-semibold mb-1">Line Items</label>
            <table class="table-auto w-full mb-2" id="items-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Planned Quarter</th>
                        <th>Estimated Value</th>
                        <th>Sourcing Method</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            <button type="button" class="btn btn-sm btn-secondary" onclick="addItemRow()">Add Item</button>
        </div>
        <div class="flex justify-end">
            <button type="submit" class="btn btn-primary">Create Plan</button>
        </div>
    </form>
</div>
<script>
function addItemRow() {
    const table = document.getElementById('items-table').getElementsByTagName('tbody')[0];
    const row = table.insertRow();
    row.innerHTML = `
        <td><input type="text" name="items[][category]" class="form-input" required></td>
        <td><input type="text" name="items[][description]" class="form-input" required></td>
        <td><input type="text" name="items[][planned_quarter]" class="form-input" required></td>
        <td><input type="number" step="0.01" name="items[][estimated_value]" class="form-input" required></td>
        <td><input type="text" name="items[][sourcing_method]" class="form-input" required></td>
        <td><button type="button" onclick="this.closest('tr').remove()" class="btn btn-xs btn-danger">Remove</button></td>
    `;
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\laragon\www\procurement\resources\views/planning/create.blade.php ENDPATH**/ ?>