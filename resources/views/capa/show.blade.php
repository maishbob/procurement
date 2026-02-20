@extends('layouts.app')

@section('title', $capa->capa_number)

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-6 max-w-5xl" x-data="{ rejectOpen: false, verifyOpen: false, closeOpen: false }">

    {{-- Header --}}
    <div class="sm:flex sm:items-start sm:justify-between mb-6">
        <div>
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="text-2xl font-bold text-gray-900">{{ $capa->capa_number }}</h1>
                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $capa->type === 'corrective' ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800' }}">
                    {{ ucfirst($capa->type) }}
                </span>
                @php $priorityColors = ['critical'=>'bg-red-100 text-red-800','high'=>'bg-orange-100 text-orange-800','medium'=>'bg-yellow-100 text-yellow-800','low'=>'bg-gray-100 text-gray-700']; @endphp
                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $priorityColors[$capa->priority] ?? '' }}">
                    {{ ucfirst($capa->priority) }} priority
                </span>
                @php $statusColors = ['draft'=>'bg-gray-100 text-gray-700','pending_approval'=>'bg-yellow-100 text-yellow-800','approved'=>'bg-blue-100 text-blue-800','in_progress'=>'bg-indigo-100 text-indigo-800','pending_verification'=>'bg-purple-100 text-purple-800','verified'=>'bg-teal-100 text-teal-800','closed'=>'bg-green-100 text-green-800','rejected'=>'bg-red-100 text-red-800','cancelled'=>'bg-gray-100 text-gray-500']; @endphp
                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColors[$capa->status] ?? '' }}">
                    {{ ucwords(str_replace('_',' ',$capa->status)) }}
                </span>
                @if($capa->isOverdue())
                <span class="inline-flex rounded-full bg-red-600 px-2.5 py-0.5 text-xs font-semibold text-white">OVERDUE</span>
                @endif
            </div>
            <p class="mt-1 text-base text-gray-600">{{ $capa->title }}</p>
        </div>
        <a href="{{ route('capa.index') }}" class="mt-4 sm:mt-0 inline-flex rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">
            &larr; Back
        </a>
    </div>

    {{-- Flash --}}
    @if(session('success'))
    <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    {{-- Workflow actions --}}
    <div class="mb-6 flex flex-wrap gap-2">
        @can('update', $capa)
        @if($capa->isDraft())
        <a href="{{ route('capa.edit', $capa) }}" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">Edit</a>
        <form method="POST" action="{{ route('capa.submit', $capa) }}">
            @csrf
            <button class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-500">Submit for Approval</button>
        </form>
        @endif
        @if($capa->status === 'approved')
        <form method="POST" action="{{ route('capa.start', $capa) }}">
            @csrf
            <button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">Start Implementation</button>
        </form>
        @endif
        @if($capa->isInProgress())
        <form method="POST" action="{{ route('capa.verify', $capa) }}" class="inline">
            @csrf
            <input type="hidden" name="passed" value="0">
            <button type="button" @click="verifyOpen = true" class="rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-500">
                Submit for Verification
            </button>
        </form>
        @endif
        @if($capa->canBeClosed())
        <button type="button" @click="closeOpen = true" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-500">Close CAPA</button>
        @endif
        @endcan

        @can('approve', $capa)
        @if($capa->canBeApproved())
        <form method="POST" action="{{ route('capa.approve', $capa) }}">
            @csrf
            <button class="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-500">Approve</button>
        </form>
        <button type="button" @click="rejectOpen = true" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500">Reject</button>
        @endif
        @endcan

        @can('verify', $capa)
        @if($capa->canBeVerified())
        <button type="button" @click="verifyOpen = true" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-500">Verify</button>
        @endif
        @endcan
    </div>

    {{-- Reject modal --}}
    <div x-show="rejectOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
            <h3 class="text-base font-semibold text-gray-900 mb-3">Reject CAPA</h3>
            <form method="POST" action="{{ route('capa.reject', $capa) }}">
                @csrf
                <textarea name="reason" required rows="3" placeholder="Reason for rejection…"
                          class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm mb-4"></textarea>
                <div class="flex gap-3">
                    <button class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white">Reject</button>
                    <button type="button" @click="rejectOpen = false" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-gray-300">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Verify modal --}}
    <div x-show="verifyOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
            <h3 class="text-base font-semibold text-gray-900 mb-3">Verification Result</h3>
            <form method="POST" action="{{ route('capa.verify', $capa) }}">
                @csrf
                <div class="mb-3 flex gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="passed" value="1" class="h-4 w-4 text-green-600"> <span class="text-sm">Passed</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="passed" value="0" class="h-4 w-4 text-red-600"> <span class="text-sm">Failed (return to in-progress)</span>
                    </label>
                </div>
                <textarea name="comments" rows="3" placeholder="Verification comments…"
                          class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm mb-4"></textarea>
                <div class="flex gap-3">
                    <button class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white">Submit</button>
                    <button type="button" @click="verifyOpen = false" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-gray-300">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Close modal --}}
    <div x-show="closeOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
            <h3 class="text-base font-semibold text-gray-900 mb-3">Close CAPA</h3>
            <form method="POST" action="{{ route('capa.close', $capa) }}">
                @csrf
                <textarea name="lessons_learned" rows="4" placeholder="Lessons learned (optional)…"
                          class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm mb-4"></textarea>
                <div class="flex gap-3">
                    <button class="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white">Close CAPA</button>
                    <button type="button" @click="closeOpen = false" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-gray-300">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Left: detail cards --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Problem & Actions --}}
            <div class="rounded-lg bg-white shadow ring-1 ring-gray-200 p-6 space-y-4">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Problem Analysis</h2>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-400">Problem Statement</dt>
                    <dd class="mt-1 text-sm text-gray-800 whitespace-pre-line">{{ $capa->problem_statement }}</dd>
                </div>
                @if($capa->root_cause_analysis)
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-400">Root Cause Analysis</dt>
                    <dd class="mt-1 text-sm text-gray-800 whitespace-pre-line">{{ $capa->root_cause_analysis }}</dd>
                </div>
                @endif
                @if($capa->immediate_action_taken)
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-400">Immediate Action Taken</dt>
                    <dd class="mt-1 text-sm text-gray-800 whitespace-pre-line">{{ $capa->immediate_action_taken }}</dd>
                </div>
                @endif
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-400">Proposed Action</dt>
                    <dd class="mt-1 text-sm text-gray-800 whitespace-pre-line">{{ $capa->proposed_action }}</dd>
                </div>
                @if($capa->implementation_plan)
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-400">Implementation Plan</dt>
                    <dd class="mt-1 text-sm text-gray-800 whitespace-pre-line">{{ $capa->implementation_plan }}</dd>
                </div>
                @endif
            </div>

            {{-- Progress Updates --}}
            <div class="rounded-lg bg-white shadow ring-1 ring-gray-200 p-6">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-4">Progress Updates</h2>

                @php $progress = $capa->getProgressPercentage(); @endphp
                <div class="mb-4">
                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                        <span>Overall Progress</span><span>{{ $progress }}%</span>
                    </div>
                    <div class="h-2 rounded-full bg-gray-200">
                        <div class="h-2 rounded-full bg-primary-600 transition-all" style="width: {{ $progress }}%"></div>
                    </div>
                </div>

                @if($capa->isInProgress())
                @can('view', $capa)
                <details class="mb-4">
                    <summary class="cursor-pointer text-sm font-semibold text-primary-700 hover:text-primary-500">+ Add Progress Update</summary>
                    <form method="POST" action="{{ route('capa.updates.store', $capa) }}" class="mt-3 space-y-3">
                        @csrf
                        <textarea name="update_description" rows="3" required placeholder="Describe progress made…"
                                  class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></textarea>
                        <div class="flex items-center gap-3">
                            <label class="text-sm text-gray-700">Progress %</label>
                            <input type="number" name="progress_percentage" min="0" max="100" step="5"
                                   value="{{ $progress }}"
                                   class="w-24 rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <button class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white">Save Update</button>
                        </div>
                    </form>
                </details>
                @endcan
                @endif

                <div class="space-y-3">
                    @forelse($capa->updates as $update)
                    <div class="border-l-2 border-gray-200 pl-4 py-1">
                        <div class="flex items-center justify-between text-xs text-gray-400">
                            <span class="font-semibold text-gray-600">{{ $update->user?->name }}</span>
                            <span>{{ $update->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <p class="mt-1 text-sm text-gray-800">{{ $update->update_description }}</p>
                        <span class="text-xs text-gray-400">{{ $update->progress_percentage }}% complete</span>
                    </div>
                    @empty
                    <p class="text-sm text-gray-400">No progress updates yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Right: metadata --}}
        <div class="space-y-6">
            <div class="rounded-lg bg-white shadow ring-1 ring-gray-200 p-6">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-4">Details</h2>
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-xs text-gray-400 uppercase tracking-wide">Source</dt><dd class="text-gray-800">{{ ucwords(str_replace('_',' ',$capa->source)) }}</dd></div>
                    @if($capa->source_reference)<div><dt class="text-xs text-gray-400 uppercase tracking-wide">Reference</dt><dd class="text-gray-800">{{ $capa->source_reference }}</dd></div>@endif
                    <div><dt class="text-xs text-gray-400 uppercase tracking-wide">Raised By</dt><dd class="text-gray-800">{{ $capa->raisedBy?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-gray-400 uppercase tracking-wide">Assigned To</dt><dd class="text-gray-800">{{ $capa->assignedTo?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-gray-400 uppercase tracking-wide">Department</dt><dd class="text-gray-800">{{ $capa->department?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-gray-400 uppercase tracking-wide">Raised On</dt><dd class="text-gray-800">{{ $capa->created_at->format('d/m/Y') }}</dd></div>
                    @if($capa->target_completion_date)<div><dt class="text-xs text-gray-400 uppercase tracking-wide">Due Date</dt><dd class="{{ $capa->isOverdue() ? 'text-red-600 font-semibold' : 'text-gray-800' }}">{{ $capa->target_completion_date->format('d/m/Y') }}</dd></div>@endif
                    @if($capa->actual_completion_date)<div><dt class="text-xs text-gray-400 uppercase tracking-wide">Completed</dt><dd class="text-gray-800">{{ $capa->actual_completion_date->format('d/m/Y') }}</dd></div>@endif
                    @if($capa->estimated_cost)<div><dt class="text-xs text-gray-400 uppercase tracking-wide">Est. Cost</dt><dd class="text-gray-800">KES {{ number_format($capa->estimated_cost, 2) }}</dd></div>@endif
                    @if($capa->actual_cost)<div><dt class="text-xs text-gray-400 uppercase tracking-wide">Actual Cost</dt><dd class="text-gray-800">KES {{ number_format($capa->actual_cost, 2) }}</dd></div>@endif
                </dl>
            </div>

            @if($capa->approvedBy)
            <div class="rounded-lg bg-white shadow ring-1 ring-gray-200 p-6">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-3">Approval</h2>
                <dl class="space-y-2 text-sm">
                    <div><dt class="text-xs text-gray-400">Approved By</dt><dd>{{ $capa->approvedBy->name }}</dd></div>
                    <div><dt class="text-xs text-gray-400">Approved At</dt><dd>{{ $capa->approved_at?->format('d/m/Y H:i') }}</dd></div>
                    @if($capa->approval_comments)<div><dt class="text-xs text-gray-400">Comments</dt><dd class="text-gray-800">{{ $capa->approval_comments }}</dd></div>@endif
                </dl>
            </div>
            @endif

            @if($capa->verifiedBy)
            <div class="rounded-lg bg-white shadow ring-1 ring-gray-200 p-6">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-3">Verification</h2>
                <dl class="space-y-2 text-sm">
                    <div><dt class="text-xs text-gray-400">Verified By</dt><dd>{{ $capa->verifiedBy->name }}</dd></div>
                    <div><dt class="text-xs text-gray-400">Result</dt><dd class="{{ $capa->verification_passed ? 'text-green-700' : 'text-red-700' }}">{{ $capa->verification_passed ? 'Passed' : 'Failed' }}</dd></div>
                    @if($capa->verification_comments)<div><dt class="text-xs text-gray-400">Comments</dt><dd>{{ $capa->verification_comments }}</dd></div>@endif
                </dl>
            </div>
            @endif

            @if($capa->lessons_learned)
            <div class="rounded-lg bg-white shadow ring-1 ring-gray-200 p-6">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-2">Lessons Learned</h2>
                <p class="text-sm text-gray-800 whitespace-pre-line">{{ $capa->lessons_learned }}</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
