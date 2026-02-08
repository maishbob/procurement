@component('mail::message')
    # Requisition {{ $requisition->id }} Approved

    Dear {{ $requisition->requester->name }},

    Your purchase requisition has been approved and will proceed to purchase order creation.

    **Requisition Details:**
    - **Request ID:** REQ-{{ $requisition->id }}
    - **Status:** Approved
    - **Approved By:** {{ $approver->name }}
    - **Total Amount:** KES {{ number_format($requisition->total_amount, 2) }}
    - **Approval Date:** {{ now()->format('d M Y') }}

    @component('mail::button', ['url' => route('requisitions.show', $requisition)])
        View Requisition
    @endcomponent

    Your procurement team will now proceed with supplier selection and purchase order creation.

    Thanks,  
    {{ config('app.name') }} Team
@endcomponent
