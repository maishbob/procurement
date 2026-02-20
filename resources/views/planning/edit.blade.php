@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4 max-w-xl">
    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <h1 class="text-2xl font-bold mb-4">Edit Annual Procurement Plan</h1>
    <form method="POST" action="{{ route('annual-procurement-plans.update', $annualProcurementPlan) }}">
        @csrf
        @method('PUT')
        <div class="mb-4">
            <label class="block font-semibold mb-1">Fiscal Year</label>
            <input type="text" name="fiscal_year" class="form-input w-full" value="{{ old('fiscal_year', $annualProcurementPlan->fiscal_year) }}" required>
        </div>
        <div class="mb-4">
            <label class="block font-semibold mb-1">Description</label>
            <textarea name="description" class="form-textarea w-full">{{ old('description', $annualProcurementPlan->description) }}</textarea>
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
                <tbody>
                @foreach($annualProcurementPlan->items as $item)
                    <tr>
                        <td><input type="text" name="items[][category]" class="form-input" value="{{ $item->category }}" required></td>
                        <td><input type="text" name="items[][description]" class="form-input" value="{{ $item->description }}" required></td>
                        <td><input type="text" name="items[][planned_quarter]" class="form-input" value="{{ $item->planned_quarter }}" required></td>
                        <td><input type="number" step="0.01" name="items[][estimated_value]" class="form-input" value="{{ $item->estimated_value }}" required></td>
                        <td><input type="text" name="items[][sourcing_method]" class="form-input" value="{{ $item->sourcing_method }}" required></td>
                        <td><button type="button" onclick="this.closest('tr').remove()" class="btn btn-xs btn-danger">Remove</button></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <button type="button" class="btn btn-sm btn-secondary" onclick="addItemRow()">Add Item</button>
        </div>
        <div class="flex justify-end">
            <button type="submit" class="btn btn-primary">Update Plan</button>
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
@endsection
