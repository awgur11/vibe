@extends('layouts.dashboard')

@section('title', __('Контактні дані'))

@section('content')
    <div class="heading"><div><h1>{{ __('Контактні дані') }}</h1><p>{{ __('Телефони, адреса та час роботи магазину') }}</p></div></div>
    <div class="settings-grid">
        @include('admin.details.settings.phones', ['key' => 'phones', 'title' => __('Телефони')])
        @include('admin.details.settings.input', ['key' => 'address', 'title' => __('Адреса магазину'), 'languages' => true, 'max' => 500])
        @include('admin.details.settings.input', ['key' => 'site_email', 'title' => __('Електронна пошта на сайті'), 'input_type' => 'email'])
        @include('admin.details.settings.input', ['key' => 'admin_email', 'title' => __('Пошта для сповіщень із сайту'), 'input_type' => 'email'])
        
        @include('admin.details.settings.textarea', ['key' => 'google_maps_iframe', 'title' => __('Карта Google'), 'max' => 5000, 'rows' => 5, 'hint' => __('Вставте HTML-код iframe з меню «Поділитися» → «Вбудувати карту» у Google Maps.')])
        @include('admin.details.settings.input', ['key' => 'store_coordinates', 'title' => __('Координати магазину'), 'max' => 60, 'regex' => '/^\s*-?(?:[0-8]?\d(?:\.\d+)?|90(?:\.0+)?),\s*-?(?:1[0-7]\d(?:\.\d+)?|[0-9]?\d(?:\.\d+)?|180(?:\.0+)?)\s*$/D', 'hint' => __('Широта, довгота для кнопки «Прокласти маршрут». Наприклад: 50.4501, 30.5234.')])
        @include('admin.details.settings.textarea', ['key' => 'working_hours', 'title' => __('Час роботи'), 'languages' => true, 'rows' => 5])
        @include('admin.details.settings.input', ['key' => 'facebook_url', 'title' => __('Посилання на Facebook'), 'input_type' => 'url', 'max' => 2048])
    </div>
@endsection

