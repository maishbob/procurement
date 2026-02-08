<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order #{{ $order->reference_number }}</title>
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
            border-bottom: 3px solid #1e40af;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .company-info h1 {
            margin: 0;
            color: #1e40af;
            font-size: 28px;
        }
        .company-info p {
            margin: 5px 0;
            color: #666;
            font-size: 13px;
        }
        .po-info {
            text-align: right;
        }
        .po-info h2 {
            margin: 0;
            color: #1e40af;
            font-size: 20px;
            margin-bottom: 10px;
        }
        .po-info p {
            margin: 5px 0;
            font-size: 13px;
            color: #666;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-weight: bold;
            background-color: #f0f4ff;
            padding: 8px 12px;
            margin-bottom: 10px;
            border-left: 4px solid #1e40af;
            font-size: 13px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            font-size: 13px;
            margin-bottom: 20px;
        }
        .info-item {
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
            color: #333;
            margin-bottom: 3px;
        }
        .info-value {
            color: #666;
            padding-left: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 12px;
        }
        table th {
            background-color: #1e40af;
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
            width: 100%;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }
        .totals-row.total {
            border-bottom: 2px solid #1e40af;
            font-weight: bold;
            font-size: 15px;
            padding: 10px 0;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 11px;
            color: #666;
            text-align: center;
        }
        .terms {
            background-color: #f9f9f9;
            padding: 15px;
            margin-top: 20px;
            border-left: 3px solid #1e40af;
            font-size: 12px;
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
                <p>Tel: +254 20 XXX XXXX | Email: procurement@school.ke</p>
            </div>
            <div class="po-info">
                <h2>PURCHASE ORDER</h2>
                <p><strong>PO Number:</strong> {{ $order->reference_number }}</p>
                <p><strong>Date:</strong> {{ $order->created_at->format('M d, Y') }}</p>
                <p><strong>Valid Until:</strong> {{ $order->delivery_date?->format('M d, Y') ?? 'As per terms' }}</p>
                <p><strong>Status:</strong> <span style="color: #16a34a; font-weight: bold;">{{ strtoupper($order->status) }}</span></p>
            </div>
        </div>

        <!-- Supplier & Delivery Info -->
        <div class="info-grid">
            <div>
                <div class="section-title">SUPPLIER</div>
                <div class="info-item">
                    <div class="info-label">{{ $order->supplier->name }}</div>
                    <div class="info-value">
                        Contact: {{ $order->supplier->contact_name }}<br>
                        Phone: {{ $order->supplier->phone }}<br>
                        Email: {{ $order->supplier->email }}<br>
                        Tax ID: {{ $order->supplier->tax_identification_number }}
                    </div>
                </div>
            </div>
            <div>
                <div class="section-title">DELIVERY</div>
                <div class="info-item">
                    <div class="info-label">Location</div>
                    <div class="info-value">{{ $order->delivery_location ?? 'Main Campus' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Expected Date</div>
                    <div class="info-value">{{ $order->delivery_date?->format('M d, Y') ?? 'Within 30 days' }}</div>
                </div>
            </div>
        </div>

        <!-- Line Items -->
        <table>
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="35%">Description</th>
                    <th width="10%" class="text-right">Qty</th>
                    <th width="10%" class="text-right">Unit</th>
                    <th width="15%" class="text-right">Unit Price (KES)</th>
                    <th width="15%" class="text-right">Amount (KES)</th>
                    <th width="10%" class="text-right">VAT (16%)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $idx => $item)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                    <td class="text-right">{{ $item->unit }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">{{ number_format($item->quantity * $item->unit_price, 2) }}</td>
                    <td class="text-right">{{ number_format(($item->quantity * $item->unit_price) * 0.16, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-section">
            <div class="totals-row">
                <span>Subtotal (before VAT):</span>
                <span>KES {{ number_format($order->items->sum(fn($i) => $i->quantity * $i->unit_price), 2) }}</span>
            </div>
            <div class="totals-row">
                <span>VAT (16%):</span>
                <span>KES {{ number_format($order->items->sum(fn($i) => ($i->quantity * $i->unit_price) * 0.16), 2) }}</span>
            </div>
            <div class="totals-row total">
                <span>TOTAL AMOUNT DUE (KES):</span>
                <span>KES {{ number_format($order->total_amount, 2) }}</span>
            </div>
        </div>

        <!-- Terms -->
        <div class="terms">
            <strong>PAYMENT TERMS & CONDITIONS</strong><br>
            • Payment terms: Net 30 days from invoice date<br>
            • Delivery: FOB Destination (school bears delivery cost if not included)<br>
            • Quality: All items must meet school specifications and quality standards<br>
            • Acceptance: Items subject to inspection upon receipt<br>
            • Late Delivery: Subject to 0.5% daily penalty until delivery<br>
            • This PO supersedes all previous quotations and discussions
        </div>

        <!-- Approval Section -->
        <div style="margin-top: 40px; border-top: 1px solid #ddd; padding-top: 20px;">
            <div style="display: flex; justify-content: space-around; font-size: 12px;">
                <div style="text-align: center; width: 30%;">
                    <p style="margin: 0 0 50px 0;">_____________________</p>
                    <p style="margin: 0;"><strong>Prepared By</strong></p>
                    <p style="margin: 5px 0 0 0; color: #666;">{{ auth()->user()->name ?? 'Procurement Officer' }}</p>
                </div>
                <div style="text-align: center; width: 30%;">
                    <p style="margin: 0 0 50px 0;">_____________________</p>
                    <p style="margin: 0;"><strong>Approved By</strong></p>
                    <p style="margin: 5px 0 0 0; color: #666;">Finance Manager</p>
                </div>
                <div style="text-align: center; width: 30%;">
                    <p style="margin: 0 0 50px 0;">_____________________</p>
                    <p style="margin: 0;"><strong>Principal/Director</strong></p>
                    <p style="margin: 5px 0 0 0; color: #666;">School Principal</p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>This is an official Purchase Order. Please acknowledge receipt and confirm delivery timeline.<br>
            For questions, contact: {{ setting('contact_email', 'procurement@school.ke') }} | Generated: {{ now()->format('M d, Y H:i') }}</p>
        </div>
    </div>
</body>
</html>
