<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $transaction->invoice_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 8px;
            width: 30mm;
            max-width: 30mm;
            font-size: 10px;
            line-height: 1.3;
        }
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 6px;
            margin-bottom: 6px;
        }
        h1 {
            margin: 0;
            font-size: 12px;
            font-weight: bold;
        }
        .meta, .totals div, .line-row {
            display: flex;
            justify-content: space-between;
            gap: 4px;
        }
        .meta {
            font-size: 9px;
            margin-top: 2px;
        }
        .muted {
            font-size: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
            font-size: 8px;
        }
        th, td {
            padding: 2px 0;
            text-align: left;
            vertical-align: top;
            border-bottom: 1px dashed #ddd;
        }
        th:nth-child(n+2), td:nth-child(n+2) { text-align: right; }
        .totals {
            border-top: 1px dashed #000;
            padding-top: 6px;
            margin-top: 6px;
            font-size: 9px;
        }
        .totals div { padding: 1px 0; }
        .grand-total {
            font-weight: bold;
            font-size: 10px;
        }
        .actions {
            margin-top: 10px;
            display: flex;
            gap: 6px;
            justify-content: center;
        }
        .actions a, .actions button {
            background: #206bc4;
            border: 0;
            color: white;
            cursor: pointer;
            padding: 6px 10px;
            font-size: 9px;
            text-decoration: none;
        }
        @media print {
            body {
                width: 30mm;
                max-width: 30mm;
                margin: 0 auto;
                padding: 0;
            }
            .actions { display: none; }
            @page { size: 30mm auto; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ AppSettings::get('app_name', 'Pharmacy') }}</h1>
        <div class="meta"><span>{{ $transaction->invoice_number }}</span><span>{{ $transaction->created_at->format('d/m/Y H:i') }}</span></div>
        <div class="muted">Cashier: {{ optional($transaction->user)->name ?: 'Staff' }}</div>
        @if ($transaction->customer_name)<div class="muted">Customer: {{ $transaction->customer_name }}</div>@endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Amt</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($transaction->lines as $line)
            @php($remainingQuantity = $line->quantity - $line->returned_quantity)
            @if ($remainingQuantity > 0)
            <tr>
                <td>
                    {{ Str::limit(optional($line->product->purchase)->name ?: 'Medicine', 12) }}<br>
                    @foreach ($line->allocations as $allocation)
                        <span class="muted">{{ $allocation->quantity }} × {{ optional($allocation->batch)->batch_number ?: '-' }} / {{ optional(optional($allocation->batch)->expiry_date)->format('m/Y') ?: '-' }}@if (!$loop->last)<br>@endif</span>
                    @endforeach
                </td>
                <td>{{ $remainingQuantity }}</td>
                <td>{{ number_format(($line->quantity > 0 ? $line->total_price / $line->quantity : 0) * $remainingQuantity, 2) }}</td>
            </tr>
            @endif
        @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div><span>Subtotal</span><span>{{ number_format($transaction->subtotal, 2) }}</span></div>
        <div><span>Discount</span><span>{{ number_format($transaction->discount, 2) }}</span></div>
        <div><span>Method</span><span>{{ ucfirst($transaction->payment_method ?? 'cash') }}</span></div>
        <div class="grand-total"><span>Total</span><span>{{ number_format($transaction->total, 2) }}</span></div>
        <div><span>Cash</span><span>{{ number_format($transaction->amount_received, 2) }}</span></div>
        <div><span>Change</span><span>{{ number_format($transaction->change_amount, 2) }}</span></div>
    </div>
    <div class="muted" style="text-align:center; margin-top:6px;">Thank you for your purchase.</div>
    <div class="actions">
        <button type="button" onclick="window.print()">Print bill</button>
        <a href="{{ route('sales') }}">New sale</a>
    </div>
</body>
</html>