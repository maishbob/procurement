@extends('layouts.app')

@section('title', 'Approved Supplier List')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center mb-6">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Approved Supplier List (ASL)</h1>
            <p class="mt-1 text-sm text-gray-500">Manage which suppliers are approved to participate in procurement processes.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-md bg-green-50 p-4">
            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-md bg-red-50 p-4">
            <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
        </div>
    @endif

    {{-- Status summary cards --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-5 mb-6">
        @foreach(['not_applied' => ['label'=>'Not Applied','color'=>'gray'], 'pending_review' => ['label'=>'Pending Review','color'=>'yellow'], 'approved' => ['label'=>'Approved','color'=>'green'], 'suspended' => ['label'=>'Suspended','color'=>'orange'], 'removed' => ['label'=>'Removed','color'=>'red']] as $s => $meta)
        <a href="{{ route('suppliers.asl.index', ['status' => $s]) }}"
           class="rounded-lg bg-white p-4 shadow text-center hover:shadow-md transition {{ $status === $s ? 'ring-2 ring-primary-500' : '' }}">
            <p class="text-2xl font-bold text-{{ $meta['color'] }}-600">{{ $counts[$s] ?? 0 }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $meta['label'] }}</p>
        </a>
        @endforeach
    </div>

    {{-- Filter bar --}}
    <div class="mb-4 flex gap-2 flex-wrap">
        <a href="{{ route('suppliers.asl.index') }}"
           class="px-3 py-1.5 text-sm rounded-md {{ !$status ? 'bg-primary-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}">
            All
        </a>
        @foreach(['pending_review' => 'Pending Review', 'approved' => 'Approved', 'suspended' => 'Suspended', 'not_applied' => 'Not Applied', 'removed' => 'Removed'] as $s => $label)
        <a href="{{ route('suppliers.asl.index', ['status' => $s]) }}"
           class="px-3 py-1.5 text-sm rounded-md {{ $status === $s ? 'bg-primary-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>

    {{-- Supplier table --}}
    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 rounded-lg">
        <table class="min-w-full divide-y divide-gray-300">
            <thead class="bg-gray-50">
                <tr>
                    <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900">Supplier</th>
                    <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">KRA PIN</th>
                    <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">ASL Status</th>
                    <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Onboarding</th>
                    <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Review Due</th>
                    <th class="relative py-3.5 pl-3 pr-4"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse($suppliers as $supplier)
                <tr class="hover:bg-gray-50">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3">
                        <div class="font-medium text-gray-900">{{ $supplier->display_name ?? $supplier->business_name }}</div>
                        <div class="text-xs text-gray-500">{{ $supplier->supplier_code }}</div>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $supplier->kra_pin ?? '—' }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                        @php
                            $colors = ['approved'=>'green','pending_review'=>'yellow','suspended'=>'orange','removed'=>'red','not_applied'=>'gray'];
                            $labels = ['approved'=>'Approved','pending_review'=>'Pending Review','suspended'=>'Suspended','removed'=>'Removed','not_applied'=>'Not Applied'];
                            $c = $colors[$supplier->asl_status] ?? 'gray';
                        @endphp
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-{{ $c }}-100 text-{{ $c }}-800">
                            {{ $labels[$supplier->asl_status] ?? $supplier->asl_status }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                        @php $ob = $supplier->onboarding_status; $obColors = ['approved'=>'green','under_review'=>'blue','incomplete'=>'yellow','expired'=>'red']; @endphp
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-{{ $obColors[$ob] ?? 'gray' }}-100 text-{{ $obColors[$ob] ?? 'gray' }}-800">
                            {{ ucfirst(str_replace('_', ' ', $ob)) }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                        {{ $supplier->asl_review_due_at ? $supplier->asl_review_due_at->format('d/m/Y') : '—' }}
                    </td>
                    <td class="whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium space-x-2">
                        <a href="{{ route('suppliers.asl.review', $supplier) }}" class="text-primary-600 hover:text-primary-900">Review</a>
                        @if($supplier->asl_status === 'not_applied' || $supplier->asl_status === 'removed')
                            <form method="POST" action="{{ route('suppliers.asl.submit', $supplier) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-blue-600 hover:text-blue-900">Submit for Review</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-8 text-center text-sm text-gray-500">No suppliers found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $suppliers->withQueryString()->links() }}</div>
</div>
@endsection
