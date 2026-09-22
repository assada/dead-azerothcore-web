@props(['title', 'meta' => null])
<div class="page-heading"><h1>{{ $title }}</h1>@if($meta)<span>{{ $meta }}</span>@endif</div>
