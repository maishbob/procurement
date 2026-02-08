@component('mail::message')
    # Requisition {{ $requisition->id }} Submitted for Approval

    Dear {{ $approver->first_name }},

    A new purchase requisition has been submitted and requires your approval.

    **Requisition Details:**
    - **Request ID:** REQ-{{ $requisition->id }}
- **Requested By:** {{ $requisition->requester->name }}
    - **Department:** {{ $requisition->department->name }}
    - **Total Amount:** KES {{ number_format($requisition->total_amount, 2) }}
    - **Description:** {{ $requisition->description }}
    - **Submit Date:** {{ $requisition->created_at->format('d M Y') }}

    @component('mail::button', ['url' => route('requisitions.show', $requisition)])
        Review Requisition
    @endcomponent

    Please review and approve or reject this requisition at your earliest convenience.

    Thanks,  
    {{ config('app.name') }} Team
@endcomponent
