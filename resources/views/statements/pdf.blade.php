<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .muted { color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border-bottom: 1px solid #e2e8f0; padding: 6px 4px; text-align: left; }
        th { font-size: 10px; text-transform: uppercase; color: #64748b; }
        .right { text-align: right; }
        .total { font-weight: bold; }
    </style>
</head>
<body>
    <h1>{{ config('city.name') }}</h1>
    <p class="muted">Account statement — generated {{ now()->toFormattedDateString() }}</p>
    <p>
        <strong>Account:</strong> {{ $account->account_number }}<br>
        <strong>Customer:</strong> {{ $account->holder_name }}<br>
        <strong>Property:</strong> {{ $account->property->fullAddress() }}<br>
        <strong>Period:</strong> {{ $from->toDateString() }} to {{ $to->toDateString() }}
    </p>
    <table>
        <tr><td class="total" colspan="3">Opening balance</td><td class="right total">{{ money($opening) }}</td></tr>
    </table>
    <table>
        <thead><tr><th>Date</th><th>Description</th><th>Type</th><th class="right">Amount</th></tr></thead>
        <tbody>
            @foreach ($lines as $line)
                <tr>
                    <td>{{ $line->date->toDateString() }}</td>
                    <td>{{ $line->description }}</td>
                    <td>{{ ucfirst($line->kind) }}</td>
                    <td class="right">{{ money($line->amount_cents) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr><td class="total" colspan="3">Closing balance</td><td class="right total">{{ money($closing) }}</td></tr>
        </tfoot>
    </table>
    <p class="muted" style="margin-top:24px">This is a computer-generated statement. Queries: {{ config('city.support_email') }}</p>
</body>
</html>
