<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requisition #{{ $requisition->reference_number }}</title>
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
            border-bottom: 3px solid #0369a1;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            color: #0369a1;
            font-size: 28px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
            font-size: 13px;
        }
        .requisition-header {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            margin-bottom: 30px;
        }
        .requisition-header div {
            flex: 1;
        }
        .requisition-header strong {
            display: block;
            color: #333;
            margin-bottom: 5px;
        }
        .label {
            font-weight: bold;
            color: #333;
            margin-top: 10px;
            margin-bottom: 3px;
            font-size: 12px;
        }
        .value {
            color: #666;
            padding-left: 10px;
            font-size: 13px;
            margin-bottom: 5px;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 12px;
            margin-top: 5px;
        }
        .status-approved {
            background-color: #dcfce7;
            color: #166534;
        }
        .status-submitted {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-draft {
            background-color: #f3f4f6;
            color: #374151;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 12px;
        }
        table th {
            background-color: #0369a1;
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
        .totals-section {
            margin-top: 20px;
            width: 50%;
            margin-left: auto;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }
        .totals-row.total {
            border-bottom: 2px solid #0369a1;
            font-weight: bold;
            font-size: 15px;
            padding: 10px 0;
        }
        .approval-section {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #0369a1;
        }
        .approval-table {
            font-size: 12px;
            width: 100%;
        }
        .approval-table td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        .approval-table th {
            background-color: #dbeafe;
            color: #0369a1;
            font-weight: bold;
            padding: 8px;
            text-align: left;
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
            <h1>REQUISITION FORM</h1>
            <p>{{ setting('organization_name', 'Kenya School') }}</p>
        </div>

        <!-- Reference Numbers and Status -->
        <div class="requisition-header">
            <div>
                <div class="label">Requisition Number</div>
                <div class="value" style="font-weight: bold; font-size: 16px;">{{ $requisition->reference_number }}</div>
            </div>
            <div>
                <div class="label">Department</div>
                <div class="value">{{ $requisition->department->name }}</div>
            </div>
            <div>
                <div class="label">Requester</div>
                <div class="value">{{ $requisition->requester->name }}</div>
            </div>
            <div>
                <div class="label">Date</div>
                <div class="value">{{ $requisition->created_at->format('M d, Y') }}</div>
            </div>
            <div style="text-align: right;">
                <div class="label">Status</div>
                <span class="status-badge status-{{ $requisition->status }}">
                    {{ strtoupper($requisition->status) }}
                </span>
            </div>
        </div>

        <!-- Requisition Details -->
        <div style="background-color: #f0f9ff; border-left: 4px solid #0369a1; padding: 15px; margin-bottom: 20px;">
            <div class="label">Purpose/Description</div>
            <div class="value">{{ $requisition->purpose }}</div>
            
            <div class="label" style="margin-top: 10px;">Budget Line</div>
            <div class="value">{{ $requisition->budget_line?->name ?? 'N/A' }}</div>
        </div>

        <!-- Requested Items -->
        <h3 style="color: #0369a1; border-bottom: 2px solid #0369a1; padding-bottom: 8px;">REQUESTED ITEMS</h3>
        <table>
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="40%">Item Description</th>
                    <th width="15%" class="text-right">Quantity</th>
                    <th width="15%" class="text-right">Est. Unit Price</th>
                    <th width="15%" class="text-right">Estimated Total</th>
                    <th width="10%" class="text-right">VAT</th>
                </tr>
            </thead>
            <tbody>
                @foreach($requisition->items as $idx => $item)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="text-right">{{ number_format($item->quantity, 2) }} {{ $item->unit }}</td>
                    <td class="text-right">KES {{ number_format($item->estimated_unit_price ?? 0, 2) }}</td>
                    <td class="text-right">KES {{ number_format(($item->estimated_unit_price ?? 0) * $item->quantity, 2) }}</td>
                    <td class="text-right">16%</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Summary -->
        <div class="totals-section">
            <div class="totals-row">
                <span>Subtotal (before VAT):</span>
                <span>KES {{ number_format($requisition->items->sum(fn($i) => ($i->estimated_unit_price ?? 0) * $i->quantity), 2) }}</span>
            </div>
            <div class="totals-row">
                <span>VAT (16%):</span>
                <span>KES {{ number_format($requisition->vat_amount ?? 0, 2) }}</span>
            </div>
            <div class="totals-row total">
                <span>TOTAL AMOUNT:</span>
                <span>KES {{ number_format($requisition->total_amount, 2) }}</span>
            </div>
        </div>

        <!-- Approvals -->
        @if($requisition->approvals->count() > 0)
        <div class="approval-section">
            <h3 style="color: #0369a1;">APPROVAL HISTORY</h3>
            <table class="approval-table">
                <thead>
                    <tr>
                        <th>Approval Level</th>
                        <th>Approver</th>
                        <th>Authority Limit</th>
                        <th>Decision</th>
                        <th>Date</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requisition->approvals as $approval)
                    <tr>
                        <td>{{ $approval->approval_level }}</td>
                        <td>{{ $approval->approver->name }}</td>
                        <td>KES {{ number_format($approval->approver->approval_limit, 0) }}</td>
                        <td style="font-weight: bold; color: {{ $approval->status === 'approved' ? '#16a34a' : '#dc2626' }};">
                            {{ strtoupper($approval->status) }}
                        </td>
                        <td>{{ $approval->decided_at?->format('M d, Y') ?? 'Pending' }}</td>
                        <td>{{ $approval->remarks ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>This requisition is subject to approval based on availability of budget and compliance with procurement policies.<br>
            Generated: {{ now()->format('M d, Y H:i') }} | Document Reference: {{ $requisition->reference_number }}</p>
        </div>
    </div>
</body>
</html>
