@component('mail::message')
    # Purchase Order {{ $purchaseOrder->po_number }}

    Dear {{ $purchaseOrder->supplier->primary_contact }},

    We are pleased to issue the following purchase order for supply of goods/services.

    **Purchase Order Details:**
    - **PO Number:** {{ $purchaseOrder->po_number }}
    - **PO Date:** {{ $purchaseOrder->po_date->format('d M Y') }}
    - **Expected Delivery:** {{ $purchaseOrder->delivery_date->format('d M Y') }}
    - **Delivery Location:** {{ $purchaseOrder->delivery_location }}
    - **Total Amount:** KES {{ number_format($purchaseOrder->total_amount, 2) }}

    **Line Items:**

    @component('mail::table')
        | Description | Quantity | Unit Price | Total |
        |:---|---:|---:|---:|
        @foreach($purchaseOrder->items as $item)
        | {{ $item->description }} | {{ $item->quantity }} | KES {{ number_format($item->unit_price, 2) }} | KES {{ number_format($item->total_price, 2) }} |
        @endforeach
    @endcomponent

    Please acknowledge receipt of this purchase order and confirm the expected delivery date. In case of any discrepancies, please contact us immediately.

    **Contact Information:**  
    Email: {{ config('mail.from.address') }}  
    Phone: [School Contact Number]

    Thanks,  
    {{ config('app.name') }}
@endcomponent
