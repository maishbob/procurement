@component('mail::message')
    # Low Stock Alert

    Dear {{ $storeManager->name }},

    The following inventory items have fallen below their reorder level and require immediate attention.

    **Stock Status:**

    @component('mail::table')
        | Item | Current Stock | Reorder Level | Status |
        |:---|---:|---:|:---|
        @foreach($lowStockItems as $item)
        | {{ $item->name }} | {{ $item->current_stock }} {{ $item->unit }} | {{ $item->reorder_level }} | ⚠️ LOW |
        @endforeach
    @endcomponent

    **Recommended Action:**
    Please initiate a purchase requisition for these items to maintain sufficient stock levels.

    **Lead Times:**
    @foreach($lowStockItems as $item)
    - {{ $item->name }}: {{ $item->lead_time_days }} days
    @endforeach

    @component('mail::button', ['url' => route('requisitions.create')])
        Create Requisition
    @endcomponent

    Please prioritize orders with the longest lead times.

    Thanks,  
    {{ config('app.name') }} Inventory System
@endcomponent
