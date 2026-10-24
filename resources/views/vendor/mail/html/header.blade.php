@props(['url'])
@php
    $cs = \App\Models\CompanySetting::first();
    $logoUrl = ($cs && $cs->business_logo) ? asset('storage/' . $cs->business_logo) : null;
    $brandName = ($cs && $cs->business_name) ? $cs->business_name : config('app.name');
@endphp
<tr>
<td class="header" style="text-align: center; padding: 25px 0;">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
@if ($logoUrl)
<img src="{{ $logoUrl }}" class="logo" alt="{{ $brandName }}" style="max-height: 48px; max-width: 220px; object-fit: contain;">
@elseif (trim($slot) === 'Laravel')
<span style="font-size: 20px; font-weight: 700; color: #1e293b; letter-spacing: -0.5px;">{{ $brandName }}</span>
@else
{!! $slot !!}
@endif
</a>
</td>
</tr>
