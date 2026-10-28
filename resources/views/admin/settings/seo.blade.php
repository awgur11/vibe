@extends('layouts.dashboard')
@section('title', __('Налаштування SEO'))
@section('content')
    <div class="heading"><div><h1>{{ __('Налаштування SEO') }}</h1><p>{{ __('Загальні метадані та індексація сайту') }}</p></div></div>
    <div class="settings-grid">
        @include('admin.details.settings.input', ['key' => 'site_title', 'title' => __('Назва сайту'), 'languages' => true, 'max' => 255])
        @include('admin.details.settings.textarea', ['key' => 'site_description', 'title' => __('Опис сайту'), 'languages' => true, 'max' => 1000])
        @include('admin.details.settings.preview', ['key' => 'favicon', 'title' => __('Favicon'), 'hint' => __('Іконка сайту для вкладки браузера.')])
        <div>
            @include('admin.details.settings.checkbox', ['key' => 'robots_index', 'default' => true, 'title' => __('Дозволити індексацію сайту')])
            <div style="height: 24px"></div>
            @include('admin.details.settings.checkbox', ['key' => 'robots_follow', 'default' => true, 'title' => __('Дозволити пошуковим системам переходити за посиланнями')])
        </div>
    </div>
@endsection
