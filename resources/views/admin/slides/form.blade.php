@extends('layouts.dashboard')
@section('title', $slide->exists ? __('Редагування слайда') : __('Новий слайд'))
@section('content')
    <div class="heading"><h1>{{ $slide->exists ? __('Редагування слайда') : __('Новий слайд') }}</h1></div>
    <form class="form admin-form" method="POST" enctype="multipart/form-data" action="{{ $slide->exists ? route('slides.update', $slide) : route('slides.store') }}">
        @csrf
        @if($slide->exists)@method('PUT')@endif
        @include('admin.details.form.preview', ['name' => 'preview', 'title' => __('Зображення слайда'), 'value' => $slide])
        <div class="panel form-settings">
            @include('admin.details.form.input', ['name' => 'title', 'title' => __('Заголовок'), 'value' => $slide, 'required' => true])
            @include('admin.details.form.textarea', ['name' => 'subtitle', 'title' => __('Підзаголовок'), 'value' => $slide, 'rows' => 3, 'maxlength' => 1000])
            @include('admin.details.form.input', ['name' => 'link', 'title' => __('Посилання на сторінку'), 'languages' => false, 'value' => $slide, 'input_type' => 'url', 'maxlength' => 2048, 'placeholder' => url('/'), 'note' => __('Повна адреса сторінки з http:// або https://.')])
        </div>
        <div class="panel form-checkboxes">
            <div class="field">
                <input type="hidden" name="public" value="0">
                <label class="check" for="public"><input id="public" type="checkbox" name="public" value="1" @checked(old('public', $slide->public))> {{ __('Опублікувати') }}</label>
            </div>
        </div>
        <div class="actions">
            <button class="button" type="submit">{{ $slide->exists ? __('Зберегти') : __('Створити слайд') }}</button>
            <a class="button secondary" href="{{ route('slides.index') }}">{{ __('Скасувати') }}</a>
        </div>
    </form>
@endsection
