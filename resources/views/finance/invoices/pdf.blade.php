<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $invoice->reference_number }}</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #7c3aed;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .company-info h1 {
            margin: 0;
            color: #7c3aed;
            font-size: 28px;
        }
        .company-info p {
            margin: 5px 0;
            color: #666;
            font-size: 13px;
        }
        .invoice-info {
            text-align: right;
        }
        .invoice-info h2 {
            margin: 0;
            color: #7c3aed;
            font-size: 22px;
            margin-bottom: 10px;
        }
        .invoice-info p {
            margin: 5px 0;
            font-size: 13px;
            color: #666;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            font-size: 13px;
            margin-bottom: 30px;
        }
        .info-item {
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
            color: #333;
            margin-bottom: 3px;
            font-size: 12px;
        }
        .info-value {
            color: #666;
            padding-left: 10px;
            font-size: 13px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 12px;
        }
        table th {
            background-color: #7c3aed;
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
            width: 60%;
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
            border-bottom: 2px solid #7c3aed;
            font-weight: bold;
            font-size: 15px;
            padding: 10px 0;
        }
        .status-box {
            background-color: #f0f4ff;
            border-left: 4px solid #7c3aed;
            padding: 15px;
            margin: 20px 0;
            font-size: 13px;
        }
        .status-label {
            font-weight: bold;
            color: #333;
        }
        .status-value {
            color: #666;
            margin-top: 5px;
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
            <div class="company-info">
                <h1>{{ config('app.name') }}</h1>
                <p>{{ setting('organization_name', 'Kenya School') }}</p>
                <p>P.O. Box: 123456, Nairobi, Kenya</p>
                <p>Tel: +254 20 XXX XXXX | Email: finance@school.ke</p>
            </div>
            <div class="invoice-info">
                <h2>INVOICE</h2>
                <p><strong>Invoice #:</strong> {{ $invoice->reference_number }}</p>
                <p><strong>Invoice Date:</strong> {{ $invoice->invoice_date->format('M d, Y') }}</p>
                <p><strong>PO Reference:</strong> {{ $invoice->purchase_order?->reference_number ?? 'N/A' }}</p>
                <p><strong>Due Date:</strong> {{ $invoice->due_date->format('M d, Y') }}</p>
            </div>
        </div>

        <!-- Supplier & Bill To -->
        <div class="info-grid">
            <div>
                <div style="font-weight: bold; margin-bottom: 10px; color: #333; border-bottom: 2px solid #7c3aed; padding-bottom: 8px;">BILL FROM</div>
                <div class="info-item">
                    <div class="info-label">{{ $invoice->supplier->name }}</div>
                    <div class="info-value">
                        Contact: {{ $invoice->supplier->contact_name }}<br>
                        Phone: {{ $invoice->supplier->phone }}<br>
                        Email: {{ $invoice->supplier->email }}<br>
                        Tax ID: {{ $invoice->supplier->tax_identification_number }}
                    </div>
                </div>
            </div>
            <div>
                <div style="font-weight: bold; margin-bottom: 10px; color: #333; border-bottom: 2px solid #7c3aed; padding-bottom: 8px;">BILL TO</div>
                <div class="info-item">
                    <div class="info-label">{{ setting('organization_name', 'Kenya School') }}</div>
                    <div class="info-value">
                        P.O. Box: 123456<br>
                        Nairobi, Kenya<br>
                        Tel: +254 20 XXX XXXX
                    </div>
                </div>
            </div>
        </div>

        <!-- Three-Way Match Status -->
        <div class="status-box">
            <div class="status-label">THREE-WAY MATCH STATUS</div>
            <div class="status-value">
                PO Match: <strong style="color: {{ $invoice->po_matched ? '#16a34a' : '#dc2626' }};">{{ $invoice->po_matched ? 'MATCHED' : 'PENDING' }}</strong> | 
                GRN Match: <strong style="color: {{ $invoice->grn_matched ? '#16a34a' : '#dc2626' }};">{{ $invoice->grn_matched ? 'MATCHED' : 'PENDING' }}</strong> | 
                Invoice Status: <strong style="color: #7c3aed;">{{ strtoupper($invoice->status) }}</strong>
            </div>
        </div>

        <!-- Line Items -->
        <table>
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="40%">Description</th>
                    <th width="10%" class="text-right">Qty</th>
                    <th width="10%" class="text-right">Unit</th>
                    <th width="15%" class="text-right">Unit Price</th>
                    <th width="15%" class="text-right">Line Total</th>
                    <th width="5%" class="text-right">VAT</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $idx => $item)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                    <td class="text-right">{{ $item->unit }}</td>
                    <td class="text-right">KES {{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">KES {{ number_format($item->quantity * $item->unit_price, 2) }}</td>
                    <td class="text-right">16%</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-section">
            <div class="totals-row">
                <span>Subtotal (before tax):</span>
                <span>KES {{ number_format($invoice->items->sum(fn($i) => $i->quantity * $i->unit_price), 2) }}</span>
            </div>
            <div class="totals-row">
                <span>VAT (16%):</span>
                <span>KES {{ number_format($invoice->vat_amount ?? 0, 2) }}</span>
            </div>
            @if($invoice->wht_amount > 0)
            <div class="totals-row">
                <span>WHT Deduction ({{ $invoice->wht_rate ?? 5 }}%):</span>
                <span>KES ({{ number_format($invoice->wht_amount, 2) }})</span>
            </div>
            @endif
            <div class="totals-row total">
                <span>AMOUNT DUE (KES):</span>
                <span>KES {{ number_format($invoice->total_amount, 2) }}</span>
            </div>
        </div>

        <!-- Payment Instructions -->
        <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; font-size: 12px;">
            <strong>PAYMENT INSTRUCTIONS</strong><br>
            Bank: Kenya Commercial Bank | Account: School Procurement Account<br>
            Swift Code: KCBLKENA | Reference: Invoice #{{ $invoice->reference_number }}
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Please retain this invoice for your records. Payment must be received by {{ $invoice->due_date->format('M d, Y') }}.<br>
            For payment inquiries, contact: {{ setting('contact_email', 'finance@school.ke') }} | Generated: {{ now()->format('M d, Y H:i') }}</p>
        </div>
    </div>
</body>
</html>
