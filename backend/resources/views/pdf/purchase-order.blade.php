<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order - {{ $po->po_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header-top {
            display: flex;
            justify-content: space-between;
        }
        .company-info h1 {
            font-size: 24px;
            color: #2563eb;
            margin-bottom: 5px;
        }
        .company-info p {
            color: #666;
            font-size: 11px;
        }
        .po-info {
            text-align: right;
        }
        .po-info h2 {
            font-size: 20px;
            color: #333;
            margin-bottom: 5px;
        }
        .po-number {
            font-size: 16px;
            font-weight: bold;
            color: #2563eb;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 5px;
        }
        .status-draft { background: #fef3c7; color: #92400e; }
        .status-approved { background: #d1fae5; color: #065f46; }
        .status-received { background: #dbeafe; color: #1e40af; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .status-partially_received { background: #e0e7ff; color: #3730a3; }
        
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .info-box {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 15px;
        }
        .info-box:last-child {
            padding-right: 0;
            padding-left: 15px;
        }
        .info-box h3 {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 8px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .info-box p {
            margin-bottom: 3px;
        }
        .info-box .label {
            color: #666;
            font-size: 10px;
        }
        .info-box .value {
            font-weight: bold;
        }
        
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.items th {
            background: #f3f4f6;
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            color: #666;
        }
        table.items td {
            border: 1px solid #ddd;
            padding: 8px;
            vertical-align: top;
        }
        table.items tr:nth-child(even) {
            background: #f9fafb;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        
        .totals {
            width: 300px;
            margin-left: auto;
            margin-bottom: 20px;
        }
        .totals table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals td {
            padding: 5px 10px;
            border-bottom: 1px solid #eee;
        }
        .totals tr:last-child td {
            border-bottom: 2px solid #333;
            font-weight: bold;
            font-size: 14px;
        }
        .totals .label {
            color: #666;
        }
        .totals .amount {
            text-align: right;
        }
        
        .payment-info {
            background: #f9fafb;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .payment-info h3 {
            font-size: 12px;
            margin-bottom: 10px;
            color: #333;
        }
        .payment-status {
            display: table;
            width: 100%;
        }
        .payment-status > div {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 10px;
        }
        .payment-status .amount {
            font-size: 18px;
            font-weight: bold;
        }
        .payment-status .label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
        }
        .paid { color: #059669; }
        .outstanding { color: #dc2626; }
        
        .notes {
            background: #fffbeb;
            padding: 10px;
            border-radius: 5px;
            border-left: 3px solid #f59e0b;
            margin-bottom: 20px;
        }
        .notes h4 {
            font-size: 11px;
            color: #92400e;
            margin-bottom: 5px;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
            text-align: center;
        }
        
        .signatures {
            display: table;
            width: 100%;
            margin-top: 50px;
        }
        .signature-box {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 0 20px;
        }
        .signature-line {
            border-top: 1px solid #333;
            padding-top: 5px;
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <div class="header">
        <table width="100%">
            <tr>
                <td width="60%">
                    <div class="company-info">
                        <h1>{{ config('app.name', 'Deshio ERP') }}</h1>
                        <p>Purchase Order Document</p>
                    </div>
                </td>
                <td width="40%" style="text-align: right;">
                    <div class="po-info">
                        <h2>PURCHASE ORDER</h2>
                        <div class="po-number">{{ $po->po_number }}</div>
                        <span class="status-badge status-{{ $po->status }}">{{ ucfirst(str_replace('_', ' ', $po->status)) }}</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="info-section">
        <table width="100%">
            <tr>
                <td width="50%" valign="top" style="padding-right: 15px;">
                    <div class="info-box">
                        <h3>Vendor Information</h3>
                        <p><span class="value">{{ $po->vendor->name ?? 'N/A' }}</span></p>
                        @if($po->vendor?->contact_person)
                            <p><span class="label">Contact:</span> {{ $po->vendor->contact_person }}</p>
                        @endif
                        @if($po->vendor?->phone)
                            <p><span class="label">Phone:</span> {{ $po->vendor->phone }}</p>
                        @endif
                        @if($po->vendor?->email)
                            <p><span class="label">Email:</span> {{ $po->vendor->email }}</p>
                        @endif
                        @if($po->vendor?->address)
                            <p><span class="label">Address:</span> {{ $po->vendor->address }}</p>
                        @endif
                    </div>
                </td>
                <td width="50%" valign="top" style="padding-left: 15px;">
                    <div class="info-box">
                        <h3>Order Details</h3>
                        <p><span class="label">Order Date:</span> <span class="value">{{ $po->order_date?->format('M d, Y') ?? 'N/A' }}</span></p>
                        <p><span class="label">Expected Delivery:</span> <span class="value">{{ $po->expected_delivery_date?->format('M d, Y') ?? 'N/A' }}</span></p>
                        @if($po->actual_delivery_date)
                            <p><span class="label">Actual Delivery:</span> <span class="value">{{ $po->actual_delivery_date->format('M d, Y') }}</span></p>
                        @endif
                        <p><span class="label">Store:</span> <span class="value">{{ $po->store->name ?? 'N/A' }}</span></p>
                        <p><span class="label">Created By:</span> <span class="value">{{ $po->createdBy->name ?? 'N/A' }}</span></p>
                        @if($po->reference_number)
                            <p><span class="label">Reference:</span> <span class="value">{{ $po->reference_number }}</span></p>
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 30%;">Product</th>
                <th style="width: 10%;">SKU</th>
                <th class="text-center" style="width: 10%;">Qty Ordered</th>
                <th class="text-center" style="width: 10%;">Qty Received</th>
                <th class="text-right" style="width: 12%;">Unit Cost</th>
                <th class="text-right" style="width: 10%;">Tax</th>
                <th class="text-right" style="width: 13%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($po->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $item->product_name }}</strong>
                        @if($item->notes)
                            <br><small style="color: #666;">{{ $item->notes }}</small>
                        @endif
                    </td>
                    <td>{{ $item->product_sku }}</td>
                    <td class="text-center">{{ number_format($item->quantity_ordered) }}</td>
                    <td class="text-center">
                        {{ number_format($item->quantity_received) }}
                        @if($item->quantity_pending > 0)
                            <br><small style="color: #dc2626;">({{ $item->quantity_pending }} pending)</small>
                        @endif
                    </td>
                    <td class="text-right">৳{{ number_format($item->unit_cost, 2) }}</td>
                    <td class="text-right">৳{{ number_format($item->tax_amount, 2) }}</td>
                    <td class="text-right"><strong>৳{{ number_format($item->total_cost, 2) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <td class="label">Subtotal</td>
                <td class="amount">৳{{ number_format($po->subtotal, 2) }}</td>
            </tr>
            @if($po->tax_amount > 0)
                <tr>
                    <td class="label">Tax</td>
                    <td class="amount">৳{{ number_format($po->tax_amount, 2) }}</td>
                </tr>
            @endif
            @if($po->discount_amount > 0)
                <tr>
                    <td class="label">Discount</td>
                    <td class="amount">-৳{{ number_format($po->discount_amount, 2) }}</td>
                </tr>
            @endif
            @if($po->shipping_cost > 0)
                <tr>
                    <td class="label">Shipping</td>
                    <td class="amount">৳{{ number_format($po->shipping_cost, 2) }}</td>
                </tr>
            @endif
            @if($po->other_charges > 0)
                <tr>
                    <td class="label">Other Charges</td>
                    <td class="amount">৳{{ number_format($po->other_charges, 2) }}</td>
                </tr>
            @endif
            <tr>
                <td class="label"><strong>Total Amount</strong></td>
                <td class="amount"><strong>৳{{ number_format($po->total_amount, 2) }}</strong></td>
            </tr>
        </table>
    </div>

    <div class="payment-info">
        <h3>Payment Status: <span style="text-transform: uppercase;">{{ str_replace('_', ' ', $po->payment_status) }}</span></h3>
        <div class="payment-status">
            <div>
                <div class="amount">৳{{ number_format($po->total_amount, 2) }}</div>
                <div class="label">Total Amount</div>
            </div>
            <div>
                <div class="amount paid">৳{{ number_format($po->paid_amount, 2) }}</div>
                <div class="label">Paid Amount</div>
            </div>
            <div>
                <div class="amount outstanding">৳{{ number_format($po->outstanding_amount, 2) }}</div>
                <div class="label">Outstanding</div>
            </div>
        </div>
    </div>

    @if($po->notes)
        <div class="notes">
            <h4>Notes</h4>
            <p>{{ $po->notes }}</p>
        </div>
    @endif

    @if($po->terms_and_conditions)
        <div class="notes" style="background: #f0f9ff; border-color: #3b82f6;">
            <h4 style="color: #1e40af;">Terms & Conditions</h4>
            <p>{{ $po->terms_and_conditions }}</p>
        </div>
    @endif

    <div class="signatures">
        <table width="100%">
            <tr>
                <td width="33%" style="text-align: center; padding: 0 20px;">
                    <div class="signature-line">Prepared By</div>
                </td>
                <td width="33%" style="text-align: center; padding: 0 20px;">
                    <div class="signature-line">Approved By</div>
                </td>
                <td width="33%" style="text-align: center; padding: 0 20px;">
                    <div class="signature-line">Received By</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>Generated on {{ now()->format('M d, Y h:i A') }} | {{ config('app.name', 'Deshio ERP') }}</p>
    </div>
</body>
</html>
