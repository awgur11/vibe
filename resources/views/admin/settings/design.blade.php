@extends('layouts.dashboard')

@section('title', __('Налаштування дизайну'))

@section('content')
    <div class="heading"><div><h1>{{ __('Налаштування дизайну') }}</h1><p>{{ __('Логотип, палітра та оформлення сайту') }}</p></div></div>
    <div class="settings-grid">
        <h2 class="text-center">Шапка главной страницы</h2>
        @include('admin.details.settings.input', ['key' => 'header_small_title', 'title' => __('Заголовок меленьким шрифтом'), 'languages' => true])
        @include('admin.details.settings.input', ['key' => 'header_first_title', 'title' => __('Заголовок чёрный'), 'languages' => true])
        @include('admin.details.settings.input', ['key' => 'header_second_title', 'title' => __('Заголовок красный'), 'languages' => true])
        @include('admin.details.settings.input', ['key' => 'header_sub_title', 'title' => __('Подзаголовок'), 'languages' => true])
        @include('admin.details.settings.preview', ['key' => 'header_preview', 'title' => __('Картинка в шапке сайта')])

<h2 class="text-center">Блок "о нас" на главной странице</h2>

        @include('admin.details.settings.input', ['key' => 'about_us_title', 'title' => __('Заголовок'), 'languages' => true])

        @include('admin.details.settings.textarea', ['key' => 'about_us_text', 'title' => __('Текст'), 'languages' => true])

        @include('admin.details.settings.preview', ['key' => 'about_us_picture', 'title' => __('Картинка')])

<h2 class="text-center">Футер</h2>

        @include('admin.details.settings.preview', ['key' => 'footer_logo', 'title' => __('Логотип')])

        @include('admin.details.settings.input', ['key' => 'footer_note', 'title' => __('Текст'), 'languages' => true])

<h2 class="text-center">Страница контактов</h2>

        @include('admin.details.settings.textarea', ['key' => 'contact_page_short_text', 'title' => __('Текст'), 'languages' => true])

        @include('admin.details.settings.preview', ['key' => 'contact_page_preview', 'title' => __('Картинка')])
<h2 class="text-center">Страница блога</h2>

        @include('admin.details.settings.textarea', ['key' => 'blog_page_short_text', 'title' => __('Текст'), 'languages' => true])

        @include('admin.details.settings.preview', ['key' => 'blog_page_preview', 'title' => __('Картинка')])

        @include('admin.details.settings.preview', ['key' => 'site_logo', 'title' => __('Логотип сайту')])

        @include('admin.details.settings.input', ['key' => 'site_title', 'title' => __('Заголовок сайта'), 'languages' => true])

        @include('admin.details.settings.input', ['key' => 'site_subtitle', 'title' => __('Подзаголовок сайта'), 'languages' => true])

    </div>
@endsection
