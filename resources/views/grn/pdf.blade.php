<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GRN #{{ $grn->reference_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background-color: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #059669;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            color: #059669;
            font-size: 28px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
            font-size: 13px;
        }
        .grn-info {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            margin-bottom: 30px;
        }
        .grn-info div {
            flex: 1;
        }
        .label {
            font-weight: bold;
            color: #333;
            margin-bottom: 3px;
            font-size: 12px;
        }
        .value {
            color: #666;
            padding-left: 10px;
            font-size: 13px;
        }
        .section-title {
            background-color: #dcfce7;
            border-left: 4px solid #059669;
            padding: 8px 12px;
            margin-bottom: 15px;
            font-weight: bold;
            font-size: 13px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 12px;
        }
        table th {
            background-color: #059669;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        table td {
            border: 1px solid #ddd;
            padding: 8px 10px;
        }
        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .text-right {
            text-align: right;
        }
        .variance-alert {
            background-color: #fee2e2;
            border-left: 4px solid #dc2626;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 12px;
            color: #7f1d1d;
        }
        .inspection-section {
            background-color: #f0fdf4;
            border-left: 4px solid #059669;
            padding: 15px;
            margin-top: 20px;
            font-size: 12px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 11px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>GOODS RECEIVED NOTE</h1>
            <p>{{ setting('organization_name', 'Kenya School') }}</p>
        </div>

        <!-- GRN Information -->
        <div class="grn-info">
            <div>
                <div class="label">GRN Number</div>
                <div class="value" style="font-weight: bold; font-size: 16px;">{{ $grn->reference_number }}</div>
            </div>
            <div>
                <div class="label">GRN Date</div>
                <div class="value">{{ $grn->received_date->format('M d, Y') }}</div>
            </div>
            <div>
                <div class="label">PO Reference</div>
                <div class="value">{{ $grn->purchase_order?->reference_number ?? 'N/A' }}</div>
            </div>
            <div>
                <div class="label">Supplier</div>
                <div class="value">{{ $grn->purchase_order?->supplier->name ?? 'N/A' }}</div>
            </div>
            <div>
                <div class="label">Received By</div>
                <div class="value">{{ $grn->received_by_user?->name ?? 'Store Manager' }}</div>
            </div>
        </div>

        <!-- Received Items -->
        <div class="section-title">ITEMS RECEIVED</div>
        <table>
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="35%">Description</th>
                    <th width="10%" class="text-right">PO Qty</th>
                    <th width="10%" class="text-right">Received Qty</th>
                    <th width="15%" class="text-right">Variance</th>
                    <th width="15%" class="text-right">Unit Price</th>
                    <th width="10%" class="text-right">Line Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($grn->items as $idx => $item)
                @php
                    $variance = $item->quantity_received - ($item->po_item?->quantity ?? 0);
                    $variancePercent = ($item->po_item?->quantity ?? 0) > 0 
                        ? (($variance / ($item->po_item?->quantity ?? 0)) * 100) 
                        : 0;
                @endphp
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="text-right">{{ number_format($item->po_item?->quantity ?? 0, 2) }}</td>
                    <td class="text-right"><strong>{{ number_format($item->quantity_received, 2) }}</strong></td>
                    <td class="text-right" style="color: {{ abs($variance) > 0 ? '#dc2626' : '#059669' }}; font-weight: bold;">
                        {{ $variance > 0 ? '+' : '' }}{{ number_format($variance, 2) }} ({{ round($variancePercent, 1) }}%)
                    </td>
                    <td class="text-right">KES {{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">KES {{ number_format($item->quantity_received * $item->unit_price, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Variance Summary -->
        @if($grn->items->sum(fn($i) => abs($i->quantity_received - ($i->po_item?->quantity ?? 0))) > 0)
        <div class="variance-alert">
            <strong>⚠️ QUANTITY VARIANCES DETECTED</strong><br>
            Please review the variances above and ensure they are justified before posting to inventory.
        </div>
        @endif

        <!-- Quality Inspection -->
        @if($grn->inspection_notes)
        <div class="inspection-section">
            <strong>QUALITY INSPECTION NOTES</strong><br>
            {{ $grn->inspection_notes }}<br><br>
            <strong>Inspection Status:</strong> <span style="color: {{ $grn->quality_check_passed ? '#059669' : '#dc2626' }};">
                {{ $grn->quality_check_passed ? 'PASSED ✓' : 'FAILED ✗' }}
            </span>
        </div>
        @endif

        <!-- Receipt Summary -->
        <div style="background-color: #f0fdf4; border-left: 4px solid #059669; padding: 15px; margin-top: 20px; font-size: 12px;">
            <div style="margin-bottom: 8px;">
                <strong>Total Items Received:</strong> {{ $grn->items->sum('quantity_received') }}<br>
                <strong>Total Qty Variance:</strong> <span style="color: {{ abs($grn->items->sum(fn($i) => $i->quantity_received - ($i->po_item?->quantity ?? 0))) > 0 ? '#dc2626' : '#059669' }};">
                    {{ $grn->items->sum(fn($i) => $i->quantity_received - ($i->po_item?->quantity ?? 0)) }}
                </span><br>
                <strong>Receipt Status:</strong> <span style="font-weight: bold; color: #059669;">{{ strtoupper($grn->status) }}</span>
            </div>
        </div>

        <!-- Delivery Details -->
        <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin-top: 20px; font-size: 12px;">
            <strong>DELIVERY DETAILS</strong><br>
            Delivery Location: {{ $grn->delivery_location ?? 'Main Store' }}<br>
            Delivery Mode: {{ $grn->delivery_mode ?? 'School Transport' }}<br>
            Posted to Inventory: <span style="font-weight: bold; color: {{ $grn->posted_to_inventory ? '#059669' : '#666' }};">
                {{ $grn->posted_to_inventory ? 'YES (' . $grn->inventory_posting_date?->format('M d, Y') . ')' : 'PENDING' }}
            </span>
        </div>

        <!-- Signatures -->
        <div style="margin-top: 40px; border-top: 2px solid #059669; padding-top: 20px;">
            <div style="display: flex; justify-content: space-around; font-size: 12px;">
                <div style="text-align: center; width: 30%;">
                    <p style="margin: 0 0 50px 0;">_____________________</p>
                    <p style="margin: 0;"><strong>Received By</strong></p>
                    <p style="margin: 5px 0 0 0; color: #666;">{{ $grn->received_by_user?->name ?? 'Store Manager' }}</p>
                </div>
                <div style="text-align: center; width: 30%;">
                    <p style="margin: 0 0 50px 0;">_____________________</p>
                    <p style="margin: 0;"><strong>Inspected By</strong></p>
                    <p style="margin: 5px 0 0 0; color: #666;">{{ $grn->inspected_by_user?->name ?? 'Quality Officer' }}</p>
                </div>
                <div style="text-align: center; width: 30%;">
                    <p style="margin: 0 0 50px 0;">_____________________</p>
                    <p style="margin: 0;"><strong>Approved By</strong></p>
                    <p style="margin: 5px 0 0 0; color: #666;">Store Manager / Director</p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>This GRN serves as proof of goods receipt and is used for inventory reconciliation and invoice matching.<br>
            Generated: {{ now()->format('M d, Y H:i') }} | Reference: {{ $grn->reference_number }}</p>
        </div>
    </div>
</body>
</html>
