@component('mail::message')
    # Payment Processed

    Dear {{ $payment->invoice->supplier->primary_contact }},

    Payment for your invoice has been successfully processed.

    **Payment Details:**
    - **Invoice Number:** INV-{{ $payment->invoice->invoice_number }}
    - **Gross Amount:** KES {{ number_format($payment->invoice->gross_amount, 2) }}
    - **WHT Deducted:** KES {{ number_format($payment->invoice->wht_amount ?? 0, 2) }}
    - **Net Amount Paid:** KES {{ number_format($payment->amount, 2) }}
    - **Payment Date:** {{ $payment->payment_date->format('d M Y') }}
    - **Payment Mode:** {{ ucfirst(str_replace('_', ' ', $payment->payment_mode)) }}
    - **Reference:** {{ $payment->reference_number }}

    WHT Certificate will be issued separately as per income tax requirements.

    If you have any questions regarding this payment, please contact our finance department.

    Thanks,  
    {{ config('app.name') }} Finance Team
@endcomponent
