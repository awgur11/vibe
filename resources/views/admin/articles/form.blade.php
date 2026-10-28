@extends('layouts.dashboard')
@section('title', $page->exists ? __('Редактирование статьи') : __('Новая статья'))
@section('content')
    <div class="heading"><h1>{{ $page->exists ? __('Редактирование статьи') : __('Новая статья') }}</h1></div>
    <form class="form admin-form" method="POST" enctype="multipart/form-data" action="{{ $page->exists ? route('articles.update', $page) : route('articles.store') }}">
        @csrf
        <input type="hidden" name="type" value="articles">
        @if($page->exists)@method('PUT')@endif
        @include('admin.details.form.preview', ['name' => 'preview', 'title' => __('Зображення статті'), 'value' => $page])
        <div class="panel form-settings">
            @include('admin.details.form.input', ['name' => 'template', 'title' => __('Шаблон сторінки'), 'languages' => false, 'value' => $page, 'maxlength' => 255, 'note' => __('Наприклад: blog для resources/views/site/blog.blade.php. Залиште порожнім для стандартного шаблону.')])
            @foreach(['icon' => __('Иконка')] as $field => $label)
                @include('admin.details.form.input', ['name' => $field, 'title' => $label, 'languages' => false, 'value' => $page, 'maxlength' => 255])
            @endforeach

            @include('admin.details.form.input', ['name' => 'position', 'languages' => false, 'title' => __('Позиция'), 'value' => $page, 'default' => 0, 'input_type' => 'number', 'min' => 0, 'required' => true])

        </div>

        @include('admin.details.form.input', ['name' => 'menu', 'title' => __('Название в меню'), 'value' => $page])

        @include('admin.details.form.input', ['name' => 'title', 'title' => __('Заголовок'), 'value' => $page, 'required' => true])

        @include('admin.details.form.textarea', ['name' => 'short_content', 'title' => __('Краткое содержание'), 'value' => $page, 'rows' => 3])

        @include('admin.details.form.textarea', ['name' => 'content', 'title' => __('Содержание'), 'value' => $page, 'rows' => 8, 'summernote' => true])

        @include('admin.details.form.input', ['name' => 'tag_title', 'title' => __('Заголовок для пошукових систем'), 'value' => $page])

        @include('admin.details.form.textarea', ['name' => 'tag_description', 'title' => __('Опис для пошукових систем'), 'value' => $page, 'rows' => 3])
        <div class="panel form-checkboxes">
            <div class="field">
                <input type="hidden" name="public" value="0">
                <label class="check" for="public"><input id="public" type="checkbox" name="public" value="1" @checked(old('public', $page->public))> {{ __('Опубликована') }}</label>
            </div>
            <div class="field">
                <input type="hidden" name="is_menu" value="0">
                <label class="check" for="is_menu"><input id="is_menu" type="checkbox" name="is_menu" value="1" @checked(old('is_menu', $page->is_menu))> {{ __('Показывать в меню') }}</label>
            </div>
        </div>
        <div class="actions"><button class="button" type="submit">{{ $page->exists ? __('Сохранить') : __('Создать статью') }}</button><a class="button secondary" href="{{ route('articles.index') }}">{{ __('Отмена') }}</a></div>
    </form>
@endsection
