<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Bill {{ $bill->bill_number }}</title>
    <style>
        body { color: #212529; font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1, h2, p { margin: 0 0 8px; }
        h1 { color: #0d6efd; font-size: 22px; }
        h2 { font-size: 15px; }
        .header, .details { margin-bottom: 24px; }
        .muted { color: #6c757d; }
        .right { text-align: right; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #dee2e6; padding: 8px; }
        th { background: #f1f3f5; text-align: left; }
        .amount { text-align: right; }
        .total { font-weight: bold; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td style="width: 55%">
                <h1>Maintenance Bill</h1>
                <p>{{ $bill->bill_number }}</p>
            </td>
            <td class="right">
                <h2>{{ $society?->name ?? config('app.name') }}</h2>
                @if ($society?->address_line1)<p>{{ $society->address_line1 }}</p>@endif
                @if ($society?->city)<p>{{ $society->city }}{{ $society->state ? ', '.$society->state : '' }}</p>@endif
                @if ($society?->email)<p>{{ $society->email }}</p>@endif
            </td>
        </tr>
    </table>

    <table class="details">
        <tr><th>Resident</th><td>{{ $bill->resident?->name ?? 'Occupant' }}</td><th>Flat</th><td>{{ $bill->flat?->number ?? 'N/A' }}</td></tr>
        <tr><th>Period</th><td>{{ $bill->period }}</td><th>Bill date</th><td>{{ $bill->bill_date?->format('d M Y') ?? '—' }}</td></tr>
        <tr><th>Due date</th><td>{{ $bill->due_date?->format('d M Y') ?? '—' }}</td><th>Status</th><td>{{ ucfirst($bill->status) }}</td></tr>
    </table>

    <table>
        <thead>
            <tr><th>Description</th><th>Type</th><th class="amount">Amount</th><th class="amount">Tax</th></tr>
        </thead>
        <tbody>
            @forelse ($bill->line_items ?? [] as $item)
                <tr>
                    <td>{{ $item['head'] ?? '—' }}</td>
                    <td>{{ str_replace('_', ' ', $item['type'] ?? '') }}</td>
                    <td class="amount">{{ money($item['amount'] ?? 0) }}</td>
                    <td class="amount">{{ money($item['tax'] ?? 0) }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Maintenance charges</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr><td colspan="3" class="amount">Subtotal</td><td class="amount">{{ money($bill->subtotal) }}</td></tr>
            <tr><td colspan="3" class="amount">Tax</td><td class="amount">{{ money($bill->tax_amount) }}</td></tr>
            @if ($bill->late_fee > 0)
                <tr><td colspan="3" class="amount">Late fee</td><td class="amount">{{ money($bill->late_fee) }}</td></tr>
            @endif
            @if ($bill->discount > 0)
                <tr><td colspan="3" class="amount">Discount</td><td class="amount">-{{ money($bill->discount) }}</td></tr>
            @endif
            <tr class="total"><td colspan="3" class="amount">Total</td><td class="amount">{{ money($bill->total) }}</td></tr>
            <tr><td colspan="3" class="amount">Paid</td><td class="amount">{{ money($bill->paid_amount) }}</td></tr>
            <tr class="total"><td colspan="3" class="amount">Balance due</td><td class="amount">{{ money($bill->balance) }}</td></tr>
        </tfoot>
    </table>

    @if ($bill->notes)
        <p class="muted" style="margin-top: 20px">{{ $bill->notes }}</p>
    @endif
</body>
</html>
