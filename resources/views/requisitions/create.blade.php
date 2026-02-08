@extends('layouts.app')

@section('title', isset($requisition) ? 'Edit Requisition' : 'Create Requisition')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <!-- Page header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold leading-tight text-gray-900">
                {{ isset($requisition) ? 'Edit Requisition' : 'Create New Requisition' }}
            </h1>
            <p class="mt-2 text-sm text-gray-700">Fill in the details below to create a purchase requisition</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0">
            <a href="{{ route('requisitions.index') }}" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to List
            </a>
        </div>
    </div>

    <form action="{{ isset($requisition) ? route('requisitions.update', $requisition) : route('requisitions.store') }}" 
          method="POST"
          x-data="requisitionForm()"
          @submit="loading = true">
        @csrf
        @if(isset($requisition))
            @method('PUT')
        @endif

        <!-- Basic Information -->
        <div class="rounded-lg bg-white shadow">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-medium text-gray-900">Basic Information</h2>
            </div>
            <div class="px-6 py-5 space-y-6">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <!-- Department -->
                    <div>
                        <label for="department_id" class="block text-sm font-medium text-gray-700">
                            Department <span class="text-red-500">*</span>
                        </label>
                        <select id="department_id" 
                                name="department_id" 
                                required
                                class="mt-1 block w-full rounded-md border shadow-sm focus:border-primary-500 focus:ring-primary-500 py-2 px-3 text-base @error('department_id') border-red-300 @else border-gray-300 @enderror">
                            <option value="">Select Department</option>
                            @foreach($departments ?? [] as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id', $requisition->department_id ?? auth()->user()->department_id) == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('department_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Priority -->
                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-700">
                            Priority <span class="text-red-500">*</span>
                        </label>
                        <select id="priority" 
                                name="priority" 
                                x-model="priority"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 py-2 px-3 text-base">
                            <option value="low">Low</option>
                            <option value="normal" selected>Normal</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>

                    <!-- Currency -->
                    <div>
                        <label for="currency" class="block text-sm font-medium text-gray-700">
                            Currency <span class="text-red-500">*</span>
                        </label>
                        <select id="currency" 
                                name="currency" 
                                x-model="currency"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 py-2 px-3 text-base">
                            <option value="KES" selected>KES - Kenyan Shilling</option>
                            <option value="USD">USD - US Dollar</option>
                            <option value="GBP">GBP - British Pound</option>
                            <option value="EUR">EUR - Euro</option>
                        </select>
                    </div>

                    <!-- Required By Date -->
                    <div>
                        <label for="required_by_date" class="block text-sm font-medium text-gray-700">
                            Required By Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" 
                               id="required_by_date" 
                               name="required_by_date" 
                               value="{{ old('required_by_date', $requisition->required_by_date ?? '') }}"
                               min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                               required
                               class="mt-1 block w-full sm:w-auto rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 py-2 px-3 text-base">
                    </div>
                </div>

                <!-- Purpose -->
                <div>
                    <label for="purpose" class="block text-sm font-medium text-gray-700">
                        Purpose <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="purpose" 
                           name="purpose" 
                           value="{{ old('purpose', $requisition->purpose ?? '') }}"
                           required
                           maxlength="500"
                           placeholder="Brief description of what you're requisitioning"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 py-2 px-3 text-base">
                    @error('purpose')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Justification -->
                <div>
                    <label for="justification" class="block text-sm font-medium text-gray-700">
                        Justification <span class="text-red-500">*</span>
                    </label>
                    <textarea id="justification" 
                              name="justification" 
                              rows="3" 
                              required
                              placeholder="Provide detailed justification for this requisition"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 py-2 px-3 text-base">{{ old('justification', $requisition->justification ?? '') }}</textarea>
                    @error('justification')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Budget Line -->
                <div>
                    <label for="budget_line_id" class="block text-sm font-medium text-gray-700">
                        Budget Line (Optional)
                    </label>
                    <select id="budget_line_id" 
                            name="budget_line_id" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 py-2 px-3 text-base">
                        <option value="">Select Budget Line</option>
                        @foreach($budgetLines ?? [] as $budget)
                        <option value="{{ $budget->id }}" {{ old('budget_line_id', $requisition->budget_line_id ?? '') == $budget->id ? 'selected' : '' }}>
                            {{ $budget->code }} - {{ $budget->name }} (Available: {{ number_format($budget->available_amount, 2) }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Special Flags -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <!-- Emergency Procurement -->
                    <div class="flex items-start">
                        <div class="flex h-5 items-center">
                            <input id="is_emergency" 
                                   name="is_emergency" 
                                   type="checkbox" 
                                   x-model="isEmergency"
                                   value="1"
                                   {{ old('is_emergency', $requisition->is_emergency ?? false) ? 'checked' : '' }}
                                   class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="is_emergency" class="font-medium text-gray-700">Emergency Procurement</label>
                            <p class="text-gray-500">This is an urgent emergency purchase</p>
                        </div>
                    </div>

                    <!-- Single Source -->
                    <div class="flex items-start">
                        <div class="flex h-5 items-center">
                            <input id="is_single_source" 
                                   name="is_single_source" 
                                   type="checkbox" 
                                   x-model="isSingleSource"
                                   value="1"
                                   {{ old('is_single_source', $requisition->is_single_source ?? false) ? 'checked' : '' }}
                                   class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="is_single_source" class="font-medium text-gray-700">Single Source</label>
                            <p class="text-gray-500">Requesting from a specific supplier</p>
                        </div>
                    </div>
                </div>

                <!-- Emergency Justification -->
                <div x-show="isEmergency" x-cloak x-transition>
                    <label for="emergency_justification" class="block text-sm font-medium text-gray-700">
                        Emergency Justification <span class="text-red-500">*</span>
                    </label>
                    <textarea id="emergency_justification" 
                              name="emergency_justification" 
                              rows="2" 
                              :required="isEmergency"
                              placeholder="Explain why this is an emergency procurement"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 py-2 px-3 text-base">{{ old('emergency_justification', $requisition->emergency_justification ?? '') }}</textarea>
                </div>

                <!-- Single Source Justification -->
                <div x-show="isSingleSource" x-cloak x-transition>
                    <label for="single_source_justification" class="block text-sm font-medium text-gray-700">
                        Single Source Justification <span class="text-red-500">*</span>
                    </label>
                    <textarea id="single_source_justification" 
                              name="single_source_justification" 
                              rows="2" 
                              :required="isSingleSource"
                              placeholder="Explain why this must be from a single source"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 py-2 px-3 text-base">{{ old('single_source_justification', $requisition->single_source_justification ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <!-- Requisition Items -->
        <div class="rounded-lg bg-white shadow">
            <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                <h2 class="text-lg font-medium text-gray-900">Requisition Items</h2>
                <button type="button" 
                        @click="addItem()"
                        class="inline-flex items-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500">
                    <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                    </svg>
                    Add Item
                </button>
            </div>
            <div class="px-6 py-5">
                <div class="space-y-4">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="rounded-lg border border-gray-300 p-4 hover:border-primary-300 transition-colors">
                            <div class="flex items-start justify-between mb-4">
                                <h3 class="text-sm font-medium text-gray-900">Item <span x-text="index + 1"></span></h3>
                                <button type="button" 
                                        @click="removeItem(index)"
                                        x-show="items.length > 1"
                                        class="text-red-600 hover:text-red-900">
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                    </svg>
                                </button>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <!-- Description -->
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">
                                        Description <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" 
                                           :name="'items['+index+'][description]'" 
                                           x-model="item.description"
                                           required
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 py-2 px-3 text-base">
                                </div>

                                <!-- Specifications -->
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Specifications</label>
                                    <textarea :name="'items['+index+'][specifications]'" 
                                              x-model="item.specifications"
                                              rows="2"
                                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 py-2 px-3 text-base"></textarea>
                                </div>

                                <!-- Quantity -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">
                                        Quantity <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" 
                                           :name="'items['+index+'][quantity]'" 
                                           x-model="item.quantity"
                                           @input="calculateItemTotal(index)"
                                           min="1"
                                           required
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 py-2 px-3 text-base">
                                </div>

                                <!-- Unit of Measure -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">
                                        Unit <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" 
                                           :name="'items['+index+'][unit_of_measure]'" 
                                           x-model="item.unit_of_measure"
                                           required
                                           placeholder="e.g., Pcs, Kg, Liters"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 py-2 px-3 text-base">
                                </div>

                                <!-- Unit Price -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">
                                        Estimated Unit Price <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" 
                                           :name="'items['+index+'][estimated_unit_price]'" 
                                           x-model="item.unit_price"
                                           @input="calculateItemTotal(index)"
                                           step="0.01"
                                           min="0"
                                           required
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 py-2 px-3 text-base">
                                </div>

                                <!-- Total -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Total</label>
                                    <div class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-900">
                                        <span x-text="formatCurrency(item.total)"></span>
                                    </div>
                                </div>

                                <!-- VAT -->
                                <div class="sm:col-span-2">
                                    <div class="flex items-center">
                                        <input :id="'vat_'+index" 
                                               :name="'items['+index+'][is_vatable]'" 
                                               type="checkbox" 
                                               x-model="item.is_vatable"
                                               @change="calculateItemTotal(index)"
                                               value="1"
                                               class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                        <label :for="'vat_'+index" class="ml-2 block text-sm text-gray-700">
                                            Subject to VAT (16%)
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Grand Total -->
                <div class="mt-6 border-t border-gray-200 pt-4">
                    <div class="flex justify-end">
                        <div class="w-64 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Subtotal:</span>
                                <span class="font-medium" x-text="formatCurrency(subtotal)"></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">VAT (16%):</span>
                                <span class="font-medium" x-text="formatCurrency(vatAmount)"></span>
                            </div>
                            <div class="flex justify-between text-base font-semibold border-t border-gray-200 pt-2">
                                <span class="text-gray-900">Grand Total:</span>
                                <span class="text-primary-600" x-text="formatCurrency(grandTotal)"></span>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="estimated_total" :value="grandTotal">
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-end gap-x-3 rounded-lg bg-white shadow px-6 py-4">
            <a href="{{ route('requisitions.index') }}" 
               class="rounded-md px-3.5 py-2.5 text-sm font-semibold text-gray-900 hover:bg-gray-50 ring-1 ring-inset ring-gray-300">
                Cancel
            </a>
            <button type="submit" 
                    :disabled="loading"
                    class="inline-flex items-center rounded-md bg-primary-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!loading">
                    {{ isset($requisition) ? 'Update Requisition' : 'Create Requisition' }}
                </span>
                <span x-show="loading" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Processing...
                </span>
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function requisitionForm() {
    return {
        loading: false,
        priority: '{{ old('priority', $requisition->priority ?? 'normal') }}',
        currency: '{{ old('currency', $requisition->currency ?? 'KES') }}',
        isEmergency: {{ old('is_emergency', $requisition->is_emergency ?? false) ? 'true' : 'false' }},
        isSingleSource: {{ old('is_single_source', $requisition->is_single_source ?? false) ? 'true' : 'false' }},
        items: [
            @if(isset($requisition) && $requisition->items->count() > 0)
                @foreach($requisition->items as $item)
                {
                    description: '{{ $item->description }}',
                    specifications: '{{ $item->specifications }}',
                    quantity: {{ $item->quantity }},
                    unit_of_measure: '{{ $item->unit_of_measure }}',
                    unit_price: {{ $item->estimated_unit_price }},
                    is_vatable: {{ $item->is_vatable ? 'true' : 'false' }},
                    total: {{ $item->estimated_total_price }}
                },
                @endforeach
            @else
                {
                    description: '',
                    specifications: '',
                    quantity: 1,
                    unit_of_measure: '',
                    unit_price: 0,
                    is_vatable: true,
                    total: 0
                }
            @endif
        ],
        
        get subtotal() {
            return this.items.reduce((sum, item) => sum + (parseFloat(item.total) || 0), 0);
        },
        
        get vatAmount() {
            return this.items.reduce((sum, item) => {
                if (item.is_vatable) {
                    return sum + ((parseFloat(item.total) || 0) * 0.16);
                }
                return sum;
            }, 0);
        },
        
        get grandTotal() {
            return this.subtotal + this.vatAmount;
        },
        
        addItem() {
            this.items.push({
                description: '',
                specifications: '',
                quantity: 1,
                unit_of_measure: '',
                unit_price: 0,
                is_vatable: true,
                total: 0
            });
        },
        
        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
            }
        },
        
        calculateItemTotal(index) {
            const item = this.items[index];
            item.total = (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0);
        },
        
        formatCurrency(amount) {
            return new Intl.NumberFormat('en-KE', {
                style: 'currency',
                currency: this.currency
            }).format(amount || 0);
        }
    }
}
</script>
@endpush
@endsection

