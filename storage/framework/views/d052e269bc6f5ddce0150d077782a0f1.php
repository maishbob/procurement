

<?php $__env->startSection('content'); ?>
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center gap-4">
            <a href="<?php echo e(route('inventory.index')); ?>" class="text-gray-500 hover:text-gray-700">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Add Inventory Item</h1>
                <p class="mt-1 text-sm text-gray-600">Create a new inventory item master record</p>
            </div>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-lg shadow">
        <form action="<?php echo e(route('inventory.store')); ?>" method="POST" class="p-6 space-y-6">
            <?php echo csrf_field(); ?>

            <?php if($errors->any()): ?>
            <div class="rounded-md bg-red-50 p-4 border border-red-200">
                <div class="flex">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"/>
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">There were errors with your submission</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <ul class="list-disc space-y-1 pl-5">
                                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li><?php echo e($error); ?></li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Basic Information -->
            <div class="border-b border-gray-200 pb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h2>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <!-- Item Code -->
                    <div>
                        <label for="item_code" class="block text-sm font-medium text-gray-700 mb-1">
                            Item Code <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="item_code" id="item_code" value="<?php echo e(old('item_code')); ?>"
                            placeholder="e.g., ITEM-001" required
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 <?php $__errorArgs = ['item_code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                        <?php $__errorArgs = ['item_code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- Item Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                            Item Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" id="name" value="<?php echo e(old('name')); ?>"
                            placeholder="e.g., Office Chair" required
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                        <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>

                <!-- Description -->
                <div class="mt-6">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" id="description" rows="3" 
                        placeholder="Optional item description..."
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"><?php echo e(old('description')); ?></textarea>
                </div>

                <!-- Category & UOM -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-6">
                    <!-- Category -->
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select name="category_id" id="category_id" 
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <option value="">-- Select Category --</option>
                            <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($category->id); ?>" <?php echo e(old('category_id') == $category->id ? 'selected' : ''); ?>>
                                <?php echo e($category->name); ?>

                            </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                    <!-- Unit of Measure -->
                    <div>
                        <label for="unit_of_measure" class="block text-sm font-medium text-gray-700 mb-1">
                            Unit of Measure <span class="text-red-500">*</span>
                        </label>
                        <select name="unit_of_measure" id="unit_of_measure" required
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 <?php $__errorArgs = ['unit_of_measure'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                            <option value="">-- Select UOM --</option>
                            <option value="PCS" <?php echo e(old('unit_of_measure') == 'PCS' ? 'selected' : ''); ?>>Pieces (PCS)</option>
                            <option value="BOX" <?php echo e(old('unit_of_measure') == 'BOX' ? 'selected' : ''); ?>>Box (BOX)</option>
                            <option value="PKT" <?php echo e(old('unit_of_measure') == 'PKT' ? 'selected' : ''); ?>>Packet (PKT)</option>
                            <option value="MTR" <?php echo e(old('unit_of_measure') == 'MTR' ? 'selected' : ''); ?>>Meter (MTR)</option>
                            <option value="KG" <?php echo e(old('unit_of_measure') == 'KG' ? 'selected' : ''); ?>>Kilogram (KG)</option>
                            <option value="LTR" <?php echo e(old('unit_of_measure') == 'LTR' ? 'selected' : ''); ?>>Liter (LTR)</option>
                            <option value="GAL" <?php echo e(old('unit_of_measure') == 'GAL' ? 'selected' : ''); ?>>Gallon (GAL)</option>
                        </select>
                        <?php $__errorArgs = ['unit_of_measure'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
            </div>

            <!-- Stock Control -->
            <div class="border-b border-gray-200 pb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Stock Control</h2>
                
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <!-- Reorder Point -->
                    <div>
                        <label for="reorder_point" class="block text-sm font-medium text-gray-700 mb-1">Reorder Point</label>
                        <input type="number" name="reorder_point" id="reorder_point" value="<?php echo e(old('reorder_point')); ?>"
                            placeholder="0" min="0" step="0.01"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <p class="mt-1 text-xs text-gray-500">Minimum stock level before reorder alert</p>
                    </div>

                    <!-- Minimum Stock Level -->
                    <div>
                        <label for="minimum_stock_level" class="block text-sm font-medium text-gray-700 mb-1">Minimum Stock</label>
                        <input type="number" name="minimum_stock_level" id="minimum_stock_level" value="<?php echo e(old('minimum_stock_level')); ?>"
                            placeholder="0" min="0" step="0.01"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <p class="mt-1 text-xs text-gray-500">Absolute minimum quantity</p>
                    </div>

                    <!-- Maximum Stock Level -->
                    <div>
                        <label for="maximum_stock_level" class="block text-sm font-medium text-gray-700 mb-1">Maximum Stock</label>
                        <input type="number" name="maximum_stock_level" id="maximum_stock_level" value="<?php echo e(old('maximum_stock_level')); ?>"
                            placeholder="0" min="0" step="0.01"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <p class="mt-1 text-xs text-gray-500">Maximum capacity</p>
                    </div>
                </div>
            </div>

            <!-- Pricing & Supplier -->
            <div class="border-b border-gray-200 pb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Pricing & Supplier</h2>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <!-- Standard Cost -->
                    <div>
                        <label for="standard_cost" class="block text-sm font-medium text-gray-700 mb-1">Standard Cost (KES)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-gray-600">KES</span>
                            <input type="number" name="standard_cost" id="standard_cost" value="<?php echo e(old('standard_cost')); ?>"
                                placeholder="0.00" min="0" step="0.01"
                                class="w-full pl-12 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        </div>
                    </div>

                    <!-- Preferred Supplier -->
                    <div>
                        <label for="preferred_supplier_id" class="block text-sm font-medium text-gray-700 mb-1">Preferred Supplier</label>
                        <select name="preferred_supplier_id" id="preferred_supplier_id"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <option value="">-- Select Supplier --</option>
                            <?php $__currentLoopData = $suppliers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $supplier): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($supplier->id); ?>" <?php echo e(old('preferred_supplier_id') == $supplier->id ? 'selected' : ''); ?>>
                                <?php echo e($supplier->name); ?>

                            </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Item Type -->
            <div class="pb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Item Type</h2>
                
                <div class="space-y-4">
                    <!-- Consumable -->
                    <label class="flex items-center">
                        <input type="checkbox" name="is_consumable" value="1" <?php echo e(old('is_consumable') ? 'checked' : ''); ?>

                            class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <span class="ml-3 text-sm text-gray-700">Consumable (Regular use item)</span>
                    </label>

                    <!-- Fixed Asset -->
                    <label class="flex items-center">
                        <input type="checkbox" name="is_asset" value="1" <?php echo e(old('is_asset') ? 'checked' : ''); ?>

                            class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <span class="ml-3 text-sm text-gray-700">Fixed Asset (Equipment/furniture)</span>
                    </label>

                    <!-- Vatable -->
                    <label class="flex items-center">
                        <input type="checkbox" name="is_vatable" value="1" <?php echo e(old('is_vatable') ? 'checked' : ''); ?>

                            class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <span class="ml-3 text-sm text-gray-700">Vatable (Subject to VAT)</span>
                    </label>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex gap-4 justify-end pt-6 border-t border-gray-200">
                <a href="<?php echo e(route('inventory.index')); ?>" class="px-6 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 rounded-md bg-primary-600 text-white hover:bg-primary-700 transition-colors">
                    Create Item
                </button>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\laragon\www\procurement\resources\views/inventory/create.blade.php ENDPATH**/ ?>