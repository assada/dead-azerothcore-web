@props(['url'])
<tr>
<td class="header" align="center">
<a href="{{ $url }}">
@if(config('site.logo'))
<img src="{{ url(config('site.logo')) }}" alt="{{ config('app.name') }}" class="logo">
@else
{{ config('app.name') }}
@endif
</a>
</td>
</tr>
