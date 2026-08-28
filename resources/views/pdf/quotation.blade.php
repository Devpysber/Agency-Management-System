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
    .dl td { padding: 8px 6px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
    .dl .k { width: 150px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; }
    .amount { font-size: 18px; font-weight: bold; margin-top: 10px; }
</style>
</head>
<body>
<div class="wrap">
    <table class="head">
        <tr>
            <td>
                <h1>QUOTATION</h1>
                <div class="muted">{{ $quotation->service_interest ?: 'Service enquiry' }}</div>
            </td>
            <td class="right small">
                <div><span class="badge">{{ ucfirst($quotation->status) }}</span></div>
                <div class="muted" style="margin-top:6px;">
                    <strong>Submitted:</strong> {{ optional($quotation->created_at)->format('M d, Y') ?? 'N/A' }}<br>
                    <strong>Responded:</strong> {{ optional($quotation->responded_at)->format('M d, Y') ?? '—' }}
                </div>
            </td>
        </tr>
    </table>

    <table class="dl">
        <tr><td class="k">Service Interest</td><td>{{ $quotation->service_interest ?: 'N/A' }}</td></tr>
        <tr><td class="k">Contact Name</td><td>{{ $quotation->name ?: 'N/A' }}</td></tr>
        <tr><td class="k">Email</td><td>{{ $quotation->email ?: 'N/A' }}</td></tr>
        <tr><td class="k">Phone</td><td>{{ $quotation->phone ?: 'N/A' }}</td></tr>
        <tr><td class="k">Message</td><td>{{ $quotation->message ?: 'No message provided.' }}</td></tr>
    </table>

    <div class="amount">
        Quoted amount:
        {{ $quotation->quoted_amount !== null ? \App\Support\Money::client((float) $quotation->quoted_amount) : 'Not yet quoted' }}
    </div>
</div>
</body>
</html>
