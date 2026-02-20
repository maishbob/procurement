@extends('layouts.app')

@section('title', 'Finance Requisition')

@section('content')
<div class="max-w-5xl mx-auto space-y-6 py-6">
    <form action="{{ route('requisitions.store') }}" 
          method="POST"
          enctype="multipart/form-data"
          x-data="requisitionForm()"
          x-init="init()">
        @csrf

        <!-- Header -->
        <div class="bg-white rounded-lg shadow-sm border-2 border-gray-300 p-8">
            <div class="text-center mb-8 border-b-2 border-gray-300 pb-6">
                <img src="/images/st_c_logo.png" alt="School Crest" class="h-20 mx-auto mb-4">
                <h1 class="text-2xl font-bold text-gray-900">FINANCE REQUISITION</h1>
            </div>

            <!-- Basic Details -->
            <div class="space-y-6">
                <!-- Date -->
                <div class="grid grid-cols-3 gap-4 items-center">
                    <label for="requisition_date" class="font-semibold text-gray-700">DATE:</label>
                    <input type="date" 
                           id="requisition_date" 
                           name="requisition_date" 
                           value="{{ date('Y-m-d') }}"
                           required
                           class="col-span-2 border-b-2 border-gray-400 focus:border-primary-600 focus:outline-none px-2 py-1">
                </div>

                <!-- Staff Member -->
                <div class="grid grid-cols-3 gap-4 items-center">
                    <label for="staff_member" class="font-semibold text-gray-700">Staff Member:</label>
                    <input type="text" 
                           id="staff_member" 
                           name="staff_member" 
                           value="{{ auth()->user()->name }}"
                           readonly
                           class="col-span-2 border-b-2 border-gray-400 bg-gray-50 px-2 py-1">
                </div>

                <!-- Subject/Area -->
                <div class="grid grid-cols-3 gap-4 items-center">
                    <label for="title" class="font-semibold text-gray-700">SUBJECT/Area:</label>
                    <input type="text" 
                           id="title" 
                           name="title" 
                           required
                           placeholder="e.g., Tent Hire, Office Supplies"
                           class="col-span-2 border-b-2 border-gray-400 focus:border-primary-600 focus:outline-none px-2 py-1">
                </div>

                <!-- Amount -->
                <div class="grid grid-cols-3 gap-4 items-center">
                    <label for="estimated_total" class="font-semibold text-gray-700">AMOUNT:</label>
                    <input type="number" 
                           id="estimated_total" 
                           name="estimated_total" 
                           step="0.01"
                           required
                           placeholder="0.00"
                           class="col-span-2 border-b-2 border-gray-400 focus:border-primary-600 focus:outline-none px-2 py-1 text-lg font-semibold">
                </div>

                <!-- Currency -->
                <div class="grid grid-cols-3 gap-4 items-center">
                    <label for="currency" class="font-semibold text-gray-700">Currency:</label>
                    <select id="currency" 
                            name="currency" 
                            required
                            class="col-span-2 border-b-2 border-gray-400 focus:border-primary-600 focus:outline-none px-2 py-1">
                        <option value="KES">KES - Kenyan Shilling</option>
                        <option value="USD">USD - US Dollar</option>
                        <option value="GBP">GBP - British Pound</option>
                        <option value="EUR">EUR - Euro</option>
                    </select>
                </div>

                <!-- Finance Level -->
                <div class="grid grid-cols-3 gap-4 items-center">
                    <label for="finance_level" class="font-semibold text-gray-700">Finance Level:</label>
                    <select id="finance_level" 
                            name="priority" 
                            required
                            class="col-span-2 border-b-2 border-gray-400 focus:border-primary-600 focus:outline-none px-2 py-1">
                        <option value="normal">Level 1 - Normal (Under 50,000)</option>
                        <option value="high">Level 2 - Medium (50,000 - 200,000)</option>
                        <option value="urgent">Level 3 - High (Over 200,000)</option>
                    </select>
                </div>

                <!-- Department -->
                <div class="grid grid-cols-3 gap-4 items-center">
                    <label for="department_id" class="font-semibold text-gray-700">Department:</label>
                    <select id="department_id" 
                            name="department_id" 
                            required
                            @change="fetchDepartmentBudgets($event.target.value)"
                            class="col-span-2 border-b-2 border-gray-400 focus:border-primary-600 focus:outline-none px-2 py-1">
                        <option value="">Select Department</option>
                        @foreach($departments ?? [] as $dept)
                        <option value="{{ $dept->id }}" {{ auth()->user()->department_id == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Budget Category -->
                <div class="grid grid-cols-3 gap-4 items-center" x-show="budgets.length > 0" x-transition>
                    <label for="budget_id" class="font-semibold text-gray-700">Budget Category:</label>
                    <select id="budget_id" 
                            name="budget_line_id" 
                            @change="selectBudget($event.target.value)"
                            class="col-span-2 border-b-2 border-gray-400 focus:border-primary-600 focus:outline-none px-2 py-1">
                        <option value="">Select Budget Category</option>
                        <template x-for="budget in budgets" :key="budget.id">
                            <option :value="budget.id" x-text="budget.name + ' (' + budget.category + ') - Available: ' + parseFloat(budget.available_amount).toFixed(2)"></option>
                        </template>
                    </select>
                </div>

                <!-- Budget Availability Warning -->
                <div x-show="budgetWarning" x-transition class="col-span-3 bg-yellow-50 border-2 border-yellow-400 rounded-lg p-4">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-yellow-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div>
                            <h4 class="font-semibold text-yellow-800">Insufficient Budget</h4>
                            <p class="text-sm text-yellow-700 mt-1" x-show="selectedBudget">
                                Available: <span x-text="selectedBudget ? parseFloat(selectedBudget.available_amount).toFixed(2) : '0.00'"></span><br>
                                Requested: <span x-text="parseFloat(document.getElementById('estimated_total').value || 0).toFixed(2)"></span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Budget Line ID (hidden) -->
                <input type="hidden" name="budget_line_id" :value="selectedBudget ? selectedBudget.id : ''">>

                <!-- Reason/Breakdown -->
                <div class="mt-6">
                    <label for="justification" class="font-semibold text-gray-700 block mb-2">
                        REASON (Should include breakdown of any amount requested):
                    </label>
                    <textarea id="justification" 
                              name="justification" 
                              rows="6"
                              required
                              placeholder="Provide detailed breakdown...&#10;Example:&#10;- Padel launch balance - Nov 2025 - 5,500/-&#10;- x2 Padel Festivals - U.98 + U.14s - 5,500/- x2"
                              class="w-full border-2 border-gray-400 rounded px-3 py-2 focus:border-primary-600 focus:outline-none"></textarea>
                </div>

                <!-- Supporting Documents -->
                <div class="mt-6 border-t-2 border-gray-300 pt-6">
                    <label class="font-semibold text-gray-700 block mb-3">
                        Supporting Documents (Optional):
                    </label>
                    <div class="space-y-3">
                        <div>
                            <input type="file" 
                                   name="supporting_documents[]" 
                                   accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                            <p class="mt-1 text-xs text-gray-500">PDF, Word, or Image files (Max 5MB each)</p>
                        </div>
                        <div>
                            <input type="file" 
                                   name="supporting_documents[]" 
                                   accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                        </div>
                        <div>
                            <input type="file" 
                                   name="supporting_documents[]" 
                                   accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                        </div>
                    </div>
                </div>

                <!-- Required By Date -->
                <div class="grid grid-cols-3 gap-4 items-center mt-6">
                    <label for="required_by_date" class="font-semibold text-gray-700">Required By:</label>
                    <input type="date" 
                           id="required_by_date" 
                           name="required_by_date" 
                           required
                           min="{{ date('Y-m-d') }}"
                           class="col-span-2 border-b-2 border-gray-400 focus:border-primary-600 focus:outline-none px-2 py-1">
                </div>

                <!-- Hidden fields for backend compatibility -->
                <input type="hidden" name="type" value="services">
                <input type="hidden" name="description" value="Finance requisition">
                <input type="hidden" name="delivery_location" value="School Campus">
                <input type="hidden" name="status" id="status_field" value="submitted">
            </div>

            <!-- Action Buttons -->
            <div class="mt-8 flex items-center justify-between pt-6 border-t-2 border-gray-300">
                <a href="{{ route('requisitions.index') }}" 
                   class="inline-flex items-center px-6 py-3 border-2 border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 font-semibold">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Cancel
                </a>
                <div class="flex gap-3">
                    <button type="button"
                            @click="document.getElementById('status_field').value='draft'; submitForm($el.closest('form'))"
                            class="inline-flex items-center px-6 py-3 border-2 border-gray-400 rounded-lg text-gray-700 bg-white hover:bg-gray-50 font-semibold">
                        Save as Draft
                    </button>
                    <button type="button"
                            @click="submitForm($el.closest('form'))"
                            class="inline-flex items-center px-6 py-3 bg-primary-600 rounded-lg text-white hover:bg-primary-700 font-semibold shadow-lg">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Submit Requisition
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function requisitionForm() {
    return {
        budgets: [],
        selectedBudget: null,
        loadingBudgets: false,
        budgetWarning: false,
        
        fetchDepartmentBudgets(departmentId) {
            if (!departmentId) {
                this.budgets = [];
                this.selectedBudget = null;
                this.budgetWarning = false;
                return;
            }

            this.loadingBudgets = true;
            
            fetch(`/api/departments/${departmentId}/budgets?fiscal_year={{ now()->year }}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                this.budgets = data.budgets || [];
                this.loadingBudgets = false;
                
                if (this.budgets.length === 0) {
                    alert('No approved budgets found for this department in the current fiscal year.');
                }
            })
            .catch(error => {
                console.error('Error fetching budgets:', error);
                this.loadingBudgets = false;
                this.budgets = [];
            });
        },
        
        selectBudget(budgetId) {
            this.selectedBudget = this.budgets.find(b => b.id == budgetId) || null;
            this.checkBudgetAvailability();
        },
        
        checkBudgetAvailability() {
            const amount = parseFloat(document.getElementById('estimated_total').value) || 0;
            
            if (this.selectedBudget && amount > 0) {
                this.budgetWarning = amount > parseFloat(this.selectedBudget.available_amount);
            } else {
                this.budgetWarning = false;
            }
        },
        
        submitForm(form) {
            // Check amount before submit
            this.checkBudgetAvailability();
            
            // Show confirmation if exceeding budget
            if (this.budgetWarning) {
                if (!confirm('The requested amount exceeds the available budget. Do you want to proceed anyway?')) {
                    return false;
                }
            }
            
            // Submit the form
            form.submit();
        },
        
        init() {
            // Add event listener for amount changes
            const amountInput = document.getElementById('estimated_total');
            if (amountInput) {
                amountInput.addEventListener('input', () => this.checkBudgetAvailability());
            }
            
            // Initialize budget checking when department is pre-selected
            const deptSelect = document.getElementById('department_id');
            if (deptSelect && deptSelect.value) {
                this.fetchDepartmentBudgets(deptSelect.value);
            }
        }
    };
}
</script>
@endsection
