@extends('layouts.dashboard')
@section('title', __('Языки'))
@section('content')
    <div class="heading">
        <div><h1>{{ __('Языки') }}</h1><p>{{ __('Управление языками сайта') }}</p></div>
        <a class="button" href="{{ route('languages.create') }}"><span aria-hidden="true">+</span> {{ __('Добавить язык') }}</a>
    </div>
    <div class="notice">{{ __('Базовый язык:') }} <strong>{{ config('app.locale') }}</strong>. {{ __('Он задаётся в конфигурации сайта и защищён от удаления.') }}</div>
    <div class="panel">
        <div class="table-wrap">
            <table>
                <thead><tr><th scope="col">{{ __('ID') }}</th><th scope="col">{{ __('Locale') }}</th><th scope="col">{{ __('Краткое название') }}</th><th scope="col">{{ __('Название') }}</th><th scope="col">{{ __('Активен') }}</th><th scope="col">{{ __('Действия') }}</th></tr></thead>
                <tbody>
                @forelse($languages as $language)
                    <tr>
                        <td>{{ $language->id }}</td>
                        <td>{{ $language->locale }} @if($language->isBase())<span class="badge base">{{ __('Базовый') }}</span>@endif</td>
                        <td>{{ $language->short }}</td><td>{{ $language->title }}</td>
                        <td><span class="badge {{ $language->active ? 'active' : '' }}">{{ $language->active ? __('Да') : __('Нет') }}</span></td>
                        <td><div class="actions">
                            <a class="button secondary" href="{{ route('languages.edit', $language) }}">{{ __('Редактировать') }}</a>
                            @if($language->isBase())
                                <button class="button" type="button" disabled title="{{ __('Базовый язык нельзя удалить') }}">{{ __('Удалить') }}</button>
                            @else
                                <form method="POST" action="{{ route('languages.destroy', $language) }}" onsubmit="return confirm({{ Illuminate\Support\Js::from(__('Удалить этот язык?')) }})">
                                    @csrf @method('DELETE')
                                    <button class="button danger" type="submit" aria-label="{{ __('Удалить язык :title', ['title' => $language->title]) }}">{{ __('Удалить') }}</button>
                                </form>
                            @endif
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="6">{{ __('Языки ещё не добавлены.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($languages->hasPages())
            <nav class="pagination" aria-label="{{ __('Страницы списка языков') }}">
                @if($languages->onFirstPage())<span>{{ __('Назад') }}</span>@else<a href="{{ $languages->previousPageUrl() }}">{{ __('Назад') }}</a>@endif
                <span>{{ $languages->currentPage() }} / {{ $languages->lastPage() }}</span>
                @if($languages->hasMorePages())<a href="{{ $languages->nextPageUrl() }}">{{ __('Далее') }}</a>@else<span>{{ __('Далее') }}</span>@endif
            </nav>
        @endif
    </div>
@endsection
