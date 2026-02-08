@extends('layouts.app')

@section('title', 'Edit GRN')

@section('content')
<div class="space-y-6">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold leading-tight text-gray-900">Edit Goods Receipt</h1>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-8">
        <form action="{{ route('grn.update', $grn) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            
            <!-- GRN Date -->
            <div>
                <label for="grn_date" class="block text-sm font-medium text-gray-700">Receipt Date</label>
                <input type="date" id="grn_date" name="grn_date" value="{{ $grn->received_date->format('Y-m-d') }}"
                    class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2">
            </div>

            <!-- Receiving Location -->
            <div>
                <label for="receiving_location" class="block text-sm font-medium text-gray-700">Receiving Location</label>
                <input type="text" id="receiving_location" name="receiving_location" value="{{ $grn->receiving_location }}"
                    class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2">
            </div>

            <!-- Remarks -->
            <div>
                <label for="remarks" class="block text-sm font-medium text-gray-700">Remarks</label>
                <textarea id="remarks" name="remarks" rows="4" class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2">{{ $grn->remarks }}</textarea>
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-3 pt-6 border-t">
                <a href="{{ route('grn.show', $grn) }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="inline-flex items-center justify-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500">Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endsection

