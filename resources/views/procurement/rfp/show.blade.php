@extends('layouts.app')

@section('title', 'RFP Details')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">{{ $process->title }}</h1>
            <p class="mt-2 text-sm text-gray-700">RFP #{{ $process->process_number }}</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 flex gap-3">
            @if($process->status === 'draft')
                <a href="{{ route('procurement.rfp.edit', $process) }}" class="btn btn-secondary">Edit</a>
                <form action="{{ route('procurement.rfp.publish', $process) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="btn btn-primary">Publish RFP</button>
                </form>
            @elseif(in_array($process->status, ['rfq_issued', 'bids_received']))
                <form action="{{ route('procurement.rfp.close', $process) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="btn btn-secondary">Close RFP</button>
                </form>
            @endif
        </div>
    </div>

    <!-- Status Badge -->
    <div class="mt-6">
        <span class="inline-flex items-center rounded-md px-2.5 py-1 text-sm font-medium 
            @if($process->status === 'draft') bg-gray-100 text-gray-800
            @elseif(in_array($process->status, ['rfq_issued', 'bids_received'])) bg-green-100 text-green-800
            @elseif(in_array($process->status, ['evaluation', 'evaluation_complete'])) bg-blue-100 text-blue-800
            @else bg-yellow-100 text-yellow-800
            @endif">
            {{ str_replace('_', ' ', ucfirst($process->status)) }}
        </span>
    </div>

    <!-- Details Grid -->
    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Main Details -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl">
                <div class="px-4 py-6 sm:p-8">
                    <h3 class="text-base font-semibold leading-7 text-gray-900">RFP Details</h3>
                    <dl class="mt-6 space-y-6 divide-y divide-gray-100 text-sm leading-6">
                        <div class="pt-6 sm:flex">
                            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">Description</dt>
                            <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
                                <div class="text-gray-900">{{ $process->description }}</div>
                            </dd>
                        </div>
                        <div class="pt-6 sm:flex">
                            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">Submission Deadline</dt>
                            <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
                                <div class="text-gray-900">{{ $process->submission_deadline ? $process->submission_deadline->format('F d, Y g:i A') : '-' }}</div>
                            </dd>
                        </div>
                        <div class="pt-6 sm:flex">
                            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">Created By</dt>
                            <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
                                <div class="text-gray-900">{{ $process->creator->name ?? 'N/A' }}</div>
                            </dd>
                        </div>
                        <div class="pt-6 sm:flex">
                            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">Created Date</dt>
                            <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
                                <div class="text-gray-900">{{ $process->created_at->format('F d, Y') }}</div>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Proposals Received -->
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl">
                <div class="px-4 py-6 sm:p-8">
                    <h3 class="text-base font-semibold leading-7 text-gray-900">Proposals Received ({{ $bids->count() }})</h3>
                    @if($bids->count() > 0)
                    <div class="mt-6 overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Supplier</th>
                                    <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Amount</th>
                                    <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Submitted</th>
                                    <th class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach($bids as $bid)
                                <tr>
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                                        {{ $bid->supplier->name ?? 'N/A' }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ $bid->currency }} {{ number_format($bid->total_amount, 2) }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ $bid->submitted_at ? $bid->submitted_at->format('M d, Y') : '-' }}
                                    </td>
                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                        <a href="{{ route('procurement.bids.show', $bid) }}" class="text-primary-600 hover:text-primary-900">View</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="mt-6 text-sm text-gray-500">No proposals received yet.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Invited Suppliers -->
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl">
                <div class="px-4 py-6 sm:p-8">
                    <h3 class="text-base font-semibold leading-7 text-gray-900">Invited Suppliers</h3>
                    <ul role="list" class="mt-6 divide-y divide-gray-100">
                        @forelse($process->invitedSuppliers as $supplier)
                        <li class="py-3">
                            <div class="text-sm text-gray-900">{{ $supplier->name }}</div>
                        </li>
                        @empty
                        <li class="py-3 text-sm text-gray-500">No suppliers invited</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6">
        <a href="{{ route('procurement.indexRFP') }}" class="text-sm font-semibold leading-6 text-gray-900">&larr; Back to RFPs</a>
    </div>
</div>
@endsection
