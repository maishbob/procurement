@extends('layouts.app')

@section('title', 'Requisition Approvals — ' . $requisition->requisition_number)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    {{-- Page header --}}
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Approval History</h1>
            <p class="mt-1 text-sm text-gray-600">
                {{ $requisition->requisition_number }} &mdash; {{ $requisition->title ?? 'Requisition' }}
            </p>
        </div>
        <a href="{{ route('requisitions.show', $requisition) }}"
           class="mt-4 sm:mt-0 inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
            &larr; Back to Requisition
        </a>
    </div>

    {{-- Current Status --}}
    <div class="bg-white rounded-lg shadow p-5 flex items-center gap-4">
        <div class="flex-1">
            <p class="text-sm text-gray-500">Current Status</p>
            <p class="text-lg font-semibold text-gray-900 capitalize">
                {{ str_replace('_', ' ', $requisition->status) }}
            </p>
        </div>
        <div class="flex-1">
            <p class="text-sm text-gray-500">Total Value</p>
            <p class="text-lg font-semibold text-gray-900">
                KES {{ number_format($requisition->items->sum(fn($i) => $i->quantity * $i->unit_price), 2) }}
            </p>
        </div>
        <div class="flex-1">
            <p class="text-sm text-gray-500">Requested by</p>
            <p class="text-lg font-semibold text-gray-900">{{ $requisition->requester?->name ?? 'N/A' }}</p>
        </div>
    </div>

    {{-- Approval Timeline --}}
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-base font-semibold text-gray-900">Approval Timeline</h2>
        </div>

        @php
            $approvals = $requisition->approvals()->orderBy('created_at')->get();
        @endphp

        @if($approvals->isEmpty())
            <div class="px-6 py-10 text-center text-gray-500">
                No approval actions recorded yet.
            </div>
        @else
            <ul role="list" class="divide-y divide-gray-100">
                @foreach($approvals as $approval)
                    <li class="px-6 py-5 flex items-start gap-4">
                        {{-- Icon --}}
                        <div class="mt-1 flex-shrink-0">
                            @if($approval->decision === 'approved')
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-green-100">
                                    <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </span>
                            @elseif($approval->decision === 'rejected')
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-red-100">
                                    <svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </span>
                            @else
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-yellow-100">
                                    <svg class="h-5 w-5 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </span>
                            @endif
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-x-3">
                                <p class="text-sm font-medium text-gray-900">
                                    {{ $approval->approver?->name ?? 'Unknown Approver' }}
                                </p>
                                <time class="flex-shrink-0 text-xs text-gray-500">
                                    {{ $approval->created_at->format('d M Y, H:i') }}
                                </time>
                            </div>
                            <p class="mt-0.5 text-xs text-gray-500">
                                {{ $approval->approval_level ?? 'Approval' }}
                                &mdash;
                                <span @class([
                                    'font-semibold',
                                    'text-green-700' => $approval->decision === 'approved',
                                    'text-red-700'   => $approval->decision === 'rejected',
                                    'text-yellow-700' => !in_array($approval->decision, ['approved', 'rejected']),
                                ])>{{ ucfirst($approval->decision ?? 'pending') }}</span>
                            </p>
                            @if($approval->notes || $approval->justification)
                                <p class="mt-1 text-sm text-gray-700 italic">
                                    "{{ $approval->notes ?? $approval->justification }}"
                                </p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- Audit Log --}}
    @if(isset($auditLogs) && $auditLogs->count())
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-base font-semibold text-gray-900">Audit Log</h2>
            </div>
            <ul role="list" class="divide-y divide-gray-100 text-sm">
                @foreach($auditLogs as $log)
                    <li class="px-6 py-3 flex items-start justify-between gap-4">
                        <div>
                            <span class="font-medium text-gray-800">{{ $log->action }}</span>
                            @if($log->user_name)
                                <span class="text-gray-500"> by {{ $log->user_name }}</span>
                            @endif
                        </div>
                        <time class="flex-shrink-0 text-xs text-gray-400">
                            {{ \Carbon\Carbon::parse($log->created_at)->format('d M Y, H:i') }}
                        </time>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

</div>
@endsection
