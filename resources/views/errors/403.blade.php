<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>403 — {{ __('Forbidden') }}</title>
<style>body{font:16px/1.5 system-ui,sans-serif;background:#f5f5f7;color:#20202a;padding:40px;text-align:center}a{color:#5142c1}</style></head>
<body><main><h1>403</h1><p>{{ __('Forbidden') }}</p><a href="{{ route('index') }}">{{ __('Back to home') }}</a></main></body>
</html>
