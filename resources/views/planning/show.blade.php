@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4 max-w-xl">
    <h1 class="text-2xl font-bold mb-4">Annual Procurement Plan Details</h1>
    <div class="mb-4">
        <strong>Fiscal Year:</strong> {{ $annualProcurementPlan->fiscal_year }}
    </div>
    <div class="mb-4">
        <strong>Description:</strong> {{ $annualProcurementPlan->description }}
    </div>
    <div class="mb-4">
        <strong>Status:</strong> <span class="badge badge-{{ $annualProcurementPlan->status }}">{{ ucfirst($annualProcurementPlan->status) }}</span>
    </div>
    <div class="mb-4">
        <strong>Submitted:</strong> {{ $annualProcurementPlan->submitted_at ? $annualProcurementPlan->submitted_at->format('Y-m-d') : '-' }}
    </div>
    <div class="mb-4">
        <strong>Line Items:</strong>
        <table class="table-auto w-full bg-white shadow rounded mt-2">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Planned Quarter</th>
                    <th>Estimated Value</th>
                    <th>Sourcing Method</th>
                </tr>
            </thead>
            <tbody>
                @forelse($annualProcurementPlan->items as $item)
                    <tr>
                        <td>{{ $item->category }}</td>
                        <td>{{ $item->description }}</td>
                        <td>{{ $item->planned_quarter }}</td>
                        <td>{{ number_format($item->estimated_value, 2) }}</td>
                        <td>{{ $item->sourcing_method }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center">No items</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="flex space-x-2">
        @can('update', $annualProcurementPlan)
            <a href="{{ route('annual-procurement-plans.edit', $annualProcurementPlan) }}" class="btn btn-warning">Edit</a>
        @endcan
        @can('submit', $annualProcurementPlan)
            <form method="POST" action="{{ route('annual-procurement-plans.submit', $annualProcurementPlan) }}" class="inline">
                @csrf
                <button type="submit" class="btn btn-primary">Submit</button>
            </form>
        @endcan
        @can('approve', $annualProcurementPlan)
            <form method="POST" action="{{ route('annual-procurement-plans.approve', $annualProcurementPlan) }}" class="inline">
                @csrf
                <button type="submit" class="btn btn-success">Approve</button>
            </form>
        @endcan
        @can('reject', $annualProcurementPlan)
            <form method="POST" action="{{ route('annual-procurement-plans.reject', $annualProcurementPlan) }}" class="inline">
                @csrf
                <button type="submit" class="btn btn-danger">Reject</button>
            </form>
        @endcan
    </div>
</div>
@endsection
