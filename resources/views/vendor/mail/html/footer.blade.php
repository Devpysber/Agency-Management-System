@php
    $cs = \App\Models\CompanySetting::first();
    $companyName = ($cs && $cs->business_name) ? $cs->business_name : config('app.name');
    $companyEmail = ($cs && $cs->business_email) ? $cs->business_email : 'contact@psyber.co';
    $companyPhone = ($cs && $cs->business_phone) ? $cs->business_phone : null;
@endphp
<tr>
<td>
<table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="content-cell" align="center" style="font-size: 12px; color: #64748b; line-height: 1.6; padding: 32px 0;">
{{ Illuminate\Mail\Markdown::parse($slot) }}
<div style="margin-top: 8px; color: #94a3b8; font-size: 11px;">
&copy; {{ date('Y') }} {{ $companyName }}. All rights reserved.<br>
{{ $companyEmail }} @if($companyPhone)&bull; {{ $companyPhone }} @endif
</div>
</td>
</tr>
</table>
</td>
</tr>
