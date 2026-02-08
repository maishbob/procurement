@extends('layouts.app')

@section('title', 'Procurement - Tenders')

@section('content')
<div class="sm:flex sm:items-center">
    <div class="sm:flex-auto">
        <h1 class="text-2xl font-semibold text-gray-900">Procurement</h1>
        <p class="mt-2 text-sm text-gray-700">Manage Tenders.</p>
    </div>
    <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
        <a href="{{ route('procurement.tender.create') }}" class="btn btn-primary">
            Create Tender
        </a>
    </div>
</div>

<!-- Tabs -->
<div class="mt-6 border-b border-gray-200">
    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
        <a href="{{ route('procurement.indexRFQ') }}" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium">
            RFQs
        </a>
        <a href="{{ route('procurement.indexRFP') }}" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium">
            RFPs
        </a>
        <a href="{{ route('procurement.indexTender') }}" class="border-primary-500 text-primary-600 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium" aria-current="page">
            Tenders
        </a>
        <a href="{{ route('procurement.indexBids') }}" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium">
            Bids
        </a>
    </nav>
</div>

<div class="mt-6">
    <p class="text-gray-500 text-center py-8">Tender module placeholder.</p>
</div>
@endsection
