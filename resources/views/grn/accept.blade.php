@extends('layouts.app')

@section('title', 'Accept Delivery — GRN #' . $grn->grn_number)

@section('content')
<div class="space-y-6">
    {{-- Page Header --}}
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Accept Delivery</h1>
            <p class="mt-1 text-sm text-gray-600">
                GRN #{{ $grn->grn_number }}
                &mdash; {{ $grn->supplier->name }}
                &mdash; PO #{{ $grn->purchaseOrder->po_number ?? $grn->purchase_order_id }}
            </p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('grn.show', $grn) }}"
               class="inline-flex items-center rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                &larr; Back to GRN
            </a>
        </div>
    </div>

    {{-- GRN Summary --}}
    <div class="rounded-lg bg-white shadow ring-1 ring-gray-200 p-6">
        <h2 class="text-base font-semibold text-gray-900 mb-4">Delivery Summary</h2>
        <dl class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div>
                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Received Date</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900">
                    {{ $grn->received_date ? $grn->received_date->format('d M Y') : '—' }}
                </dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Qty Ordered</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900">{{ number_format($grn->total_quantity_ordered) }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Qty Received</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900">{{ number_format($grn->total_quantity_received) }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Acceptance Rate</dt>
                <dd class="mt-1 text-sm font-semibold
                    {{ $grn->acceptance_rate >= 95 ? 'text-green-700' : ($grn->acceptance_rate >= 75 ? 'text-yellow-700' : 'text-red-700') }}">
                    {{ number_format($grn->acceptance_rate, 1) }}%
                </dd>
            </div>
        </dl>

        @if ($grn->has_discrepancies)
            <div class="mt-4 rounded-md bg-yellow-50 border border-yellow-200 p-3 flex items-start gap-3">
                <svg class="w-5 h-5 text-yellow-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <p class="text-sm font-medium text-yellow-800">Discrepancies reported on this delivery.</p>
                    @if ($grn->discrepancy_details)
                        <p class="text-xs text-yellow-700 mt-1">{{ $grn->discrepancy_details }}</p>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- Items Table --}}
    <div class="rounded-lg bg-white shadow ring-1 ring-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-base font-semibold text-gray-900">Items Received</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Description</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wide">Ordered</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wide">Received</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wide">Accepted</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wide">Rejected</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Quality</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Batch / Serial</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($items as $item)
                        <tr class="{{ $item->hasRejections() ? 'bg-red-50' : '' }}">
                            <td class="px-4 py-3 text-gray-500">{{ $item->line_number ?? $loop->iteration }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $item->description }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">{{ number_format($item->quantity_ordered) }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">{{ number_format($item->quantity_received) }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-green-700">{{ number_format($item->quantity_accepted) }}</td>
                            <td class="px-4 py-3 text-right font-semibold {{ $item->hasRejections() ? 'text-red-700' : 'text-gray-400' }}">
                                {{ number_format($item->quantity_rejected) }}
                            </td>
                            <td class="px-4 py-3">
                                @php $qColor = $item->quality_check_passed ? 'green' : ($item->quality_check_passed === null ? 'gray' : 'red'); @endphp
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                    bg-{{ $qColor }}-100 text-{{ $qColor }}-800">
                                    {{ $item->quality_status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs">
                                @if ($item->batch_number)
                                    <span class="block">Batch: {{ $item->batch_number }}</span>
                                @endif
                                @if ($item->serial_number)
                                    <span class="block">S/N: {{ $item->serial_number }}</span>
                                @endif
                                @if ($item->expiry_date)
                                    <span class="block">Exp: {{ \Carbon\Carbon::parse($item->expiry_date)->format('d M Y') }}</span>
                                @endif
                                @if (!$item->batch_number && !$item->serial_number && !$item->expiry_date)
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">No items found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Acceptance Form --}}
    <div class="rounded-lg bg-white shadow ring-1 ring-gray-200 p-6">
        <h2 class="text-base font-semibold text-gray-900 mb-5">Acceptance Decision</h2>

        @if ($errors->any())
            <div class="mb-5 rounded-md bg-red-50 border border-red-200 p-4">
                <ul class="text-sm text-red-700 list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Accept form --}}
        <form action="{{ route('grn.accept', $grn) }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <fieldset>
                <legend class="text-sm font-semibold text-gray-700 mb-3">Decision</legend>
                <div class="space-y-3">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="radio" name="acceptance_decision" value="accepted"
                               class="mt-0.5 h-4 w-4 text-primary-600 border-gray-300 focus:ring-primary-500"
                               {{ old('acceptance_decision') === 'accepted' ? 'checked' : '' }} required>
                        <span>
                            <span class="block text-sm font-medium text-gray-900">Accept</span>
                            <span class="block text-xs text-gray-500">All items received are acceptable. Ready for invoice processing.</span>
                        </span>
                    </label>
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="radio" name="acceptance_decision" value="partially_accepted"
                               class="mt-0.5 h-4 w-4 text-primary-600 border-gray-300 focus:ring-primary-500"
                               {{ old('acceptance_decision') === 'partially_accepted' ? 'checked' : '' }}>
                        <span>
                            <span class="block text-sm font-medium text-gray-900">Partially Accept</span>
                            <span class="block text-xs text-gray-500">Some items are acceptable. Invoice will be raised for accepted quantities only.</span>
                        </span>
                    </label>
                </div>
            </fieldset>

            <div>
                <label for="acceptance_notes" class="block text-sm font-semibold text-gray-700 mb-1">
                    Notes <span class="text-gray-400 font-normal">(optional)</span>
                </label>
                <textarea id="acceptance_notes" name="acceptance_notes" rows="3"
                          class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                          placeholder="Any observations about the delivery condition...">{{ old('acceptance_notes') }}</textarea>
            </div>

            <div>
                <label for="completion_certificate" class="block text-sm font-semibold text-gray-700 mb-1">
                    Completion Certificate <span class="text-gray-400 font-normal">(for services / works — optional)</span>
                </label>
                <input type="file" id="completion_certificate" name="completion_certificate"
                       accept=".pdf,.jpg,.jpeg,.png"
                       class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                <p class="mt-1 text-xs text-gray-500">PDF, JPG or PNG — max 10 MB</p>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-md bg-green-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Confirm Acceptance
                </button>

                {{-- Reject button (separate form to keep validation clean) --}}
                <button type="button"
                        onclick="document.getElementById('reject-form').classList.toggle('hidden')"
                        class="inline-flex items-center gap-2 rounded-md bg-white px-4 py-2.5 text-sm font-semibold text-red-700 shadow-sm ring-1 ring-inset ring-red-300 hover:bg-red-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Reject Delivery
                </button>
            </div>
        </form>

        {{-- Reject form (hidden by default) --}}
        <div id="reject-form" class="hidden mt-6 pt-6 border-t border-red-100">
            <h3 class="text-sm font-semibold text-red-700 mb-3">Rejection Reason</h3>
            <form action="{{ route('grn.reject-acceptance', $grn) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="reject_notes" class="block text-sm font-semibold text-gray-700 mb-1">
                        Reason for rejection <span class="text-red-500">*</span>
                    </label>
                    <textarea id="reject_notes" name="acceptance_notes" rows="4" required minlength="10"
                              class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                              placeholder="Describe why the delivery is being rejected (minimum 10 characters)..."></textarea>
                </div>
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-md bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-600 transition-colors">
                    Confirm Rejection
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
