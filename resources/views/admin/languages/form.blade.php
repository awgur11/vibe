@extends('layouts.dashboard')
@section('title', $language->exists ? __('Редактирование языка') : __('Новый язык'))
@section('content')
    <div class="heading"><div><h1>{{ $language->exists ? __('Редактирование языка') : __('Новый язык') }}</h1><p>{{ __('Locale, названия и доступность языка') }}</p></div></div>
    <form class="form admin-form" method="POST" action="{{ $language->exists ? route('languages.update', $language) : route('languages.store') }}">
        @csrf
        <div class="panel form-settings">
            @if($language->exists)@method('PUT')<div class="field"><label>{{ __('ID') }}</label><div>{{ $language->id }}</div></div>@endif
            @include('admin.details.form.input', ['name' => 'locale', 'languages' => false, 'title' => __('Locale'), 'value' => $language, 'required' => true, 'maxlength' => 35, 'readonly' => $language->isBase(), 'autofocus' => !$language->isBase(), 'note' => $language->isBase() ? __('Базовый locale задаётся в config/app.php (APP_LOCALE). Изменить его здесь нельзя.') : __('Например: en, uk, ru или en_US. Значение должно быть уникальным.')])
            @include('admin.details.form.input', ['name' => 'short', 'languages' => false, 'title' => __('Краткое название (short)'), 'value' => $language, 'required' => true, 'minlength' => 3, 'maxlength' => 3, 'pattern' => '\p{L}{3}', 'placeholder' => 'UKR'])
            @include('admin.details.form.input', ['name' => 'title', 'languages' => false, 'title' => __('Название (title)'), 'value' => $language, 'required' => true, 'maxlength' => 255, 'placeholder' => __('Українська')])
        </div>
        <div class="panel form-checkboxes">
            <div class="field">
                <input type="hidden" name="active" value="0">
                <label class="check" for="active"><input id="active" type="checkbox" name="active" value="1" @checked(old('active', $language->active))> {{ __('Активен') }}</label>
            </div>
        </div>
        <div class="actions"><button class="button" type="submit">{{ $language->exists ? __('Сохранить') : __('Создать язык') }}</button><a class="button secondary" href="{{ route('languages.index') }}">{{ __('Отмена') }}</a></div>
    </form>
@endsection
