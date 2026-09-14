<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $transaction->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #222; margin: 32px auto; max-width: 760px; }
        .header { border-bottom: 2px solid #222; margin-bottom: 20px; padding-bottom: 12px; }
        h1 { margin: 0 0 6px; font-size: 24px; }
        .meta, .totals { display: flex; justify-content: space-between; gap: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border-bottom: 1px solid #ddd; padding: 9px 4px; text-align: left; }
        th:nth-child(n+2), td:nth-child(n+2) { text-align: right; }
        .totals { border-top: 2px solid #222; padding-top: 8px; 
        /* margin-left: auto;  */
        /* max-width: 280px;  */
    }
        .totals div { display: flex; justify-content: space-between; gap: 18px; padding: 3px 0; flex-direction: column; }
        .grand-total { font-weight: bold; font-size: 18px; }
        .actions { margin: 24px 0; }
        .actions a, .actions button { background: #206bc4; border: 0; color: white; cursor: pointer; padding: 10px 14px; text-decoration: none; margin-right: 8px; }
        @media print { .actions { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ AppSettings::get('app_name', 'Pharmacy') }}</h1>
        <div class="meta"><span>Invoice: {{ $transaction->invoice_number }}</span><span>{{ $transaction->created_at->format('d M Y H:i') }}</span></div>
        <div>Cashier: {{ optional($transaction->user)->name ?: 'Staff' }}</div>
        @if ($transaction->customer_name)<div>Customer: {{ $transaction->customer_name }}</div>@endif
    </div>

    <table>
        <thead><tr><th>Medicine</th><th>Qty</th><th>Unit price</th><th>Total</th></tr></thead>
        <tbody>
        @foreach ($transaction->lines as $line)
            <tr>
                <td>{{ optional($line->product->purchase)->name ?: 'Medicine' }}</td>
                <td>{{ $line->quantity }}</td>
                <td>{{ AppSettings::get('app_currency', '$') }} {{ number_format($line->total_price / $line->quantity, 2) }}</td>
                <td>{{ AppSettings::get('app_currency', '$') }} {{ number_format($line->total_price, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div><span>Subtotal</span><span>{{ AppSettings::get('app_currency', '$') }} {{ number_format($transaction->subtotal, 2) }}</span></div>
        <div><span>Discount</span><span>{{ AppSettings::get('app_currency', '$') }} {{ number_format($transaction->discount, 2) }}</span></div>
        <div class="grand-total"><span>Total</span><span>{{ AppSettings::get('app_currency', '$') }} {{ number_format($transaction->total, 2) }}</span></div>
        <div><span>Cash received</span><span>{{ AppSettings::get('app_currency', '$') }} {{ number_format($transaction->amount_received, 2) }}</span></div>
        <div><span>Change</span><span>{{ AppSettings::get('app_currency', '$') }} {{ number_format($transaction->change_amount, 2) }}</span></div>
    </div>
    <p>Thank you for your purchase.</p>
    <div class="actions">
        <button type="button" onclick="window.print()">Print bill</button>
        <a href="{{ route('sales') }}">New sale</a>
    </div>
</body>
</html>