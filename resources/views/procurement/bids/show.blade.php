@extends('layouts.app')

@section('title', 'Bid Details')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Supplier Bid</h1>
            <p class="mt-2 text-sm text-gray-700">Bid #{{ $bid->bid_number }}</p>
        </div>
    </div>

    <!-- Details Grid -->
    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Bid Details -->
        <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl">
            <div class="px-4 py-6 sm:p-8">
                <h3 class="text-base font-semibold leading-7 text-gray-900">Bid Information</h3>
                <dl class="mt-6 space-y-6 divide-y divide-gray-100 text-sm leading-6">
                    <div class="pt-6 sm:flex">
                        <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">Supplier</dt>
                        <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
                            <div class="text-gray-900">{{ $bid->supplier->name ?? 'N/A' }}</div>
                        </dd>
                    </div>
                    <div class="pt-6 sm:flex">
                        <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">Procurement Process</dt>
                        <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
                            <div class="text-gray-900">{{ $bid->procurementProcess->title ?? 'N/A' }}</div>
                        </dd>
                    </div>
                    <div class="pt-6 sm:flex">
                        <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">Total Amount</dt>
                        <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
                            <div class="text-gray-900 font-semibold text-lg">{{ $bid->currency }} {{ number_format($bid->total_amount, 2) }}</div>
                        </dd>
                    </div>
                    <div class="pt-6 sm:flex">
                        <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">Submitted Date</dt>
                        <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
                            <div class="text-gray-900">{{ $bid->submitted_at ? $bid->submitted_at->format('F d, Y g:i A') : '-' }}</div>
                        </dd>
                    </div>
                    <div class="pt-6 sm:flex">
                        <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">Delivery Timeline</dt>
                        <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
                            <div class="text-gray-900">{{ $bid->delivery_days ?? '-' }} days</div>
                        </dd>
                    </div>
                    <div class="pt-6 sm:flex">
                        <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">Validity Period</dt>
                        <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
                            <div class="text-gray-900">{{ $bid->validity_days ?? '-' }} days</div>
                        </dd>
                    </div>
                    <div class="pt-6 sm:flex">
                        <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">Status</dt>
                        <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium 
                                @if($bid->status === 'submitted') bg-blue-100 text-blue-800
                                @elseif($bid->status === 'evaluated') bg-green-100 text-green-800
                                @elseif($bid->status === 'disqualified') bg-red-100 text-red-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ ucfirst($bid->status) }}
                            </span>
                        </dd>
                    </div>
                    @if($bid->submission_notes)
                    <div class="pt-6 sm:flex">
                        <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">Notes</dt>
                        <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
                            <div class="text-gray-900">{{ $bid->submission_notes }}</div>
                        </dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>

        <!-- Evaluation -->
        <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl">
            <div class="px-4 py-6 sm:p-8">
                <h3 class="text-base font-semibold leading-7 text-gray-900">Evaluation</h3>
                @if($evaluations->count() > 0)
                <dl class="mt-6 space-y-6 divide-y divide-gray-100 text-sm leading-6">
                    @foreach($evaluations as $evaluation)
                    <div class="pt-6">
                        <div class="sm:flex">
                            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">Score</dt>
                            <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
                                <div class="text-gray-900">{{ $evaluation->weighted_score ?? 'N/A' }}</div>
                            </dd>
                        </div>
                        @if($evaluation->evaluation_notes)
                        <div class="mt-4">
                            <dt class="font-medium text-gray-900">Notes:</dt>
                            <dd class="mt-1 text-gray-600">{{ $evaluation->evaluation_notes }}</dd>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </dl>
                @else
                <p class="mt-6 text-sm text-gray-500">Not yet evaluated</p>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-6">
        <a href="{{ route('procurement.indexBids') }}" class="text-sm font-semibold leading-6 text-gray-900">&larr; Back to Bids</a>
    </div>
</div>
@endsection
