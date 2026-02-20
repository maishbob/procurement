@extends('layouts.app')

@section('title', 'CAPA Register')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-6">

    {{-- Page header --}}
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">CAPA Register</h1>
            <p class="mt-1 text-sm text-gray-500">Corrective and Preventive Actions — ISO 9001:2015</p>
        </div>
        @can('create', \App\Modules\Quality\Models\CapaAction::class)
        <a href="{{ route('capa.create') }}"
           class="mt-4 sm:mt-0 inline-flex items-center gap-x-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-primary-500">
            <svg class="-ml-0.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
            </svg>
            New CAPA
        </a>
        @endcan
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    {{-- Filters --}}
    <form method="GET" class="mb-4 flex flex-wrap gap-3">
        <select name="status" onchange="this.form.submit()"
                class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
            <option value="">All Statuses</option>
            @foreach(['draft','pending_approval','approved','in_progress','pending_verification','verified','closed','rejected','cancelled'] as $s)
            <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucwords(str_replace('_',' ',$s)) }}</option>
            @endforeach
        </select>
        <select name="type" onchange="this.form.submit()"
                class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
            <option value="">All Types</option>
            <option value="corrective" @selected(request('type')==='corrective')>Corrective</option>
            <option value="preventive" @selected(request('type')==='preventive')>Preventive</option>
        </select>
        <select name="priority" onchange="this.form.submit()"
                class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
            <option value="">All Priorities</option>
            @foreach(['critical','high','medium','low'] as $p)
            <option value="{{ $p }}" @selected(request('priority')===$p)>{{ ucfirst($p) }}</option>
            @endforeach
        </select>
        @if(request()->hasAny(['status','type','priority']))
        <a href="{{ route('capa.index') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50">Clear</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="overflow-hidden rounded-lg bg-white shadow ring-1 ring-gray-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">CAPA #</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Title</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Priority</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Assigned To</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Due</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse($capas as $capa)
                @php
                    $priorityColors = ['critical'=>'bg-red-100 text-red-800','high'=>'bg-orange-100 text-orange-800','medium'=>'bg-yellow-100 text-yellow-800','low'=>'bg-gray-100 text-gray-700'];
                    $statusColors   = ['draft'=>'bg-gray-100 text-gray-700','pending_approval'=>'bg-yellow-100 text-yellow-800','approved'=>'bg-blue-100 text-blue-800','in_progress'=>'bg-indigo-100 text-indigo-800','pending_verification'=>'bg-purple-100 text-purple-800','verified'=>'bg-teal-100 text-teal-800','closed'=>'bg-green-100 text-green-800','rejected'=>'bg-red-100 text-red-800','cancelled'=>'bg-gray-100 text-gray-500'];
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono text-xs text-primary-700">{{ $capa->capa_number }}</td>
                    <td class="px-4 py-3 font-medium text-gray-900 max-w-xs truncate">{{ $capa->title }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $capa->type === 'corrective' ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800' }}">
                            {{ ucfirst($capa->type) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $priorityColors[$capa->priority] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ ucfirst($capa->priority) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $capa->assignedTo?->name ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusColors[$capa->status] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ ucwords(str_replace('_',' ',$capa->status)) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-600">
                        @if($capa->target_completion_date)
                            <span class="{{ $capa->isOverdue() ? 'text-red-600 font-semibold' : '' }}">
                                {{ $capa->target_completion_date->format('d/m/Y') }}
                                @if($capa->isOverdue()) <span class="text-xs">(overdue)</span> @endif
                            </span>
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2">
                            <a href="{{ route('capa.show', $capa) }}"
                               class="rounded px-2 py-1 text-xs font-medium text-primary-700 ring-1 ring-primary-300 hover:bg-primary-50">View</a>
                            @can('update', $capa)
                            @if($capa->isDraft())
                            <a href="{{ route('capa.edit', $capa) }}"
                               class="rounded px-2 py-1 text-xs font-medium text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">Edit</a>
                            @endif
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-10 text-center text-sm text-gray-500">
                        No CAPA records found.
                        @can('create', \App\Modules\Quality\Models\CapaAction::class)
                        <a href="{{ route('capa.create') }}" class="text-primary-600 hover:underline">Create the first one.</a>
                        @endcan
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $capas->links() }}</div>
</div>
@endsection
