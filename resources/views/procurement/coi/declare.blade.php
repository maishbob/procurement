@extends('layouts.app')

@section('title', 'Conflict of Interest Declaration')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 max-w-2xl">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Conflict of Interest Declaration</h1>
        <p class="mt-1 text-sm text-gray-500">
            This declaration is required before you may participate in the evaluation of this procurement process.
        </p>
    </div>

    {{-- Process summary --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <p class="text-sm font-semibold text-blue-900">{{ $process->title }}</p>
        <p class="text-xs text-blue-700 mt-1">
            Type: {{ strtoupper($process->type) }}
            &bull; Budget: KES {{ number_format($process->budget_allocation ?? 0, 2) }}
            @if($process->submission_deadline)
                &bull; Deadline: {{ \Carbon\Carbon::parse($process->submission_deadline)->format('d/m/Y') }}
            @endif
        </p>
    </div>

    @if($existing)
        <div class="mb-6 rounded-md {{ $existing->has_conflict ? 'bg-red-50 border border-red-200' : 'bg-green-50 border border-green-200' }} p-4">
            <p class="text-sm font-semibold {{ $existing->has_conflict ? 'text-red-800' : 'text-green-800' }}">
                You have already submitted a declaration for this process
                ({{ $existing->declared_at->format('d/m/Y H:i') }}).
            </p>
            <p class="mt-1 text-sm {{ $existing->has_conflict ? 'text-red-700' : 'text-green-700' }}">
                Declared: <strong>{{ $existing->has_conflict ? 'Conflict of interest exists' : 'No conflict of interest' }}</strong>
            </p>
            @if($existing->conflict_details)
                <p class="mt-1 text-sm text-red-700">Details: {{ $existing->conflict_details }}</p>
            @endif
            <p class="mt-2 text-xs text-gray-500">You may update your declaration below if circumstances have changed.</p>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-md bg-red-50 p-4"><p class="text-sm font-medium text-red-800">{{ session('error') }}</p></div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-md bg-red-50 p-4">
            <ul class="text-sm text-red-700 list-disc pl-4">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- Declaration form --}}
    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('procurement.coi.store', $process) }}" x-data="{ hasConflict: {{ old('has_conflict', $existing?->has_conflict ? '1' : '') ?: 'null' }} }">
            @csrf

            <fieldset class="mb-6">
                <legend class="text-sm font-semibold text-gray-900 mb-3">
                    Do you have a conflict of interest with any supplier participating in this process,
                    or with the process itself? <span class="text-red-500">*</span>
                </legend>

                <div class="space-y-3">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="radio" name="has_conflict" value="0"
                               @click="hasConflict = 0"
                               {{ old('has_conflict', $existing?->has_conflict === false ? '0' : '') === '0' ? 'checked' : '' }}
                               class="mt-0.5 h-4 w-4 text-primary-600 border-gray-300 focus:ring-primary-500" />
                        <div>
                            <p class="text-sm font-medium text-gray-900">No — I have no conflict of interest</p>
                            <p class="text-xs text-gray-500">
                                I confirm I have no financial, personal, or professional interest in the outcome
                                of this procurement process that could compromise my impartiality.
                            </p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="radio" name="has_conflict" value="1"
                               @click="hasConflict = 1"
                               {{ old('has_conflict', $existing?->has_conflict ? '1' : '') === '1' ? 'checked' : '' }}
                               class="mt-0.5 h-4 w-4 text-red-600 border-gray-300 focus:ring-red-500" />
                        <div>
                            <p class="text-sm font-medium text-gray-900">Yes — I have a conflict of interest</p>
                            <p class="text-xs text-gray-500">
                                I have an interest that may affect my ability to act impartially.
                                I will be recused from this evaluation.
                            </p>
                        </div>
                    </label>
                </div>
            </fieldset>

            {{-- Conflict details (shown only when conflict declared) --}}
            <div x-show="hasConflict === 1" x-cloak class="mb-6">
                <label for="conflict_details" class="block text-sm font-semibold text-gray-900 mb-1">
                    Please describe the nature of the conflict <span class="text-red-500">*</span>
                </label>
                <textarea name="conflict_details" id="conflict_details" rows="4"
                          placeholder="Describe your relationship or interest (e.g. family member employed by a bidding supplier, financial interest, prior business relationship...)"
                          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">{{ old('conflict_details', $existing?->conflict_details) }}</textarea>
            </div>

            {{-- Warning when conflict selected --}}
            <div x-show="hasConflict === 1" x-cloak class="mb-6 rounded-md bg-red-50 border border-red-200 p-4">
                <p class="text-sm font-semibold text-red-800">
                    Submitting this declaration will immediately recuse you from this evaluation.
                    The Procurement Manager will be notified.
                </p>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ url()->previous() }}"
                   class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" x-bind:disabled="hasConflict === null"
                        class="rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 disabled:opacity-50 disabled:cursor-not-allowed">
                    Submit Declaration
                </button>
            </div>
        </form>
    </div>

    <p class="mt-4 text-xs text-gray-400">
        This declaration is recorded under the Public Procurement and Asset Disposal Act (PPADA) and
        forms part of the immutable audit trail for this procurement process.
    </p>
</div>
@endsection
