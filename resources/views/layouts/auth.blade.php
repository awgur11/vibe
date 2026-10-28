<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('Аккаунт')) — Vibe</title>
    <style>
        *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:#f5f5f7;color:#20202a;font:16px/1.5 system-ui,sans-serif}.card{width:100%;max-width:440px;background:white;padding:36px;border-radius:20px;border:1px solid #e6e6eb;box-shadow:0 12px 40px #20202a08}.brand{font-weight:800;font-size:24px;color:#6151d5;text-decoration:none}h1{font-size:26px;margin:24px 0 8px}p{color:#676774}label{display:block;margin-top:18px;font-weight:600}input:not([type=checkbox]){width:100%;padding:12px;margin-top:6px;border:1px solid #cfcfd9;border-radius:9px;font:inherit}input:focus{outline:2px solid #6151d5;outline-offset:2px}.check{display:flex;align-items:center;gap:8px;font-weight:400}button{width:100%;padding:13px;margin-top:24px;border:0;border-radius:9px;background:#6151d5;color:white;font:600 16px system-ui;cursor:pointer}button:hover{background:#4d3fba}a{color:#5142c1}.links{display:flex;flex-wrap:wrap;gap:12px;justify-content:space-between;margin-top:22px;font-size:14px}.status,.errors{padding:12px 16px;border-radius:9px;margin-top:18px}.status{background:#edf8ef;color:#24683b}.errors{background:#fff0f0;color:#a52626}.errors ul{margin:0;padding-left:18px}.details{background:#f5f5f7;border-radius:9px;padding:14px;overflow-wrap:anywhere}
    </style>
</head>
<body>
<main class="card">
    <a class="brand" href="{{ route('index') }}">Vibe</a>
    @if(session('status'))<div class="status" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())
        <div class="errors" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    @yield('content')
</main>
</body>
</html>
