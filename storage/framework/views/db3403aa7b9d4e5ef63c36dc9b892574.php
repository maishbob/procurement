

<?php $__env->startSection('title', 'Requisition Details'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-7xl mx-auto space-y-6">
    <!-- Page header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-x-3">
                <h1 class="text-3xl font-bold leading-tight text-gray-900"><?php echo e($requisition->requisition_number); ?></h1>
                <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium <?php echo e($requisition->status_color); ?>">
                    <?php echo e($requisition->status_label); ?>

                </span>
                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset <?php echo e($requisition->priority_color); ?>">
                    <?php echo e($requisition->priority_label); ?>

                </span>
            </div>
            <p class="mt-2 text-sm text-gray-700">Created <?php echo e($requisition->created_at->format('d M Y, H:i')); ?></p>
        </div>
        <div class="mt-4 flex gap-x-3 sm:ml-16 sm:mt-0">
            <a href="<?php echo e(route('requisitions.index')); ?>" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                Back to List
            </a>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('update', $requisition)): ?>
                <a href="<?php echo e(route('requisitions.edit', $requisition)); ?>" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit
                </a>
            <?php endif; ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('submit', $requisition)): ?>
                <form action="<?php echo e(route('requisitions.submit', $requisition)); ?>" method="POST" class="inline">
                    <?php echo csrf_field(); ?>
                    <button type="submit" 
                            onclick="return confirm('Submit this requisition for approval?')"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-500">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Submit for Approval
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabs -->
    <div x-data="{ activeTab: 'details' }" class="space-y-6">
        <!-- Tab navigation -->
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <button @click="activeTab = 'details'" 
                        :class="{ 'border-primary-500 text-primary-600': activeTab === 'details' }"
                        class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium">
                    Details
                </button>
                <button @click="activeTab = 'items'" 
                        :class="{ 'border-primary-500 text-primary-600': activeTab === 'items' }"
                        class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium">
                    Items (<?php echo e($requisition->items->count()); ?>)
                </button>
                <button @click="activeTab = 'approvals'" 
                        :class="{ 'border-primary-500 text-primary-600': activeTab === 'approvals' }"
                        class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium">
                    Approvals
                </button>
                <button @click="activeTab = 'history'" 
                        :class="{ 'border-primary-500 text-primary-600': activeTab === 'history' }"
                        class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium">
                    History
                </button>
            </nav>
        </div>

        <!-- Details Tab -->
        <div x-show="activeTab === 'details'" class="space-y-6">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Main details -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Basic Information -->
                    <div class="rounded-lg bg-white shadow">
                        <div class="border-b border-gray-200 px-6 py-4">
                            <h3 class="text-lg font-medium text-gray-900">Basic Information</h3>
                        </div>
                        <div class="px-6 py-5">
                            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Department</dt>
                                    <dd class="mt-1 text-sm text-gray-900"><?php echo e($requisition->department->name); ?></dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Requested By</dt>
                                    <dd class="mt-1 text-sm text-gray-900"><?php echo e($requisition->requester?->name ?? 'N/A'); ?></dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Required By Date</dt>
                                    <dd class="mt-1 text-sm text-gray-900"><?php echo e(\Carbon\Carbon::parse($requisition->required_by_date)->format('d M Y')); ?></dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Currency</dt>
                                    <dd class="mt-1 text-sm text-gray-900"><?php echo e($requisition->currency); ?></dd>
                                </div>
                                <div class="sm:col-span-2">
                                    <dt class="text-sm font-medium text-gray-500">Subject/Purpose</dt>
                                    <dd class="mt-1 text-sm text-gray-900"><?php echo e($requisition->title ?? $requisition->purpose ?? 'N/A'); ?></dd>
                                </div>
                                <div class="sm:col-span-2">
                                    <dt class="text-sm font-medium text-gray-500">Justification</dt>
                                    <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap"><?php echo e($requisition->justification); ?></dd>
                                </div>
                                <?php if($requisition->budget_line_id): ?>
                                <div class="sm:col-span-2">
                                    <dt class="text-sm font-medium text-gray-500">Budget Line</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <?php echo e($requisition->budgetLine?->budget_code ?? 'N/A'); ?> - <?php echo e($requisition->budgetLine?->description ?? 'N/A'); ?>

                                    </dd>
                                </div>
                                <?php endif; ?>
                            </dl>
                        </div>
                    </div>

                    <!-- Special Flags -->
                    <?php if($requisition->is_emergency || $requisition->is_single_source): ?>
                    <div class="rounded-lg bg-yellow-50 border border-yellow-200 p-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">Special Procurement Flags</h3>
                                <div class="mt-2 text-sm text-yellow-700 space-y-2">
                                    <?php if($requisition->is_emergency): ?>
                                    <div>
                                        <p class="font-medium">Emergency Procurement</p>
                                        <p><?php echo e($requisition->emergency_justification); ?></p>
                                    </div>
                                    <?php endif; ?>
                                    <?php if($requisition->is_single_source): ?>
                                    <div>
                                        <p class="font-medium">Single Source</p>
                                        <p><?php echo e($requisition->single_source_justification); ?></p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Financial Summary -->
                    <div class="rounded-lg bg-white shadow overflow-hidden">
                        <div class="border-b border-gray-200 px-6 py-4">
                            <h3 class="text-lg font-medium text-gray-900">Financial Summary</h3>
                        </div>
                        <div class="px-6 py-5 space-y-4">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Subtotal:</span>
                                <span class="text-sm font-medium text-gray-900"><?php echo e($requisition->formatted_estimated_total); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">VAT:</span>
                                <span class="text-sm font-medium text-gray-900">
                                    <?php echo e(number_format($requisition->items->sum(fn($item) => $item->is_vatable ? $item->estimated_total_price * 0.16 : 0), 2)); ?>

                                </span>
                            </div>
                            <div class="border-t border-gray-200 pt-4">
                                <div class="flex justify-between">
                                    <span class="text-base font-medium text-gray-900">Grand Total:</span>
                                    <span class="text-base font-semibold text-primary-600">
                                        <?php echo e($requisition->currency); ?> <?php echo e(number_format($requisition->estimated_total + $requisition->items->sum(fn($item) => $item->is_vatable ? $item->estimated_total_price * 0.16 : 0), 2)); ?>

                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Approval Requirements -->
                    <div class="rounded-lg bg-white shadow overflow-hidden">
                        <div class="border-b border-gray-200 px-6 py-4">
                            <h3 class="text-lg font-medium text-gray-900">Approval Requirements</h3>
                        </div>
                        <div class="px-6 py-5">
                            <ul class="space-y-3">
                                <li class="flex items-center">
                                    <svg class="h-5 w-5 <?php echo e($requisition->requires_hod_approval ? 'text-green-500' : 'text-gray-300'); ?>" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="ml-2 text-sm text-gray-700">HOD Approval</span>
                                </li>
                                <li class="flex items-center">
                                    <svg class="h-5 w-5 <?php echo e($requisition->requires_principal_approval ? 'text-green-500' : 'text-gray-300'); ?>" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="ml-2 text-sm text-gray-700">Principal Approval</span>
                                </li>
                                <li class="flex items-center">
                                    <svg class="h-5 w-5 <?php echo e($requisition->requires_board_approval ? 'text-green-500' : 'text-gray-300'); ?>" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="ml-2 text-sm text-gray-700">Board Approval</span>
                                </li>
                                <li class="flex items-center">
                                    <svg class="h-5 w-5 <?php echo e($requisition->requires_tender ? 'text-green-500' : 'text-gray-300'); ?>" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="ml-2 text-sm text-gray-700">Tender Required</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Supporting Documents -->
                    <?php if($requisition->supporting_documents && count($requisition->supporting_documents) > 0): ?>
                    <div class="rounded-lg bg-white shadow overflow-hidden">
                        <div class="border-b border-gray-200 px-6 py-4">
                            <h3 class="text-lg font-medium text-gray-900">Supporting Documents</h3>
                        </div>
                        <div class="px-6 py-5">
                            <ul class="space-y-3">
                                <?php $__currentLoopData = $requisition->supporting_documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li class="flex items-center justify-between py-2">
                                    <div class="flex items-center min-w-0 flex-1">
                                        <svg class="h-5 w-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                        <span class="ml-2 text-sm text-gray-700 truncate"><?php echo e($doc['name']); ?></span>
                                    </div>
                                    <a href="<?php echo e(asset('storage/' . $doc['path'])); ?>" 
                                       target="_blank"
                                       class="ml-3 flex-shrink-0 text-primary-600 hover:text-primary-900">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                    </a>
                                </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Items Tab -->
        <div x-show="activeTab === 'items'" class="rounded-lg bg-white shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Specifications</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">VAT</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php $__currentLoopData = $requisition->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo e($loop->iteration); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo e($item->description); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate"><?php echo e($item->specifications); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right"><?php echo e($item->quantity); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo e($item->unit_of_measure); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right"><?php echo e(number_format($item->estimated_unit_price, 2)); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 text-right"><?php echo e(number_format($item->estimated_total_price, 2)); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <?php if($item->is_vatable): ?>
                                    <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-green-100 text-green-700">16%</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-right text-sm font-medium text-gray-900">Total:</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right"><?php echo e($requisition->formatted_estimated_total); ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Approvals Tab -->
        <div x-show="activeTab === 'approvals'" class="space-y-6">
            <!-- Approval Form -->
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('approve', $requisition)): ?>
                <div class="rounded-lg bg-primary-50 border border-primary-200 p-6">
                    <h3 class="text-lg font-medium text-primary-900 mb-4">Take Action</h3>
                    <form action="<?php echo e(route('requisitions.approve', $requisition)); ?>" method="POST" class="space-y-4">
                        <?php echo csrf_field(); ?>
                        <div>
                            <label for="level" class="block text-sm font-medium text-gray-700">Approval Level</label>
                            <select name="level" id="level" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                <option value="hod">Head of Department</option>
                                <option value="principal">Principal</option>
                                <option value="board">Board</option>
                            </select>
                        </div>
                        <div>
                            <label for="comments" class="block text-sm font-medium text-gray-700">Comments</label>
                            <textarea name="comments" id="comments" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"></textarea>
                        </div>
                        <div class="flex gap-x-3">
                            <button type="submit" class="inline-flex items-center rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                </svg>
                                Approve
                            </button>
                            <button type="button" 
                                    onclick="document.getElementById('reject-form').classList.remove('hidden')"
                                    class="inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                                </svg>
                                Reject
                            </button>
                        </div>
                    </form>

                    <!-- Reject Form (hidden by default) -->
                    <form id="reject-form" action="<?php echo e(route('requisitions.reject', $requisition)); ?>" method="POST" class="mt-4 hidden border-t border-primary-200 pt-4">
                        <?php echo csrf_field(); ?>
                        <div class="space-y-4">
                            <div>
                                <label for="reject_level" class="block text-sm font-medium text-gray-700">Approval Level</label>
                                <select name="level" id="reject_level" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                    <option value="hod">Head of Department</option>
                                    <option value="principal">Principal</option>
                                    <option value="board">Board</option>
                                </select>
                            </div>
                            <div>
                                <label for="reason" class="block text-sm font-medium text-gray-700">Rejection Reason *</label>
                                <textarea name="reason" id="reason" rows="3" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"></textarea>
                            </div>
                            <button type="submit" class="inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                                Confirm Rejection
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Approval History -->
            <div class="rounded-lg bg-white shadow">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-medium text-gray-900">Approval History</h3>
                </div>
                <div class="px-6 py-5">
                    <?php if($requisition->approvals->count() > 0): ?>
                        <div class="flow-root">
                            <ul role="list" class="-mb-8">
                                <?php $__currentLoopData = $requisition->approvals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $approval): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li>
                                    <div class="relative pb-8">
                                        <?php if(!$loop->last): ?>
                                        <span class="absolute left-5 top-5 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                        <?php endif; ?>
                                        <div class="relative flex items-start space-x-3">
                                            <div class="relative">
                                                <div class="flex h-10 w-10 items-center justify-center rounded-full <?php echo e($approval->status === 'approved' ? 'bg-green-500' : ($approval->status === 'rejected' ? 'bg-red-500' : 'bg-gray-400')); ?> ring-8 ring-white">
                                                    <?php if($approval->status === 'approved'): ?>
                                                        <svg class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                                        </svg>
                                                    <?php elseif($approval->status === 'rejected'): ?>
                                                        <svg class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor">
                                                            <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                                        </svg>
                                                    <?php else: ?>
                                                        <svg class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd" />
                                                        </svg>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div>
                                                    <div class="text-sm">
                                                        <span class="font-medium text-gray-900"><?php echo e($approval->level_label); ?></span>
                                                    </div>
                                                    <p class="mt-0.5 text-sm text-gray-500">
                                                        <?php echo e($approval->approver->full_name); ?>

                                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium <?php echo e($approval->status_color); ?> ml-2">
                                                            <?php echo e($approval->status_label); ?>

                                                        </span>
                                                    </p>
                                                </div>
                                                <?php if($approval->comments): ?>
                                                <div class="mt-2 text-sm text-gray-700">
                                                    <p><?php echo e($approval->comments); ?></p>
                                                </div>
                                                <?php endif; ?>
                                                <?php if($approval->responded_at): ?>
                                                <div class="mt-2 text-xs text-gray-500">
                                                    <?php echo e($approval->responded_at->format('d M Y, H:i')); ?>

                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                    <?php else: ?>
                        <p class="text-sm text-gray-500">No approval actions yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- History Tab -->
        <div x-show="activeTab === 'history'" class="rounded-lg bg-white shadow">
            <div class="border-b border-gray-200 px-6 py-4">
                <h3 class="text-lg font-medium text-gray-900">Activity History</h3>
            </div>
            <div class="px-6 py-5">
                <div class="flow-root">
                    <ul role="list" class="-mb-8">
                        <li>
                            <div class="relative pb-8">
                                <span class="absolute left-5 top-5 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                <div class="relative flex items-start space-x-3">
                                    <div>
                                        <div class="relative px-1">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 ring-8 ring-white">
                                                <svg class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1 py-1.5">
                                        <div class="text-sm text-gray-500">
                                            <span class="font-medium text-gray-900"><?php echo e($requisition->requester->full_name); ?></span>
                                            <?php echo ' created the requisition'; ?>

                                            <span class="whitespace-nowrap"><?php echo e($requisition->created_at->diffForHumans()); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <?php if($requisition->submitted_at): ?>
                        <li>
                            <div class="relative pb-8">
                                <div class="relative flex items-start space-x-3">
                                    <div>
                                        <div class="relative px-1">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-100 ring-8 ring-white">
                                                <svg class="h-5 w-5 text-primary-500" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M3 4a2 2 0 00-2 2v1.161l8.441 4.221a1.25 1.25 0 001.118 0L19 7.162V6a2 2 0 00-2-2H3z" />
                                                    <path d="M19 8.839l-7.77 3.885a2.75 2.75 0 01-2.46 0L1 8.839V14a2 2 0 002 2h14a2 2 0 002-2V8.839z" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1 py-1.5">
                                        <div class="text-sm text-gray-500">
                                            <?php echo 'Requisition submitted for approval'; ?>

                                            <span class="whitespace-nowrap"><?php echo e(\Carbon\Carbon::parse($requisition->submitted_at)->diffForHumans()); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\laragon\www\procurement\resources\views/requisitions/show.blade.php ENDPATH**/ ?>