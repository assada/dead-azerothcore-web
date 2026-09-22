<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<title>{{ config('app.name') }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<style>
@media only screen and (max-width: 620px) {
    .inner-body, .footer { width: 100% !important; }
    .body { padding: 0 14px !important; }
    .content-cell { padding: 30px 24px !important; }
    .header { padding: 24px 0 20px !important; }
    h1 { font-size: 28px !important; }
    .footer .content-cell { padding: 24px 14px !important; }
}
</style>
{!! $head ?? '' !!}
</head>
<body>
<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation" bgcolor="#151410">
<tr><td align="center">
<table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
{!! $header ?? '' !!}
<tr><td class="body" align="center">
<table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation" bgcolor="#e8d7b2">
<tr><td class="content-cell">
{!! Illuminate\Mail\Markdown::parse($slot) !!}
{!! $subcopy ?? '' !!}
</td></tr>
</table>
</td></tr>
{!! $footer ?? '' !!}
</table>
</td></tr>
</table>
</body>
</html>
