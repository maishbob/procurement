@extends('layouts.app')

@section('title', 'KPI Dashboard')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-6">

    {{-- Header + Filter --}}
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">KPI Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500">Fiscal Year {{ $fiscalYear }} — {{ $quarter }}</p>
        </div>
        <form method="GET" class="mt-4 sm:mt-0 flex items-center gap-2">
            <label class="text-sm text-gray-600">Fiscal Year</label>
            <input type="text" name="fiscal_year" value="{{ $fiscalYear }}" placeholder="e.g. 2025/2026"
                   class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 w-32">
            <button class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-500">Apply</button>
        </form>
    </div>

    {{-- Flash --}}
    @if(session('error'))
    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    @php
        $cycle    = $data['procurement_cycle_time']   ?? [];
        $budget   = $data['budget_utilization']        ?? [];
        $supplier = $data['supplier_performance']      ?? [];
        $comply   = $data['compliance']                ?? [];
        $payment  = $data['payment_efficiency']        ?? [];
        $process  = $data['process_efficiency']        ?? [];
    @endphp

    {{-- Summary KPI Cards --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5 mb-6">

        {{-- Cycle Time --}}
        <div class="rounded-lg bg-white shadow ring-1 ring-gray-200 p-5 text-center">
            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">Avg Cycle Time</dt>
            <dd class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($cycle['average_days'] ?? 0, 1) }}</dd>
            <dd class="text-xs text-gray-500">days</dd>
        </div>

        {{-- 3-Way Match Rate --}}
        @php $matchRate = $comply['three_way_match_rate'] ?? 0; @endphp
        <div class="rounded-lg bg-white shadow ring-1 ring-gray-200 p-5 text-center">
            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">3-Way Match</dt>
            <dd class="mt-2 text-3xl font-bold {{ $matchRate >= 95 ? 'text-green-600' : ($matchRate >= 80 ? 'text-yellow-600' : 'text-red-600') }}">
                {{ number_format($matchRate, 1) }}%
            </dd>
            <dd class="text-xs text-gray-500">compliance rate</dd>
        </div>

        {{-- Budget Utilization --}}
        @php $budgetUtil = $budget['utilization_percentage'] ?? 0; @endphp
        <div class="rounded-lg bg-white shadow ring-1 ring-gray-200 p-5 text-center">
            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">Budget Used</dt>
            <dd class="mt-2 text-3xl font-bold {{ $budgetUtil <= 90 ? 'text-green-600' : 'text-red-600' }}">
                {{ number_format($budgetUtil, 1) }}%
            </dd>
            <dd class="text-xs text-gray-500">of allocated</dd>
        </div>

        {{-- On-Time Delivery --}}
        @php $onTime = $supplier['on_time_delivery_rate'] ?? 0; @endphp
        <div class="rounded-lg bg-white shadow ring-1 ring-gray-200 p-5 text-center">
            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">On-Time Delivery</dt>
            <dd class="mt-2 text-3xl font-bold {{ $onTime >= 90 ? 'text-green-600' : ($onTime >= 75 ? 'text-yellow-600' : 'text-red-600') }}">
                {{ number_format($onTime, 1) }}%
            </dd>
            <dd class="text-xs text-gray-500">of deliveries</dd>
        </div>

        {{-- Payment Processing --}}
        <div class="rounded-lg bg-white shadow ring-1 ring-gray-200 p-5 text-center">
            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">Avg Payment Time</dt>
            <dd class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($payment['average_processing_days'] ?? 0, 1) }}</dd>
            <dd class="text-xs text-gray-500">days to pay</dd>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Exception Alerts --}}
        <div class="rounded-lg bg-white shadow ring-1 ring-gray-200 p-6">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-4">Exception Alerts</h2>
            <ul class="space-y-3 text-sm">
                <li class="flex items-center justify-between">
                    <span class="text-gray-600">Failed 3-Way Matches</span>
                    @php $failed3wm = $comply['three_way_match_failures'] ?? 0; @endphp
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $failed3wm > 0 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-700' }}">
                        {{ $failed3wm }}
                    </span>
                </li>
                <li class="flex items-center justify-between">
                    <span class="text-gray-600">Non-eTIMS Invoices</span>
                    @php $noEtims = $comply['non_etims_invoices'] ?? 0; @endphp
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $noEtims > 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-700' }}">
                        {{ $noEtims }}
                    </span>
                </li>
                <li class="flex items-center justify-between">
                    <span class="text-gray-600">Overall Compliance Score</span>
                    @php $score = $comply['overall_compliance_score'] ?? 0; @endphp
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $score >= 90 ? 'bg-green-100 text-green-800' : ($score >= 70 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                        {{ number_format($score, 1) }}%
                    </span>
                </li>
                <li class="flex items-center justify-between">
                    <span class="text-gray-600">Emergency Procurements</span>
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ ($process['emergency_procurement_count'] ?? 0) > 0 ? 'bg-orange-100 text-orange-800' : 'bg-green-100 text-green-700' }}">
                        {{ $process['emergency_procurement_count'] ?? 0 }}
                    </span>
                </li>
                <li class="flex items-center justify-between">
                    <span class="text-gray-600">Single-Source</span>
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-gray-100 text-gray-700">
                        {{ $process['single_source_count'] ?? 0 }}
                    </span>
                </li>
            </ul>
        </div>

        {{-- Budget Utilization --}}
        <div class="rounded-lg bg-white shadow ring-1 ring-gray-200 p-6">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-4">Budget Utilization</h2>
            @php
                $budgetTotal = $budget['total_budget'] ?? 0;
                $budgetSpent = $budget['total_spent'] ?? 0;
                $budgetCommitted = $budget['total_committed'] ?? 0;
            @endphp
            <dl class="space-y-2 text-sm mb-4">
                <div class="flex justify-between"><dt class="text-gray-500">Total Budget</dt><dd class="font-semibold">KES {{ number_format($budgetTotal) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Spent</dt><dd class="font-semibold text-red-700">KES {{ number_format($budgetSpent) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Committed</dt><dd class="font-semibold text-orange-700">KES {{ number_format($budgetCommitted) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Available</dt><dd class="font-semibold text-green-700">KES {{ number_format(max(0, $budgetTotal - $budgetSpent - $budgetCommitted)) }}</dd></div>
            </dl>
            @if($budgetTotal > 0)
            <div class="space-y-1">
                <div class="flex justify-between text-xs text-gray-400"><span>Spent</span><span>{{ number_format(($budgetSpent / $budgetTotal) * 100, 1) }}%</span></div>
                <div class="h-3 rounded-full bg-gray-100 overflow-hidden flex">
                    <div class="h-3 bg-red-500" style="width: {{ min(100, ($budgetSpent / $budgetTotal) * 100) }}%"></div>
                    <div class="h-3 bg-orange-400" style="width: {{ min(100 - ($budgetSpent / $budgetTotal) * 100, ($budgetCommitted / $budgetTotal) * 100) }}%"></div>
                </div>
                <div class="flex gap-4 text-xs text-gray-400 mt-1">
                    <span class="flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-full bg-red-500"></span>Spent</span>
                    <span class="flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-full bg-orange-400"></span>Committed</span>
                    <span class="flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-full bg-gray-200"></span>Available</span>
                </div>
            </div>
            @endif

            {{-- Spend by department --}}
            @if(!empty($budget['spend_by_department']))
            <div class="mt-4">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">By Department</h3>
                <div class="space-y-2">
                    @foreach(array_slice($budget['spend_by_department'], 0, 5) as $dept)
                    <div>
                        <div class="flex justify-between text-xs text-gray-600 mb-0.5">
                            <span>{{ $dept['department'] ?? 'Unknown' }}</span>
                            <span>KES {{ number_format($dept['total_spent'] ?? 0) }}</span>
                        </div>
                        @if($budgetTotal > 0)
                        <div class="h-1.5 rounded-full bg-gray-100">
                            <div class="h-1.5 rounded-full bg-primary-500" style="width: {{ min(100, (($dept['total_spent'] ?? 0) / $budgetTotal) * 100) }}%"></div>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- Quarterly Snapshot --}}
        <div class="rounded-lg bg-white shadow ring-1 ring-gray-200 p-6">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-4">{{ $quarter }} Snapshot</h2>
            @php
                $qCycle   = $quarterData['procurement_cycle_time'] ?? [];
                $qSupp    = $quarterData['supplier_performance']   ?? [];
                $qComply  = $quarterData['compliance']             ?? [];
                $qPayment = $quarterData['payment_efficiency']     ?? [];
                $qProcess = $quarterData['process_efficiency']     ?? [];
            @endphp
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Avg Cycle (days)</dt><dd class="font-semibold">{{ number_format($qCycle['average_days'] ?? 0, 1) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Requisitions</dt><dd class="font-semibold">{{ $qProcess['total_requisitions'] ?? 0 }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Approval Rate</dt><dd class="font-semibold">{{ number_format($qProcess['approval_rate'] ?? 0, 1) }}%</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">On-Time Deliveries</dt><dd class="font-semibold">{{ number_format($qSupp['on_time_delivery_rate'] ?? 0, 1) }}%</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">3-Way Match Rate</dt><dd class="font-semibold">{{ number_format($qComply['three_way_match_rate'] ?? 0, 1) }}%</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">On-Time Payment Rate</dt><dd class="font-semibold">{{ number_format($qPayment['on_time_payment_rate'] ?? 0, 1) }}%</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Total Payments</dt><dd class="font-semibold">KES {{ number_format($qPayment['total_payment_value'] ?? 0) }}</dd></div>
            </dl>
        </div>
    </div>

    {{-- Procurement Process Efficiency --}}
    <div class="mt-6 rounded-lg bg-white shadow ring-1 ring-gray-200 p-6">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-4">Process Efficiency — Full Year</h2>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="text-center">
                <dd class="text-2xl font-bold text-gray-900">{{ $process['total_requisitions'] ?? 0 }}</dd>
                <dt class="text-xs text-gray-400 uppercase tracking-wide">Total Requisitions</dt>
            </div>
            <div class="text-center">
                <dd class="text-2xl font-bold text-green-600">{{ number_format($process['approval_rate'] ?? 0, 1) }}%</dd>
                <dt class="text-xs text-gray-400 uppercase tracking-wide">Approval Rate</dt>
            </div>
            <div class="text-center">
                <dd class="text-2xl font-bold text-red-600">{{ number_format($process['rejection_rate'] ?? 0, 1) }}%</dd>
                <dt class="text-xs text-gray-400 uppercase tracking-wide">Rejection Rate</dt>
            </div>
            <div class="text-center">
                <dd class="text-2xl font-bold text-gray-900">{{ number_format($cycle['median_days'] ?? 0, 1) }}</dd>
                <dt class="text-xs text-gray-400 uppercase tracking-wide">Median Cycle (days)</dt>
            </div>
        </div>
    </div>

</div>
@endsection
