<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Orders Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 22px;
            color: #2563eb;
            margin-bottom: 5px;
        }
        .header p {
            color: #666;
            font-size: 11px;
        }
        .report-title {
            font-size: 16px;
            color: #333;
            margin-top: 5px;
        }
        
        .filters-applied {
            background: #f3f4f6;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 10px;
        }
        .filters-applied span {
            display: inline-block;
            background: #e5e7eb;
            padding: 2px 8px;
            border-radius: 3px;
            margin-right: 5px;
            margin-bottom: 3px;
        }
        
        .summary-boxes {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .summary-box {
            display: table-cell;
            width: 20%;
            text-align: center;
            padding: 10px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
        }
        .summary-box:first-child {
            border-radius: 5px 0 0 5px;
        }
        .summary-box:last-child {
            border-radius: 0 5px 5px 0;
        }
        .summary-box .number {
            font-size: 20px;
            font-weight: bold;
            color: #2563eb;
        }
        .summary-box .label {
            font-size: 9px;
            color: #666;
            text-transform: uppercase;
        }
        .summary-box.total-amount .number { color: #059669; }
        .summary-box.paid .number { color: #2563eb; }
        .summary-box.outstanding .number { color: #dc2626; }
        
        table.report {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10px;
        }
        table.report th {
            background: #1e40af;
            color: white;
            padding: 8px 5px;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
        }
        table.report td {
            border-bottom: 1px solid #e5e7eb;
            padding: 6px 5px;
            vertical-align: top;
        }
        table.report tr:nth-child(even) {
            background: #f9fafb;
        }
        table.report tr:hover {
            background: #f3f4f6;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        
        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-draft { background: #fef3c7; color: #92400e; }
        .status-approved { background: #d1fae5; color: #065f46; }
        .status-received { background: #dbeafe; color: #1e40af; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .status-partially_received { background: #e0e7ff; color: #3730a3; }
        
        .payment-unpaid { color: #dc2626; }
        .payment-partial { color: #f59e0b; }
        .payment-paid { color: #059669; }
        
        .totals-row {
            background: #1e40af !important;
            color: white;
            font-weight: bold;
        }
        .totals-row td {
            border-bottom: none;
            padding: 10px 5px;
        }
        
        .status-summary {
            margin-bottom: 20px;
        }
        .status-summary h3 {
            font-size: 12px;
            margin-bottom: 10px;
            color: #333;
        }
        .status-summary table {
            width: 100%;
            border-collapse: collapse;
        }
        .status-summary td {
            padding: 5px 10px;
            border: 1px solid #e5e7eb;
        }
        
        .vendor-summary {
            page-break-before: always;
            margin-top: 20px;
        }
        .vendor-summary h3 {
            font-size: 14px;
            margin-bottom: 10px;
            color: #1e40af;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 9px;
            color: #666;
            text-align: center;
        }
        .page-number {
            text-align: right;
            font-size: 9px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="header">
        <table width="100%">
            <tr>
                <td width="70%">
                    <h1>{{ config('app.name', 'Deshio ERP') }}</h1>
                    <p class="report-title">Purchase Orders Summary Report</p>
                </td>
                <td width="30%" style="text-align: right; vertical-align: top;">
                    <p><strong>Report Generated:</strong></p>
                    <p>{{ now()->format('M d, Y h:i A') }}</p>
                </td>
            </tr>
        </table>
    </div>

    @if(!empty($filters))
        <div class="filters-applied">
            <strong>Filters Applied:</strong>
            @if(!empty($filters['from_date']))
                <span>From: {{ $filters['from_date'] }}</span>
            @endif
            @if(!empty($filters['to_date']))
                <span>To: {{ $filters['to_date'] }}</span>
            @endif
            @if(!empty($filters['vendor_id']))
                <span>Vendor: {{ $filters['vendor_name'] ?? $filters['vendor_id'] }}</span>
            @endif
            @if(!empty($filters['store_id']))
                <span>Store: {{ $filters['store_name'] ?? $filters['store_id'] }}</span>
            @endif
            @if(!empty($filters['status']))
                <span>Status: {{ ucfirst($filters['status']) }}</span>
            @endif
            @if(!empty($filters['payment_status']))
                <span>Payment: {{ ucfirst($filters['payment_status']) }}</span>
            @endif
        </div>
    @endif

    <table width="100%">
        <tr>
            <td width="20%" style="text-align: center; padding: 10px; background: #f9fafb; border: 1px solid #e5e7eb;">
                <div style="font-size: 20px; font-weight: bold; color: #2563eb;">{{ $summary['total_orders'] }}</div>
                <div style="font-size: 9px; color: #666; text-transform: uppercase;">Total Orders</div>
            </td>
            <td width="20%" style="text-align: center; padding: 10px; background: #f9fafb; border: 1px solid #e5e7eb;">
                <div style="font-size: 20px; font-weight: bold; color: #059669;">৳{{ number_format($summary['total_amount'], 2) }}</div>
                <div style="font-size: 9px; color: #666; text-transform: uppercase;">Total Amount</div>
            </td>
            <td width="20%" style="text-align: center; padding: 10px; background: #f9fafb; border: 1px solid #e5e7eb;">
                <div style="font-size: 20px; font-weight: bold; color: #2563eb;">৳{{ number_format($summary['total_paid'], 2) }}</div>
                <div style="font-size: 9px; color: #666; text-transform: uppercase;">Total Paid</div>
            </td>
            <td width="20%" style="text-align: center; padding: 10px; background: #f9fafb; border: 1px solid #e5e7eb;">
                <div style="font-size: 20px; font-weight: bold; color: #dc2626;">৳{{ number_format($summary['total_outstanding'], 2) }}</div>
                <div style="font-size: 9px; color: #666; text-transform: uppercase;">Outstanding</div>
            </td>
            <td width="20%" style="text-align: center; padding: 10px; background: #f9fafb; border: 1px solid #e5e7eb;">
                <div style="font-size: 20px; font-weight: bold; color: #7c3aed;">{{ $summary['total_items'] }}</div>
                <div style="font-size: 9px; color: #666; text-transform: uppercase;">Total Items</div>
            </td>
        </tr>
    </table>

    <br>

    <table class="report">
        <thead>
            <tr>
                <th style="width: 12%;">PO Number</th>
                <th style="width: 10%;">Date</th>
                <th style="width: 18%;">Vendor</th>
                <th style="width: 12%;">Store</th>
                <th style="width: 8%;" class="text-center">Status</th>
                <th style="width: 8%;" class="text-center">Payment</th>
                <th style="width: 5%;" class="text-center">Items</th>
                <th style="width: 10%;" class="text-right">Total</th>
                <th style="width: 9%;" class="text-right">Paid</th>
                <th style="width: 8%;" class="text-right">Due</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchaseOrders as $po)
                <tr>
                    <td><strong>{{ $po->po_number }}</strong></td>
                    <td>{{ $po->order_date?->format('M d, Y') ?? '-' }}</td>
                    <td>{{ Str::limit($po->vendor->name ?? 'N/A', 25) }}</td>
                    <td>{{ Str::limit($po->store->name ?? 'N/A', 15) }}</td>
                    <td class="text-center">
                        <span class="status-badge status-{{ $po->status }}">
                            {{ Str::limit(ucfirst(str_replace('_', ' ', $po->status)), 10) }}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="payment-{{ $po->payment_status }}">
                            {{ ucfirst($po->payment_status) }}
                        </span>
                    </td>
                    <td class="text-center">{{ $po->items_count ?? $po->items->count() }}</td>
                    <td class="text-right">৳{{ number_format($po->total_amount, 2) }}</td>
                    <td class="text-right">৳{{ number_format($po->paid_amount, 2) }}</td>
                    <td class="text-right">৳{{ number_format($po->outstanding_amount, 2) }}</td>
                </tr>
            @endforeach
            <tr class="totals-row">
                <td colspan="7" class="text-right"><strong>TOTALS:</strong></td>
                <td class="text-right">৳{{ number_format($summary['total_amount'], 2) }}</td>
                <td class="text-right">৳{{ number_format($summary['total_paid'], 2) }}</td>
                <td class="text-right">৳{{ number_format($summary['total_outstanding'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    @if(!empty($statusBreakdown) && count($statusBreakdown) > 0)
        <div class="status-summary">
            <h3>Status Breakdown</h3>
            <table>
                <tr>
                    @foreach($statusBreakdown as $status)
                        <td style="text-align: center; width: {{ 100 / count($statusBreakdown) }}%;">
                            <span class="status-badge status-{{ $status->status }}">{{ ucfirst(str_replace('_', ' ', $status->status)) }}</span>
                            <br>
                            <strong>{{ $status->count }}</strong> orders
                            <br>
                            <small>৳{{ number_format($status->total, 2) }}</small>
                        </td>
                    @endforeach
                </tr>
            </table>
        </div>
    @endif

    @if(!empty($vendorBreakdown) && count($vendorBreakdown) > 0)
        <div style="margin-top: 20px;">
            <h3 style="font-size: 12px; margin-bottom: 10px; color: #333;">Top Vendors by Purchase Amount</h3>
            <table class="report">
                <thead>
                    <tr>
                        <th>Vendor</th>
                        <th class="text-center">Orders</th>
                        <th class="text-right">Total Amount</th>
                        <th class="text-right">Paid</th>
                        <th class="text-right">Outstanding</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vendorBreakdown->take(10) as $vendor)
                        <tr>
                            <td>{{ $vendor->vendor_name }}</td>
                            <td class="text-center">{{ $vendor->order_count }}</td>
                            <td class="text-right">৳{{ number_format($vendor->total_amount, 2) }}</td>
                            <td class="text-right">৳{{ number_format($vendor->paid_amount, 2) }}</td>
                            <td class="text-right">৳{{ number_format($vendor->outstanding, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="footer">
        <p>This is a computer-generated report. No signature required.</p>
        <p>Generated on {{ now()->format('M d, Y h:i A') }} | {{ config('app.name', 'Deshio ERP') }}</p>
    </div>
</body>
</html>
