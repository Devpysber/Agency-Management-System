{{-- dompdf: keep CSS simple (no flex/grid), use tables for layout --}}
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { color: #1f2937; font-size: 12px; margin: 0; }
    .wrap { padding: 8px 4px; }
    h1 { font-size: 22px; letter-spacing: 3px; margin: 0 0 2px; }
    .muted { color: #6b7280; }
    .small { font-size: 11px; }
    table { width: 100%; border-collapse: collapse; }
    .head td { vertical-align: top; padding-bottom: 24px; }
    .head .right { text-align: right; }
    .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; background: #eef2ff; color: #4338ca; font-size: 10px; }
    .items th { text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; border-bottom: 2px solid #d1d5db; padding: 8px 6px; }
    .items td { padding: 9px 6px; border-bottom: 1px solid #e5e7eb; }
    .items .num { text-align: right; }
    .totals { width: 240px; margin-left: auto; margin-top: 14px; }
    .totals td { padding: 5px 6px; }
    .totals .num { text-align: right; }
    .totals .grand td { border-top: 2px solid #d1d5db; font-size: 15px; font-weight: bold; padding-top: 10px; }
    .notes { margin-top: 26px; border-top: 1px solid #e5e7eb; padding-top: 12px; }
    .notes h4 { font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; margin: 0 0 4px; }
</style>
</head>
<body>
<div class="wrap">
    <table class="head">
        <tr>
            <td>
                <h1>ESTIMATE</h1>
                <div class="muted">{{ $estimate->estimate_number }}</div>
            </td>
            <td class="right small">
                <div><span class="badge">{{ ucfirst($estimate->status) }}</span></div>
                <div class="muted" style="margin-top:6px;">
                    <strong>Issue date:</strong> {{ optional($estimate->issue_date)->format('M d, Y') ?? 'N/A' }}<br>
                    <strong>Valid until:</strong> {{ optional($estimate->valid_until)->format('M d, Y') ?? 'N/A' }}<br>
                    <strong>Billed to:</strong> {{ $estimate->client_display_name }}
                </div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Description</th>
                <th class="num" style="width:60px;">Qty</th>
                <th class="num" style="width:90px;">Unit Price</th>
                <th class="num" style="width:90px;">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($estimate->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="num">{{ $item->qty }}</td>
                    <td class="num">@money((float) $item->unit_price)</td>
                    <td class="num">@money((float) $item->qty * (float) $item->unit_price)</td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">No line items.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals">
        <tr><td class="muted">Subtotal</td><td class="num">@money((float) $estimate->subtotal)</td></tr>
        <tr><td class="muted">Tax</td><td class="num">@money((float) ($estimate->tax ?? 0))</td></tr>
        <tr class="grand"><td>Total</td><td class="num">@money((float) $estimate->total)</td></tr>
    </table>

    @if ($estimate->notes)
        <div class="notes">
            <h4>Notes</h4>
            <div>{{ $estimate->notes }}</div>
        </div>
    @endif
</div>
</body>
</html>
