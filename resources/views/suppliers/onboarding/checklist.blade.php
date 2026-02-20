@extends('layouts.app')

@section('title', 'Onboarding — ' . ($supplier->display_name ?? $supplier->business_name))

@section('content')
<div class="px-4 sm:px-6 lg:px-8 max-w-3xl">
    <div class="mb-6">
        <a href="{{ route('suppliers.asl.review', $supplier) }}" class="text-sm text-primary-600 hover:text-primary-800">&larr; Back to ASL Review</a>
        <h1 class="mt-2 text-2xl font-semibold text-gray-900">Onboarding Checklist</h1>
        <p class="text-sm text-gray-500">{{ $supplier->display_name ?? $supplier->business_name }}</p>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-md bg-green-50 p-4"><p class="text-sm font-medium text-green-800">{{ session('success') }}</p></div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-md bg-red-50 p-4"><p class="text-sm font-medium text-red-800">{{ session('error') }}</p></div>
    @endif

    {{-- Overall progress --}}
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-semibold text-gray-700">Overall Completeness</span>
            <span class="text-xl font-bold {{ $completeness['complete'] ? 'text-green-600' : 'text-yellow-600' }}">
                {{ $completeness['percentage'] }}%
            </span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3">
            <div class="h-3 rounded-full {{ $completeness['complete'] ? 'bg-green-500' : 'bg-yellow-500' }}"
                 style="width: {{ $completeness['percentage'] }}%"></div>
        </div>
        @if($completeness['complete'])
            <p class="mt-2 text-sm text-green-700 font-medium">All required documents uploaded and valid.</p>
        @else
            @if($completeness['missing'])
                <p class="mt-2 text-sm text-gray-600">
                    Missing: <span class="text-red-600 font-medium">{{ implode(', ', array_map(fn($d) => str_replace('_', ' ', ucfirst($d)), $completeness['missing'])) }}</span>
                </p>
            @endif
            @if($completeness['expired'])
                <p class="mt-1 text-sm text-gray-600">
                    Expired: <span class="text-orange-600 font-medium">{{ implode(', ', array_map(fn($d) => str_replace('_', ' ', ucfirst($d)), $completeness['expired'])) }}</span>
                </p>
            @endif
        @endif
    </div>

    {{-- Required document list --}}
    @php
        $requiredDocs = ['kra_pin_certificate','tax_compliance_certificate','bank_letter','business_registration'];
        $docLabels = [
            'kra_pin_certificate'        => 'KRA PIN Certificate',
            'tax_compliance_certificate' => 'Tax Compliance Certificate',
            'bank_letter'                => 'Bank Confirmation Letter',
            'business_registration'      => 'Business Registration Certificate',
        ];
        $uploadedDocs = $supplier->documents->keyBy('document_type');
    @endphp

    <div class="space-y-4">
        @foreach($requiredDocs as $docType)
            @php $doc = $uploadedDocs[$docType] ?? null; @endphp
            <div class="bg-white rounded-lg shadow p-5 flex items-start gap-4">
                {{-- Status icon --}}
                <div class="flex-shrink-0 mt-0.5">
                    @if($doc && !$doc->isExpired())
                        <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center">
                            <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    @elseif($doc && $doc->isExpired())
                        <div class="h-8 w-8 rounded-full bg-red-100 flex items-center justify-center">
                            <svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M12 3a9 9 0 110 18A9 9 0 0112 3z" />
                            </svg>
                        </div>
                    @else
                        <div class="h-8 w-8 rounded-full bg-gray-100 flex items-center justify-center">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    @endif
                </div>

                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900">{{ $docLabels[$docType] }}</p>
                    @if($doc)
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $doc->file_name }}
                            @if($doc->expiry_date)
                                &bull; Expires {{ $doc->expiry_date->format('d/m/Y') }}
                                @if($doc->isExpired()) <span class="text-red-600 font-semibold">(EXPIRED)</span> @endif
                                @if($doc->isExpiringSoon()) <span class="text-orange-600 font-semibold">(Expiring soon)</span> @endif
                            @endif
                        </p>
                        <p class="text-xs mt-1 {{ $doc->verified ? 'text-green-600' : 'text-gray-400' }}">
                            {{ $doc->verified ? '✓ Verified' : 'Pending verification' }}
                        </p>
                    @else
                        <p class="text-xs text-gray-400 mt-0.5">Not yet uploaded</p>
                    @endif
                </div>

                <div class="flex-shrink-0">
                    <a href="{{ route('suppliers.onboarding.upload', [$supplier, 'type' => $docType]) }}"
                       class="inline-flex items-center rounded-md {{ $doc ? 'bg-gray-100 text-gray-700 hover:bg-gray-200' : 'bg-primary-600 text-white hover:bg-primary-500' }} px-3 py-1.5 text-xs font-semibold shadow-sm">
                        {{ $doc ? 'Replace' : 'Upload' }}
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
