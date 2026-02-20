@extends('layouts.app')

@section('title', 'Review Budget - ' . $budget->budget_code)

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Review Budget</h1>
            <p class="mt-2 text-gray-600">{{ $budget->budget_code }} - {{ $budget->name }}</p>
        </div>
        <a href="{{ route('budgets.pending') }}" 
           class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Pending
        </a>
    </div>

    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
        {{ session('error') }}
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Budget Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Main Information -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Budget Information</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Budget Code</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $budget->budget_code }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Fiscal Year</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $budget->fiscal_year }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Department</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $budget->department?->name ?? 'N/A' }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Category</label>
                        <p class="mt-1 text-sm text-gray-900">{{ ucfirst($budget->category) }}</p>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $budget->description }}</p>
                    </div>
                </div>
            </div>

            <!-- Financial Details -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Financial Details</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-blue-50 rounded-lg p-4">
                        <label class="block text-sm font-medium text-blue-700">Allocated Amount</label>
                        <p class="mt-2 text-2xl font-bold text-blue-900">KES {{ number_format($budget->allocated_amount, 2) }}</p>
                    </div>
                    
                    <div class="bg-yellow-50 rounded-lg p-4">
                        <label class="block text-sm font-medium text-yellow-700">Committed</label>
                        <p class="mt-2 text-2xl font-bold text-yellow-900">KES {{ number_format($budget->committed_amount, 2) }}</p>
                    </div>
                    
                    <div class="bg-green-50 rounded-lg p-4">
                        <label class="block text-sm font-medium text-green-700">Available</label>
                        <p class="mt-2 text-2xl font-bold text-green-900">KES {{ number_format($budget->available_amount, 2) }}</p>
                    </div>
                </div>
            </div>

            <!-- Approval History -->
            @if($budget->approvals->count() > 0)
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Approval History</h2>
                
                <div class="flow-root">
                    <ul role="list" class="-mb-8">
                        @foreach($budget->approvals as $approval)
                        <li>
                            <div class="relative pb-8">
                                @if(!$loop->last)
                                <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                @endif
                                <div class="relative flex space-x-3">
                                    <div>
                                        <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white
                                            @if($approval->action === 'approved') bg-green-500
                                            @elseif($approval->action === 'rejected') bg-red-500
                                            @elseif($approval->action === 'reviewed') bg-yellow-500
                                            @else bg-blue-500
                                            @endif">
                                            <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                @if($approval->action === 'approved')
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                @elseif($approval->action === 'rejected')
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                @else
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                                @endif
                                            </svg>
                                        </span>
                                    </div>
                                    <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                        <div>
                                            <p class="text-sm text-gray-900">
                                                <strong>{{ ucfirst($approval->action) }}</strong> by 
                                                <strong>{{ $approval->approver?->name ?? 'Unknown' }}</strong>
                                                <span class="text-gray-500">({{ ucfirst($approval->approver_role) }})</span>
                                            </p>
                                            @if($approval->comments)
                                            <p class="mt-1 text-sm text-gray-600">{{ $approval->comments }}</p>
                                            @endif
                                        </div>
                                        <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                            {{ $approval->created_at->format('M d, Y H:i') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif
        </div>

        <!-- Approval Actions -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow p-6 sticky top-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Approval Actions</h2>
                
                <div class="space-y-4">
                    <!-- Approve Form -->
                    <form method="POST" action="{{ route('budgets.approve', $budget) }}" onsubmit="return confirm('Are you sure you want to approve this budget?');">
                        @csrf
                        <div class="mb-4">
                            <label for="approve_comments" class="block text-sm font-medium text-gray-700 mb-2">
                                Comments (Optional)
                            </label>
                            <textarea 
                                id="approve_comments"
                                name="comments"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                                placeholder="Add any approval comments..."></textarea>
                        </div>
                        <button type="submit" 
                                class="w-full inline-flex justify-center items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Approve Budget
                        </button>
                    </form>

                    <div class="border-t border-gray-200 pt-4"></div>

                    <!-- Reject Form -->
                    <form method="POST" action="{{ route('budgets.reject', $budget) }}" onsubmit="return confirmReject();">
                        @csrf
                        <div class="mb-4">
                            <label for="rejection_reason" class="block text-sm font-medium text-red-700 mb-2">
                                Rejection Reason <span class="text-red-500">*</span>
                            </label>
                            <textarea 
                                id="rejection_reason"
                                name="rejection_reason"
                                rows="3"
                                required
                                class="w-full px-3 py-2 border border-red-300 rounded-lg focus:ring-red-500 focus:border-red-500"
                                placeholder="Please provide a reason for rejecting this budget..."></textarea>
                            @error('rejection_reason')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" 
                                class="w-full inline-flex justify-center items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Reject Budget
                        </button>
                    </form>
                </div>

                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-sm font-medium text-gray-900 mb-2">Submission Details</h3>
                    <div class="text-sm text-gray-600 space-y-1">
                        <p><strong>Submitted by:</strong> {{ $budget->submitter?->name ?? 'N/A' }}</p>
                        <p><strong>Submitted at:</strong> {{ $budget->submitted_at ? $budget->submitted_at->format('M d, Y H:i') : 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmReject() {
    const reason = document.getElementById('rejection_reason').value.trim();
    if (!reason) {
        alert('Please provide a reason for rejecting this budget.');
        return false;
    }
    return confirm('Are you sure you want to reject this budget? This action cannot be undone.');
}
</script>
@endsection
