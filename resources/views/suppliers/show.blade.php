@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ $supplier->business_name }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ $supplier->supplier_code ?? 'N/A' }} Â· {{ $supplier->city }}, {{ $supplier->country }}</p>
            </div>
            <div class="mt-4 sm:mt-0 flex gap-2">
                @can('suppliers.update')
                <a href="{{ route('suppliers.edit', $supplier->id) }}" class="inline-flex items-center px-4 py-2 rounded-md bg-primary-600 text-white hover:bg-primary-700 transition-colors">
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                </a>
                @endcan
                <a href="{{ route('suppliers.index') }}" class="inline-flex items-center px-4 py-2 rounded-md bg-gray-200 text-gray-700 hover:bg-gray-300 transition-colors">
                    â† Back to Suppliers
                </a>
            </div>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div x-data="{ activeTab: 'details' }" class="mb-8">
        <div class="border-b border-gray-200">
            <nav class="flex space-x-8 -mb-px" aria-label="Tabs">
                <button @click="activeTab = 'details'" 
                        :class="activeTab === 'details' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    Details
                </button>
                <button @click="activeTab = 'documents'" 
                        :class="activeTab === 'documents' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    Documents
                </button>
                <button @click="activeTab = 'performance'" 
                        :class="activeTab === 'performance' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    Performance
                </button>
                <button @click="activeTab = 'transactions'" 
                        :class="activeTab === 'transactions' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    Transactions
                </button>
            </nav>
        </div>

        <!-- Details Tab -->
        <div x-show="activeTab === 'details'" class="mt-8 space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Information -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h2>
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Business Name</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $supplier->business_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Trading Name</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $supplier->trading_name ?? 'â€”' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">KRA PIN</dt>
                                <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $supplier->kra_pin }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Registration Number</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $supplier->registration_number ?? 'â€”' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">VAT Number</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $supplier->vat_number ?? 'â€”' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd class="mt-1">
                                    @if($supplier->status === 'blacklisted')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Blacklisted</span>
                                    @elseif($supplier->status === 'inactive')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inactive</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                                    @endif
                                </dd>
                            </div>
                        </dl>

                        <hr class="my-6">

                        <h3 class="text-sm font-semibold text-gray-900 mb-4">Contact Information</h3>
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Email</dt>
                                <dd class="mt-1 text-sm text-primary-600">
                                    <a href="mailto:{{ $supplier->email }}">{{ $supplier->email }}</a>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Phone</dt>
                                <dd class="mt-1 text-sm text-primary-600">
                                    <a href="tel:{{ $supplier->phone }}">{{ $supplier->phone }}</a>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Website</dt>
                                <dd class="mt-1 text-sm text-primary-600">
                                    @if($supplier->website)
                                    <a href="{{ $supplier->website }}" target="_blank" rel="noopener noreferrer">{{ $supplier->website }}</a>
                                    @else
                                    â€”
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Contact Person</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $supplier->contact_person ?? 'â€”' }}</dd>
                            </div>
                        </dl>

                        <hr class="my-6">

                        <h3 class="text-sm font-semibold text-gray-900 mb-4">Address</h3>
                        <dl class="grid grid-cols-1 gap-6">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Physical Address</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $supplier->physical_address ?? 'â€”' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Postal Address</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $supplier->postal_address ?? 'â€”' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">City / Country</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $supplier->city }}, {{ $supplier->country }}</dd>
                            </div>
                        </dl>

                        <hr class="my-6">

                        <h3 class="text-sm font-semibold text-gray-900 mb-4">Bank Details</h3>
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Bank</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $supplier->bank_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Branch</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $supplier->bank_branch }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Account Name</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $supplier->bank_account_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Account Number</dt>
                                <dd class="mt-1 text-sm font-mono text-gray-900">{{ $supplier->bank_account_number }}</dd>
                            </div>
                            @if($supplier->bank_swift_code)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">SWIFT Code</dt>
                                <dd class="mt-1 text-sm font-mono text-gray-900">{{ $supplier->bank_swift_code }}</dd>
                            </div>
                            @endif
                        </dl>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Tax Compliance Status -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Tax Compliance</h3>
                        @if($supplier->is_tax_compliant)
                            <div class="rounded-lg bg-green-50 p-4 border border-green-200 mb-3">
                                <div class="flex items-center">
                                    <svg class="h-8 w-8 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="ml-2 text-sm font-medium text-green-800">Compliant</span>
                                </div>
                            </div>
                            @if($supplier->tax_compliance_cert_expiry)
                            <p class="text-xs text-gray-600">
                                Certificate expires: <strong>{{ $supplier->tax_compliance_cert_expiry->format('d M Y') }}</strong>
                                @if($supplier->tax_compliance_cert_expiry->diffInDays() < 30)
                                <span class="text-red-600 ml-1">(expires soon!)</span>
                                @endif
                            </p>
                            @endif
                        @else
                            <div class="rounded-lg bg-red-50 p-4 border border-red-200">
                                <div class="flex items-center">
                                    <svg class="h-8 w-8 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="ml-2 text-sm font-medium text-red-800">Not Compliant</span>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Performance Summary -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Performance</h3>
                        <div class="space-y-4">
                            <!-- Rating -->
                            <div>
                                <label class="text-xs text-gray-600 font-medium">Overall Rating</label>
                                <div class="flex items-center gap-1 mt-2">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= floor($supplier->performance_rating ?? 0))
                                            <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        @else
                                            <svg class="h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        @endif
                                    @endfor
                                </div>
                                <p class="text-sm text-gray-600 mt-2">{{ $supplier->performance_rating ?? 0 }}/5.0</p>
                            </div>

                            <!-- On-Time Delivery -->
                            <div>
                                <label class="text-xs text-gray-600 font-medium">On-Time Delivery</label>
                                <div class="mt-2">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-sm text-gray-600">{{ $supplier->on_time_delivery_percentage ?? 0 }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-green-500 h-2 rounded-full" style="width: {{ $supplier->on_time_delivery_percentage ?? 0 }}%"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Total Orders -->
                            <div>
                                <label class="text-xs text-gray-600 font-medium">Total Orders</label>
                                <p class="text-lg font-semibold text-gray-900 mt-1">{{ $supplier->total_orders ?? 0 }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-4">Actions</h3>
                        <div class="space-y-2">
                            @if($supplier->status === 'active')
                                @can('suppliers.blacklist')
                                <button onclick="showBlacklistModal({{ $supplier->id }})" class="w-full px-4 py-2 rounded-md bg-red-50 text-red-700 hover:bg-red-100 transition-colors text-sm font-medium">
                                    Blacklist Supplier
                                </button>
                                @endcan
                            @elseif($supplier->status === 'blacklisted')
                                @can('suppliers.unblacklist')
                                <button onclick="showUnblacklistModal({{ $supplier->id }})" class="w-full px-4 py-2 rounded-md bg-green-50 text-green-700 hover:bg-green-100 transition-colors text-sm font-medium">
                                    Unblacklist Supplier
                                </button>
                                @endcan
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Documents Tab -->
        <div x-show="activeTab === 'documents'" class="mt-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Documents</h2>
                <div class="text-center py-8">
                    <svg class="h-12 w-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-gray-600">Document upload functionality coming soon</p>
                </div>
            </div>
        </div>

        <!-- Performance Tab -->
        <div x-show="activeTab === 'performance'" class="mt-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Performance Metrics -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-6">Performance Metrics</h2>
                    <div class="space-y-6">
                        <!-- Quality Rating -->
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <label class="text-sm font-medium text-gray-700">Quality Rating</label>
                                <span class="text-sm font-semibold text-gray-900">{{ $supplier->quality_rating ?? 'â€”' }}/10</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-primary-500 h-2 rounded-full" style="width: {{ ($supplier->quality_rating ?? 0) * 10 }}%"></div>
                            </div>
                        </div>

                        <!-- Responsiveness -->
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <label class="text-sm font-medium text-gray-700">Responsiveness</label>
                                <span class="text-sm font-semibold text-gray-900">{{ $supplier->responsiveness_rating ?? 'â€”' }}/10</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-purple-500 h-2 rounded-full" style="width: {{ ($supplier->responsiveness_rating ?? 0) * 10 }}%"></div>
                            </div>
                        </div>

                        <!-- Compliance -->
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <label class="text-sm font-medium text-gray-700">Compliance & Punctuality</label>
                                <span class="text-sm font-semibold text-gray-900">{{ $supplier->compliance_rating ?? 'â€”' }}/10</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-500 h-2 rounded-full" style="width: {{ ($supplier->compliance_rating ?? 0) * 10 }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Performance History -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-6">Summary</h2>
                    <div class="space-y-4">
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <p class="text-xs text-gray-600 font-medium mb-1">Total Orders Value</p>
                            <p class="text-2xl font-bold text-gray-900">KES {{ number_format($supplier->total_orders_value ?? 0, 2) }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <p class="text-xs text-gray-600 font-medium mb-1">Completed Orders</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $supplier->completed_orders ?? 0 }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <p class="text-xs text-gray-600 font-medium mb-1">Average Order Value</p>
                            <p class="text-2xl font-bold text-gray-900">KES {{ number_format($supplier->average_order_value ?? 0, 2) }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <p class="text-xs text-gray-600 font-medium mb-1">Last Order Date</p>
                            <p class="text-lg font-semibold text-gray-900">{{ $supplier->last_order_date?->format('d M Y') ?? 'â€”' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transactions Tab -->
        <div x-show="activeTab === 'transactions'" class="mt-8">
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">PO Number</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr class="hover:bg-gray-50">
                                <td colspan="4" class="px-6 py-12 text-center text-gray-600">
                                    No transactions yet
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function showBlacklistModal(supplierId) {
    console.log('Show blacklist modal for supplier:', supplierId);
}

function showUnblacklistModal(supplierId) {
    console.log('Show unblacklist modal for supplier:', supplierId);
}
</script>
@endpush
@endsection

