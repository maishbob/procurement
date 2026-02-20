@extends('layouts.app')

@section('title', 'Department Budgets - ' . $fiscalYear)

@section('content')
<div class="max-w-7xl mx-auto space-y-6 py-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Department Budgets</h1>
            <p class="mt-2 text-gray-600">Fiscal Year: <span class="font-semibold text-primary-600">{{ $fiscalYear }}</span></p>
        </div>
        <a href="{{ route('budgets.setup') }}" 
           class="px-4 py-2 border-2 border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-semibold">
            Back to Setup
        </a>
    </div>

    <form action="{{ route('budgets.store-department-budgets') }}" method="POST">
        @csrf
        <input type="hidden" name="fiscal_year" value="{{ $fiscalYear }}">

        <!-- Department Budgets Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Set Budget Allocation per Department</h2>
                <p class="text-sm text-gray-600 mt-1">Enter the total budget amount for each department</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Department</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Budget Category</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Allocated Amount (KES)</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Current Balance</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($departments as $dept)
                        @php
                            $existingBudget = $existingBudgets->where('department_id', $dept->id)->first();
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                {{ $dept->name }}
                                <input type="hidden" name="departments[{{ $dept->id }}][department_id]" value="{{ $dept->id }}">
                            </td>
                            <td class="px-6 py-4">
                                <select name="departments[{{ $dept->id }}][category]" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded focus:border-primary-500 focus:outline-none text-sm">
                                    <option value="operational" {{ ($existingBudget?->category ?? 'operational') == 'operational' ? 'selected' : '' }}>Operational</option>
                                    <option value="capital" {{ ($existingBudget?->category ?? '') == 'capital' ? 'selected' : '' }}>Capital</option>
                                    <option value="development" {{ ($existingBudget?->category ?? '') == 'development' ? 'selected' : '' }}>Development</option>
                                    <option value="emergency" {{ ($existingBudget?->category ?? '') == 'emergency' ? 'selected' : '' }}>Emergency</option>
                                </select>
                            </td>
                            <td class="px-6 py-4">
                                <input type="number" 
                                       name="departments[{{ $dept->id }}][allocated_amount]" 
                                       value="{{ $existingBudget?->allocated_amount ?? 0 }}"
                                       step="0.01"
                                       min="0"
                                       placeholder="0.00"
                                       class="w-full px-3 py-2 border border-gray-300 rounded focus:border-primary-500 focus:outline-none text-sm font-semibold">
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                @if($existingBudget)
                                    {{ number_format($existingBudget->available_amount, 2) }}
                                @else
                                    ---
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           name="departments[{{ $dept->id }}][is_active]" 
                                           value="1"
                                           {{ ($existingBudget?->is_active ?? true) ? 'checked' : '' }}
                                           class="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                    <span class="ml-2 text-sm text-gray-700">Active</span>
                                </label>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                        <tr>
                            <td colspan="2" class="px-6 py-4 text-right text-sm font-bold text-gray-900">
                                Total Budget:
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-lg font-bold text-primary-600" id="total-budget">0.00</span>
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Actions -->
            <div class="p-6 border-t border-gray-200 flex items-center justify-between bg-gray-50">
                <div class="text-sm text-gray-600">
                    <span class="font-medium">Note:</span> Amounts are in Kenyan Shillings (KES)
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('budgets.setup') }}" 
                       class="px-6 py-3 border-2 border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 font-semibold">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-semibold shadow-lg">
                        Save All Budgets
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    // Calculate total budget
    document.addEventListener('DOMContentLoaded', function() {
        const inputs = document.querySelectorAll('input[name*="[allocated_amount]"]');
        const totalElement = document.getElementById('total-budget');

        function updateTotal() {
            let total = 0;
            inputs.forEach(input => {
                const value = parseFloat(input.value) || 0;
                total += value;
            });
            totalElement.textContent = total.toLocaleString('en-KE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        inputs.forEach(input => {
            input.addEventListener('input', updateTotal);
        });

        updateTotal();
    });
</script>
@endpush
@endsection
