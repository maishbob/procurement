@extends('layouts.app')

@section('title', 'Edit ' . $capa->capa_number)

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-6 max-w-4xl">

    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit {{ $capa->capa_number }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $capa->title }}</p>
        </div>
        <a href="{{ route('capa.show', $capa) }}" class="mt-4 sm:mt-0 inline-flex rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">
            &larr; Back to CAPA
        </a>
    </div>

    @if($errors->any())
    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 p-4">
        <p class="text-sm font-semibold text-red-800 mb-1">Please fix the following errors:</p>
        <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('capa.update', $capa) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="rounded-lg bg-white shadow ring-1 ring-gray-200 p-6 space-y-5">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Details</h2>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" value="{{ old('title', $capa->title) }}" required maxlength="255"
                       class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Description <span class="text-red-500">*</span></label>
                <textarea name="description" rows="3" required
                          class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">{{ old('description', $capa->description) }}</textarea>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Priority <span class="text-red-500">*</span></label>
                    <select name="priority" required class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                        @foreach(['critical','high','medium','low'] as $p)
                        <option value="{{ $p }}" @selected(old('priority', $capa->priority) === $p)>{{ ucfirst($p) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Target Completion Date</label>
                    <input type="date" name="target_completion_date"
                           value="{{ old('target_completion_date', $capa->target_completion_date?->format('Y-m-d')) }}"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Assigned To</label>
                    <select name="assigned_to" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Unassigned</option>
                        @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected(old('assigned_to', $capa->assigned_to) == $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Department</label>
                    <select name="department_id" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">None</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" @selected(old('department_id', $capa->department_id) == $dept->id)>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Estimated Cost (KES)</label>
                <input type="number" name="estimated_cost" value="{{ old('estimated_cost', $capa->estimated_cost) }}" min="0" step="0.01"
                       class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
            </div>
        </div>

        <div class="rounded-lg bg-white shadow ring-1 ring-gray-200 p-6 space-y-5">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Problem Analysis & Actions</h2>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Problem Statement <span class="text-red-500">*</span></label>
                <textarea name="problem_statement" rows="3" required
                          class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">{{ old('problem_statement', $capa->problem_statement) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Root Cause Analysis</label>
                <textarea name="root_cause_analysis" rows="3"
                          class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">{{ old('root_cause_analysis', $capa->root_cause_analysis) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Immediate Action Taken</label>
                <textarea name="immediate_action_taken" rows="2"
                          class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">{{ old('immediate_action_taken', $capa->immediate_action_taken) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Proposed Action <span class="text-red-500">*</span></label>
                <textarea name="proposed_action" rows="3" required
                          class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">{{ old('proposed_action', $capa->proposed_action) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Implementation Plan</label>
                <textarea name="implementation_plan" rows="3"
                          class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">{{ old('implementation_plan', $capa->implementation_plan) }}</textarea>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="rounded-lg bg-primary-600 px-5 py-2 text-sm font-semibold text-white shadow hover:bg-primary-500">Save Changes</button>
            <a href="{{ route('capa.show', $capa) }}" class="rounded-lg bg-white px-5 py-2 text-sm font-semibold text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">Cancel</a>
        </div>
    </form>
</div>
@endsection
