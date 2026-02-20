@extends('layouts.app')

@section('title', 'ASL Review — ' . ($supplier->display_name ?? $supplier->business_name))

@section('content')
<div class="px-4 sm:px-6 lg:px-8 max-w-4xl">
    <div class="mb-6">
        <a href="{{ route('suppliers.asl.index') }}" class="text-sm text-primary-600 hover:text-primary-800">&larr; Back to ASL</a>
        <h1 class="mt-2 text-2xl font-semibold text-gray-900">
            ASL Review: {{ $supplier->display_name ?? $supplier->business_name }}
        </h1>
        <p class="text-sm text-gray-500">{{ $supplier->supplier_code }} &bull; {{ $supplier->kra_pin }}</p>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-md bg-green-50 p-4"><p class="text-sm font-medium text-green-800">{{ session('success') }}</p></div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-md bg-red-50 p-4"><p class="text-sm font-medium text-red-800">{{ session('error') }}</p></div>
    @endif

    {{-- Onboarding completeness bar --}}
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex items-center justify-between mb-2">
            <h2 class="text-base font-semibold text-gray-900">Onboarding Completeness</h2>
            <span class="text-lg font-bold {{ $completeness['complete'] ? 'text-green-600' : 'text-yellow-600' }}">
                {{ $completeness['percentage'] }}%
            </span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3 mb-4">
            <div class="h-3 rounded-full {{ $completeness['complete'] ? 'bg-green-500' : 'bg-yellow-500' }}"
                 style="width: {{ $completeness['percentage'] }}%"></div>
        </div>

        {{-- Document checklist --}}
        <div class="space-y-3">
            @php
                $requiredDocs = ['kra_pin_certificate','tax_compliance_certificate','bank_letter','business_registration'];
                $docLabels = [
                    'kra_pin_certificate' => 'KRA PIN Certificate',
                    'tax_compliance_certificate' => 'Tax Compliance Certificate',
                    'bank_letter' => 'Bank Letter / Confirmation',
                    'business_registration' => 'Business Registration Certificate',
                ];
                $uploadedDocs = $supplier->documents->keyBy('document_type');
            @endphp
            @foreach($requiredDocs as $docType)
                @php $doc = $uploadedDocs[$docType] ?? null; @endphp
                <div class="flex items-center justify-between rounded-md border p-3">
                    <div class="flex items-center gap-3">
                        @if($doc && !$doc->isExpired())
                            <span class="h-5 w-5 text-green-500">&#10003;</span>
                        @elseif($doc && $doc->isExpired())
                            <span class="h-5 w-5 text-red-500">&#9888;</span>
                        @else
                            <span class="h-5 w-5 text-gray-300">&#10007;</span>
                        @endif
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $docLabels[$docType] }}</p>
                            @if($doc)
                                <p class="text-xs text-gray-500">
                                    Uploaded: {{ $doc->file_name }}
                                    @if($doc->expiry_date) &bull; Expires: {{ $doc->expiry_date->format('d/m/Y') }} @endif
                                    @if($doc->isExpired()) <span class="text-red-600 font-medium">EXPIRED</span> @endif
                                </p>
                            @else
                                <p class="text-xs text-gray-400">Not uploaded</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($doc)
                            @if(!$doc->verified)
                                <form method="POST" action="{{ route('suppliers.documents.verify', [$supplier, $doc]) }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-blue-600 hover:text-blue-800 font-medium">Verify</button>
                                </form>
                            @else
                                <span class="text-xs text-green-600 font-medium">Verified</span>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ASL Action Panel --}}
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-base font-semibold text-gray-900 mb-4">ASL Actions</h2>
        <p class="text-sm text-gray-600 mb-4">
            Current status:
            <span class="font-semibold">{{ ucfirst(str_replace('_', ' ', $supplier->asl_status)) }}</span>
        </p>

        <div class="flex flex-wrap gap-3">
            {{-- Submit for review --}}
            @if(in_array($supplier->asl_status, ['not_applied', 'removed']))
                <form method="POST" action="{{ route('suppliers.asl.submit', $supplier) }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                        Submit for Review
                    </button>
                </form>
            @endif

            {{-- Approve --}}
            @if($supplier->asl_status === 'pending_review')
                <form method="POST" action="{{ route('suppliers.asl.approve', $supplier) }}" class="flex items-center gap-2">
                    @csrf
                    <button type="submit" class="inline-flex items-center rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-500"
                            onclick="return confirm('Approve this supplier for the ASL?')">
                        Approve for ASL
                    </button>
                </form>
            @endif

            {{-- Suspend --}}
            @if($supplier->asl_status === 'approved')
                <form method="POST" action="{{ route('suppliers.asl.suspend', $supplier) }}" class="flex items-center gap-2"
                      x-data="{ reason: '' }">
                    @csrf
                    <input type="text" name="reason" required minlength="10" placeholder="Reason for suspension (min 10 chars)"
                           class="rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 w-72" />
                    <button type="submit" class="inline-flex items-center rounded-md bg-yellow-600 px-4 py-2 text-sm font-semibold text-white hover:bg-yellow-500">
                        Suspend
                    </button>
                </form>
            @endif

            {{-- Remove --}}
            @if(in_array($supplier->asl_status, ['approved', 'suspended', 'pending_review']))
                <form method="POST" action="{{ route('suppliers.asl.remove', $supplier) }}" class="flex items-center gap-2">
                    @csrf
                    <input type="text" name="reason" required minlength="10" placeholder="Reason for removal (min 10 chars)"
                           class="rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 w-72" />
                    <button type="submit" class="inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500"
                            onclick="return confirm('Remove this supplier from the ASL? This cannot be undone without re-submitting.')">
                        Remove
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Supplier detail summary --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-base font-semibold text-gray-900 mb-4">Supplier Details</h2>
        <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
            <div><dt class="text-gray-500">Business Name</dt><dd class="font-medium text-gray-900">{{ $supplier->business_name }}</dd></div>
            <div><dt class="text-gray-500">KRA PIN</dt><dd class="font-medium text-gray-900">{{ $supplier->kra_pin ?? '—' }}</dd></div>
            <div><dt class="text-gray-500">Email</dt><dd class="font-medium text-gray-900">{{ $supplier->email ?? '—' }}</dd></div>
            <div><dt class="text-gray-500">Phone</dt><dd class="font-medium text-gray-900">{{ $supplier->phone ?? '—' }}</dd></div>
            <div><dt class="text-gray-500">Tax Cert Expiry</dt>
                <dd class="font-medium {{ $supplier->isTaxComplianceCertExpired() ? 'text-red-600' : 'text-gray-900' }}">
                    {{ $supplier->tax_compliance_cert_expiry?->format('d/m/Y') ?? '—' }}
                </dd>
            </div>
            <div><dt class="text-gray-500">ASL Review Due</dt><dd class="font-medium text-gray-900">{{ $supplier->asl_review_due_at?->format('d/m/Y') ?? '—' }}</dd></div>
        </dl>
    </div>
</div>
@endsection
