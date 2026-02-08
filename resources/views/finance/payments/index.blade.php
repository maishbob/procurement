@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Payments</h1>
                <p class="mt-1 text-sm text-gray-600">Process supplier payments and withholding tax</p>
            </div>
            @can('payments.create')
            <div class="mt-4 sm:mt-0">
                <a href="{{ route('payments.create') }}" class="inline-flex items-center px-4 py-2 rounded-md bg-primary-600 text-white hover:bg-primary-700 transition-colors">
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    New Payment
                </a>
            </div>
            @endcan
        </div>
    </div>

    <!-- WHT Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600 mb-1">Pending Payments</p>
            <p class="text-3xl font-bold text-gray-900">{{ $stats['pending_count'] ?? 0 }}</p>
            <p class="text-xs text-gray-500 mt-2">Total: KES {{ number_format($stats['pending_amount'] ?? 0, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600 mb-1">This Month WHT</p>
            <p class="text-3xl font-bold text-orange-600">KES {{ number_format($stats['month_wht'] ?? 0, 2) }}</p>
            <p class="text-xs text-gray-500 mt-2">{{ $stats['month_wht_count'] ?? 0 }} payments</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600 mb-1">Year to Date WHT</p>
            <p class="text-3xl font-bold text-orange-600">KES {{ number_format($stats['ytd_wht'] ?? 0, 2) }}</p>
            <p class="text-xs text-gray-500 mt-2">{{ $stats['ytd_count'] ?? 0 }} payments</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <form method="GET" action="{{ route('payments.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">All Statuses</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="pending_approval" {{ request('status') === 'pending_approval' ? 'selected' : '' }}>Pending Approval</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="processed" {{ request('status') === 'processed' ? 'selected' : '' }}>Processed</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>

                <!-- Payment Method -->
                <div>
                    <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-1">Method</label>
                    <select name="payment_method" id="payment_method" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">All Methods</option>
                        <option value="bank_transfer" {{ request('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                        <option value="mobile_money" {{ request('payment_method') === 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
                        <option value="cheque" {{ request('payment_method') === 'cheque' ? 'selected' : '' }}>Cheque</option>
                    </select>
                </div>

                <!-- Supplier -->
                <div>
                    <label for="supplier" class="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
                    <select name="supplier_id" id="supplier" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">All Suppliers</option>
                        <option value="active" {{ request('supplier_id') ? 'selected' : '' }}>â€” filtered â€”</option>
                    </select>
                </div>

                <!-- Amount Range -->
                <div>
                    <label for="amount_min" class="block text-sm font-medium text-gray-700 mb-1">Min Amount</label>
                    <input type="number" name="amount_min" id="amount_min" value="{{ request('amount_min') }}" placeholder="0" step="0.01" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>

                <!-- Search -->
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-3 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" name="search" id="search" placeholder="Payment #, supplier..." value="{{ request('search') }}" class="w-full pl-10 pr-4 py-2 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>
                </div>
            </div>

            <!-- Filter Actions -->
            <div class="flex gap-2 justify-end">
                <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md bg-primary-600 text-white hover:bg-primary-700 transition-colors">
                    Filter
                </button>
                @if(request()->hasAny(['status', 'payment_method', 'supplier_id', 'amount_min', 'search']))
                <a href="{{ route('payments.index') }}" class="inline-flex items-center px-4 py-2 rounded-md bg-gray-200 text-gray-700 hover:bg-gray-300 transition-colors">
                    Clear
                </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Payments Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-300">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Payment Ref</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Supplier</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Invoices</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Gross Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">WHT</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Net Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($payments as $payment)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="{{ route('payments.show', $payment->id) }}" class="text-primary-600 hover:text-primary-700">{{ $payment->payment_reference }}</a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $payment->supplier->business_name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $payment->invoices_count ?? 0 }} invoice(s)</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">KES {{ number_format($payment->gross_amount, 2) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-orange-600">
                            KES {{ number_format($payment->wht_amount, 2) }}
                            @if($payment->wht_amount > 0)
                            <span class="text-xs text-gray-500">({{ $payment->wht_rate ?? 0 }}%)</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">KES {{ number_format($payment->net_amount, 2) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                            $statusColors = [
                                'draft' => 'bg-gray-100 text-gray-800',
                                'pending_approval' => 'bg-primary-100 text-primary-800',
                                'approved' => 'bg-green-100 text-green-800',
                                'processed' => 'bg-green-100 text-green-800',
                                'rejected' => 'bg-red-100 text-red-800',
                            ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$payment->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ str_replace('_', ' ', ucwords($payment->status)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            <div class="relative x-data inline-block text-left" x-data="{ open: false }">
                                <button @click="open = !open" class="inline-flex items-center text-gray-400 hover:text-gray-600">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10.5 1.5H9.5V.5h1v1zm0 17H9.5v1h1v-1zm8-8.5v1h1v-1h-1zm-17 0v1H.5v-1h1zM5.5 5.5L4.793 4.793l-.707.707L4.793 5.5l.707.707.707-.707zm8 8L12.793 12.793l-.707.707.707.707.707-.707zm0-8L12.793 4.793l-.707.707.707.707.707-.707zm-8 8L4.793 12.793l-.707.707.707.707.707-.707z"/>
                                    </svg>
                                </button>
                                <div x-show="open" @click.away="open = false" class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10">
                                    <a href="{{ route('payments.show', $payment->id) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">View Details</a>
                                    @can('payments.approve')
                                    @if($payment->status === 'pending_approval')
                                    <a href="{{ route('payments.approve', $payment->id) }}" class="block px-4 py-2 text-sm text-green-700 hover:bg-green-50">Approve</a>
                                    @endif
                                    @endcan
                                    @can('payments.process')
                                    @if($payment->status === 'approved')
                                    <form action="{{ route('payments.process', $payment->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-primary-700 hover:bg-primary-50" onclick="return confirm('Process payment?')">Process Payment</button>
                                    </form>
                                    @endif
                                    @endcan
                                    @if($payment->status === 'processed' && $payment->wht_certificate_generated)
                                    <a href="{{ route('payments.wht-certificate', $payment->id) }}" class="block px-4 py-2 text-sm text-orange-700 hover:bg-orange-50">Download WHT Cert</a>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <svg class="h-12 w-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c3.3 0 6-1.343 6-3s-2.7-3-6-3-6 1.343-6 3 2.7 3 6 3zm0 3c-3.3 0-6 1.343-6 3v6c0 1.657 2.7 3 6 3s6-1.343 6-3v-6c0-1.657-2.7-3-6-3z"/>
                            </svg>
                            <p class="text-gray-600">No payments found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    @if($payments->hasPages())
    <div class="mt-6">
        {{ $payments->links() }}
    </div>
    @endif
</div>
@endsection

