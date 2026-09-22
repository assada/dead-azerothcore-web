@php
    $code = $exception->getStatusCode();
    [$title, $message, $action, $href] = match ($code) {
        401 => ['Your adventure awaits', 'Sign in to continue to this page.', 'Log in', '/login'],
        403 => ['This path is closed', 'You do not have access to this page.', 'My account', '/profile'],
        404 => ['Off the map', 'We could not find this page. Even the best adventurers take a wrong turn.', 'Return home', '/'],
        405 => ['A wrong turn', 'This page does not support that action.', 'Return home', '/'],
        410 => ['Lost to the ages', 'This page is no longer available.', 'Return home', '/'],
        419 => ['Time to regroup', 'Your session expired. Sign in again to continue.', 'Log in', '/login'],
        429 => ['Still on cooldown', 'Too many requests. Wait a moment before trying again.', 'Return home', '/'],
        503 => ['A brief intermission', 'The website is temporarily unavailable. Please try again soon.', 'Return home', '/'],
        default => $code >= 500
            ? ['A disturbance in Azeroth', 'Something went wrong on the website. Please try again in a moment.', 'Return home', '/']
            : ['The journey stops here', 'We could not complete this request.', 'Return home', '/'],
    };
    if ($exception instanceof \Illuminate\Routing\Exceptions\InvalidSignatureException) {
        [$title, $message, $action, $href] = ['This link has faded', 'This link has expired or is invalid. Open your account to request a new email.', 'My account', '/profile'];
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="color-scheme" content="dark">
    <title>{{ $code }} · {{ $title }} · {{ config('app.name') }}</title>
    @if(config('site.favicon'))<link rel="icon" href="{{ asset(config('site.favicon')) }}">@endif
    {{-- Keep error pages usable when the asset manifest, session or database is unavailable. --}}
    <style>@include('errors.styles')</style>
</head>
<body>
    <header class="error-header">
        <a href="/" aria-label="{{ config('app.name') }} home"><x-application-logo /></a>
    </header>
    <main class="error-scene" aria-labelledby="error-title">
        <div class="error-content">
            <p class="error-code">{{ $code }}</p>
            <h1 id="error-title">{{ $title }}</h1>
            <p class="error-message">{{ $message }}</p>
            <a class="error-action" href="{{ $href }}">{{ $action }}</a>
        </div>
    </main>
    <footer class="error-footer"><span>{{ config('app.name') }}</span><span>{{ config('wow.expansion') }}</span></footer>
</body>
</html>
