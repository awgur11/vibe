<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('Личный кабинет')) — Vibe</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#f5f5f7;color:#20202a;font:16px/1.5 system-ui,sans-serif}a{color:#5142c1}button,input,textarea{font:inherit}button,a,input{outline-offset:3px}.shell{display:flex;min-height:100vh}.sidebar{width:240px;flex-shrink:0;padding:28px 20px;background:white;border-right:1px solid #e6e6eb;display:flex;flex-direction:column;gap:28px}.brand{font-size:26px;font-weight:800;color:#6151d5;text-decoration:none}.nav{display:grid;gap:8px}.nav a{display:flex;align-items:center;gap:12px;padding:12px;border-radius:10px;text-decoration:none;color:#555565}.nav a:hover,.nav a.selected{background:#eeeafd;color:#5142c1}.icon{width:22px;height:22px;flex-shrink:0}.account{margin-top:auto;font-size:14px;overflow-wrap:anywhere}.account p{margin:4px 0 16px}.content{flex:1;min-width:0;padding:40px;max-width:1400px}.heading{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:24px}h1{font-size:28px;margin:0 0 8px}p{color:#676774}.heading p{margin:0}.panel{padding:24px;background:white;border:1px solid #e6e6eb;border-radius:16px}.button{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:10px 16px;border:1px solid transparent;border-radius:9px;background:#6151d5;color:white;text-decoration:none;cursor:pointer;white-space:nowrap}.button:hover{background:#4d3fba}.button.secondary{background:white;color:#5142c1;border-color:#d9d5ee}.button.danger{background:#fff0f0;color:#a52626}.button:disabled{background:#f2f2f5;color:#91919d;cursor:not-allowed}.status,.errors{padding:12px 16px;border-radius:9px;margin-bottom:20px}.status{background:#edf8ef;color:#24683b}.errors{background:#fff0f0;color:#a52626}.errors ul{margin:0;padding-left:18px}.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;text-align:left}th{font-size:13px;color:#676774;font-weight:600;background:#fafafd}td,th{padding:14px;border-bottom:1px solid #eeeef3}tr:last-child td{border-bottom:0}.actions{display:flex;align-items:center;gap:8px}.actions form{margin:0}.badge{display:inline-block;padding:3px 9px;border-radius:20px;font-size:12px;background:#efeff4;color:#686878}.badge.base{background:#eeeafd;color:#5142c1}.badge.active{background:#edf8ef;color:#24683b}.form{width:100%;max-width:none}.field{margin-bottom:20px}.field label{display:block;font-weight:600;margin-bottom:6px}.field textarea,.field input:not([type=checkbox]){width:100%;padding:11px 12px;border:1px solid #cfcfd9;border-radius:9px}.field input[readonly]{background:#f5f5f7;color:#676774}.hint{font-size:13px;margin:6px 0;color:#676774}.check{display:flex!important;align-items:center;gap:10px}.check input{width:18px;height:18px;accent-color:#6151d5}.details{padding:16px;background:#f5f5f7;border-radius:9px}.pagination{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:20px;font-size:14px}.pagination a{padding:8px 12px;border:1px solid #d9d5ee;border-radius:8px;text-decoration:none}.pagination span{color:#676774}.notice{margin:0 0 20px;padding:12px 16px;background:#eeeafd;border-radius:9px;color:#5142c1;font-size:14px}@media(max-width:760px){.shell{flex-direction:column}.sidebar{width:100%;padding:18px;border-right:0;border-bottom:1px solid #e6e6eb;gap:16px}.nav{display:flex;flex-wrap:wrap}.account{display:flex;align-items:center;justify-content:space-between;gap:12px}.account p{display:none}.content{padding:24px 16px}.heading{align-items:flex-start;flex-wrap:wrap}.panel{padding:16px}.actions{flex-wrap:wrap}}
        .field textarea{resize:vertical}.field-error{color:#a52626;font-size:13px}.language-fields{border:1px solid #e6e6eb;border-radius:9px;margin:0 0 24px;padding:20px}.language-fields legend{padding:0 8px;font-weight:600}
        .form-field-row{display:grid;grid-template-columns:1fr;gap:16px}.form-block-title{font-size:17px}.language-label{font-size:13px}.form-field-column{min-width:0}.form-block .input-group{width:100%}@media(min-width:1000px){.form-field-row:has(.col-md-6){grid-template-columns:repeat(2,minmax(0,1fr))}}
        .content:has(>.admin-form){max-width:none}.admin-form{display:flex;flex-direction:column;gap:24px}.admin-form>.multilingual-block{margin:0}.form-settings>.field:last-child{margin-bottom:0}.form-checkboxes{display:flex;align-items:center;flex-wrap:wrap;gap:20px 32px}.form-checkboxes .field,.form-checkboxes .check{margin:0}
        .form-block-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px}.field .form-block-heading label{margin:0}.form-block-heading .translate-button{padding:5px 10px;font-size:12px;white-space:normal;text-align:center}.form-block-heading+.hint:empty{display:none}
        .form-field-column .note-editor{width:100%;min-width:0}.form-field-column .note-editable{min-height:180px}.field .note-editor button{font:inherit}.field .note-editor input:not([type=checkbox]){width:100%}
        .form-field-column .note-editor.fullscreen{background:#fff;z-index:1050}.form-field-column .note-editor.fullscreen .note-editing-area,.form-field-column .note-editor.fullscreen .note-editable{background:#fff}
        .note-modal .note-modal-footer{display:flex;justify-content:flex-end;align-items:center;flex-wrap:wrap;gap:8px;height:auto;min-height:60px;padding:12px 20px}.note-modal .note-modal-footer .note-btn{float:none;max-width:100%;white-space:normal}
        .settings-nav summary{display:flex;align-items:center;gap:12px;padding:12px;border-radius:10px;color:#555565;cursor:pointer;list-style:none}.settings-nav summary::-webkit-details-marker{display:none}.settings-nav summary:hover,.settings-nav[open] summary{background:#eeeafd;color:#5142c1}.settings-nav>a{margin:6px 0 0 34px}.settings-nav-arrow{margin-left:auto;transition:transform .15s}.settings-nav[open] .settings-nav-arrow{transform:rotate(180deg)}
    </style>
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <a class="brand" href="{{ route('index') }}">Vibe</a>
        <nav class="nav" aria-label="{{ __('Личный кабинет') }}">
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'selected' : '' }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1Z"/><path d="M9 21v-8h6v8"/></svg>
                {{ __('Обзор') }}
            </a>
            <a href="{{ route('languages.index') }}" class="{{ request()->routeIs('languages.*') ? 'selected' : '' }}" @if(request()->routeIs('languages.*')) aria-current="page" @endif>
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="9"/><ellipse cx="12" cy="12" rx="4" ry="9"/><path d="M3 12h18M5 6h14M5 18h14"/></svg>
                {{ __('Языки') }}
            </a>
            <a href="{{ route('pages.index') }}" class="{{ request()->routeIs('pages.*') ? 'selected' : '' }}" @if(request()->routeIs('pages.*')) aria-current="page" @endif>
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M14 3H5v18h14V8Z"/><path d="M14 3v5h5M8 12h8M8 16h8"/></svg>
                {{ __('Страницы') }}
            </a>
            <a href="{{ route('articles.index') }}" class="{{ request()->routeIs('articles.*') ? 'selected' : '' }}" @if(request()->routeIs('articles.*')) aria-current="page" @endif>
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M14 3H5v18h14V8Z"/><path d="M14 3v5h5M8 12h8M8 16h8"/></svg>
                {{ __('Блог') }}
            </a>
            <a href="{{ route('catalogs.index') }}" class="{{ request()->routeIs('catalogs.*') ? 'selected' : '' }}" @if(request()->routeIs('catalogs.*')) aria-current="page" @endif>
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 7h7l2 2h9v11H3Z"/><path d="M3 7V4h7l2 3"/></svg>
                {{ __('Каталоги') }}
            </a>
            <a href="{{ route('products.index') }}" class="{{ request()->routeIs('products.*') ? 'selected' : '' }}" @if(request()->routeIs('products.*')) aria-current="page" @endif>
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m3 7 9-4 9 4v10l-9 4-9-4ZM3 7l9 4 9-4M12 11v10"/></svg>
                {{ __('Товари') }}
            </a>
            <a href="{{ route('filters.index') }}" class="{{ request()->routeIs('filters.*') ? 'selected' : '' }}" @if(request()->routeIs('filters.*')) aria-current="page" @endif>
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 4h18l-7 8v7l-4 2v-9Z"/></svg>
                {{ __('Фільтри') }}
            </a>
            <a href="{{ route('slides.index') }}" class="{{ request()->routeIs('slides.*') ? 'selected' : '' }}" @if(request()->routeIs('slides.*')) aria-current="page" @endif>
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8" cy="9" r="1"/><path d="m3 17 5-5 4 4 4-6 5 7"/></svg>
                {{ __('Слайдер') }}
            </a>
            <a href="{{ route('advs.index') }}" class="{{ request()->routeIs('advs.*') ? 'selected' : '' }}" @if(request()->routeIs('advs.*')) aria-current="page" @endif>
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m12 3 8 4v5c0 5-8 9-8 9s-8-4-8-9V7Z"/><path d="m8 12 3 3 5-6"/></svg>
                {{ __('Преимущества') }}
            </a>
            <a href="{{ route('brands.index') }}" class="{{ request()->routeIs('brands.*') ? 'selected' : '' }}" @if(request()->routeIs('brands.*')) aria-current="page" @endif>
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m12 3 3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1Z"/></svg>
                {{ __('Бренды') }}
            </a>
            <a href="{{ route('orders.index') }}" class="{{ request()->routeIs('orders.*') ? 'selected' : '' }}" @if(request()->routeIs('orders.*')) aria-current="page" @endif>
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V2h6v2M9 9h6M9 13h6M9 17h4"/></svg>
                {{ __('Заказы') }}
            </a>
            <a href="{{ route('product-comments.index') }}" class="{{ request()->routeIs('product-comments.*') ? 'selected' : '' }}">{{ __('Отзывы о товарах') }}</a>
            <details class="settings-nav" @if(request()->routeIs('settings.*')) open @endif>
                <summary>
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/><circle cx="9" cy="6" r="2" fill="white"/><circle cx="15" cy="12" r="2" fill="white"/><circle cx="8" cy="18" r="2" fill="white"/></svg>
                    {{ __('Налаштування') }} <span class="settings-nav-arrow" aria-hidden="true">⌄</span>
                </summary>
                <a href="{{ route('settings.seo') }}" class="{{ request()->routeIs('settings.seo') ? 'selected' : '' }}" @if(request()->routeIs('settings.seo')) aria-current="page" @endif>SEO</a>
                <a href="{{ route('settings.design') }}" class="{{ request()->routeIs('settings.design') ? 'selected' : '' }}" @if(request()->routeIs('settings.design')) aria-current="page" @endif>{{ __('Дизайн') }}</a>
                <a href="{{ route('settings.contacts') }}" class="{{ request()->routeIs('settings.contacts') ? 'selected' : '' }}" @if(request()->routeIs('settings.contacts')) aria-current="page" @endif>{{ __('Контакти') }}</a>
            </details>
        </nav>
        <div class="account">
            <div><strong>{{ auth()->user()->name }}</strong><p>{{ auth()->user()->email }}</p></div>
            <form action="{{ route('logout') }}" method="POST">@csrf<button class="button secondary" type="submit">{{ __('Выйти') }}</button></form>
        </div>
    </aside>
    <main class="content">
        @if(session('status'))<div class="status" role="status">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="errors" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @yield('content')
    </main>
</div>
@include('admin.details.toasts')
@stack('scripts')
</body>
</html>
