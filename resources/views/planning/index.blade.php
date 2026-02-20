@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Annual Procurement Plans</h1>
        <a href="{{ route('annual-procurement-plans.create') }}" class="btn btn-primary">New Plan</a>
    </div>
    <table class="table-auto w-full bg-white shadow rounded">
        <thead>
            <tr>
                <th class="px-4 py-2">Fiscal Year</th>
                <th class="px-4 py-2">Description</th>
                <th class="px-4 py-2">Status</th>
                <th class="px-4 py-2">Submitted</th>
                <th class="px-4 py-2">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($plans as $plan)
                <tr>
                    <td class="border px-4 py-2">{{ $plan->fiscal_year }}</td>
                    <td class="border px-4 py-2">{{ $plan->description }}</td>
                    <td class="border px-4 py-2">
                        <span class="badge badge-{{ $plan->status }}">{{ ucfirst($plan->status) }}</span>
                    </td>
                    <td class="border px-4 py-2">{{ $plan->submitted_at ? $plan->submitted_at->format('Y-m-d') : '-' }}</td>
                    <td class="border px-4 py-2">
                        <a href="{{ route('annual-procurement-plans.show', $plan) }}" class="btn btn-sm btn-info">View</a>
                        @can('update', $plan)
                            <a href="{{ route('annual-procurement-plans.edit', $plan) }}" class="btn btn-sm btn-warning">Edit</a>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center py-4">No plans found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
