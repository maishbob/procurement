@component('mail::message')
    # Budget Threshold Exceeded Alert

    Dear {{ $budgetOwner->name }},

    Your department budget has reached {{ $budgetLine->utilization_percentage }}% utilization, exceeding the alert threshold.

    **Budget Details:**
    - **Budget:** {{ $budgetLine->name }}
    - **Department:** {{ $budgetLine->department->name }}
    - **Allocated Amount:** KES {{ number_format($budgetLine->allocated_amount, 2) }}
    - **Committed Amount:** KES {{ number_format($budgetLine->committed_amount, 2) }}
    - **Executed Amount:** KES {{ number_format($budgetLine->executed_amount, 2) }}
    - **Remaining Balance:** KES {{ number_format($budgetLine->remaining_balance, 2) }}
    - **Utilization:** {{ $budgetLine->utilization_percentage }}%

    Please review pending requisitions and consider consolidating or deferring non-urgent purchases.

    @component('mail::button', ['url' => route('reports.budget')])
        View Budget Report
    @endcomponent

    If you have any questions, please contact the Finance Department.

    Thanks,  
    {{ config('app.name') }} Finance Team
@endcomponent
