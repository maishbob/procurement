@extends('layouts.app')

@section('title', 'Supplier Performance Report')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Supplier Performance Report</h1>
            <p class="mt-2 text-gray-600">Evaluate supplier delivery, quality, and payment performance</p>
        </div>
        <button onclick="exportReport('csv')" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Export CSV
        </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="search" id="search" placeholder="Search supplier..." class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
            <select id="category" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="">All Categories</option>
                <option value="regular">Regular Supplier</option>
                <option value="preferred">Preferred Supplier</option>
                <option value="emergency">Emergency Supplier</option>
            </select>
            <select id="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="blacklisted">Blacklisted</option>
            </select>
            <button onclick="applyFilters()" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">Filter</button>
        </div>
    </div>

    <!-- Suppliers Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Supplier</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Contact</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-900">Rating</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">On-Time %</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Quality Score</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Total Orders</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Total Spent</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($suppliers as $supplier)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                        <a href="{{ route('suppliers.show', $supplier) }}" class="text-primary-600 hover:underline">
                            {{ $supplier->name }}
                        </a>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $supplier->contact_name ?? 'N/A' }}</td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex justify-center">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="w-4 h-4 {{ $i <= round($supplier->rating ?? 0) ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                            @endfor
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 text-right">{{ $supplier->on_time_percentage ?? 0 }}%</td>
                    <td class="px-6 py-4 text-sm text-gray-600 text-right">{{ $supplier->quality_score ?? 0 }}/100</td>
                    <td class="px-6 py-4 text-sm text-gray-600 text-right">{{ $supplier->orders_count ?? 0 }}</td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 text-right">KES {{ number_format($supplier->total_spent ?? 0, 0) }}</td>
                    <td class="px-6 py-4 text-sm">
                        @if($supplier->blacklist_date)
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">Blacklisted</span>
                        @else
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">Active</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-8 text-center text-gray-500">No suppliers found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($suppliers->hasPages())
    <div class="flex justify-center">
        {{ $suppliers->links() }}
    </div>
    @endif
</div>

<script>
    function applyFilters() {
        const search = document.getElementById('search').value;
        const category = document.getElementById('category').value;
        const status = document.getElementById('status').value;
        
        const params = new URLSearchParams();
        if (search) params.append('search', search);
        if (category) params.append('category', category);
        if (status) params.append('status', status);
        
        window.location.href = '{{ route("reports.suppliers") }}?' + params.toString();
    }

    function exportReport(format) {
        alert(`Exporting as ${format.toUpperCase()}...`);
    }
</script>
@endsection

