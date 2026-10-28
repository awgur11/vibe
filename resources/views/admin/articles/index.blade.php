@extends('layouts.dashboard')
@section('title', __('Блог'))
@section('content')
    <div class="heading">
        <div><h1>{{ __('Блог') }}</h1><p>{{ __('Управление статьями блога') }}</p></div>
        <a class="button" href="{{ route('articles.create') }}">+ {{ __('Добавить статью') }}</a>
    </div>
    <div class="panel">
        <div class="table-wrap"><table>
            <thead><tr><th>{{ __('Заголовок') }}</th><th>{{ __('Опубликована') }}</th><th>{{ __('Действия') }}</th></tr></thead>
            <tbody>
            @forelse($pages as $page)
                <tr><td>{{ $page->translated('title', $baseLanguage) ?? '—' }}</td>
                    <td><span class="badge {{ $page->public ? 'active' : '' }}">{{ $page->public ? __('Да') : __('Нет') }}</span></td>
                    <td><div class="actions">
                        <a class="button secondary" href="{{ route('articles.edit', $page) }}">{{ __('Редактировать') }}</a>
                        <form method="POST" action="{{ route('articles.destroy', $page) }}" onsubmit="return confirm({{ Illuminate\Support\Js::from(__('Удалить эту статью?')) }})">
                            @csrf @method('DELETE')<button class="button danger" type="submit">{{ __('Удалить') }}</button>
                        </form>
                    </div></td>
                </tr>
            @empty
                <tr><td colspan="3">{{ __('Статьи ещё не добавлены.') }}</td></tr>
            @endforelse
            </tbody>
        </table></div>
        @if($pages->hasPages())
            <nav class="pagination" aria-label="{{ __('Страницы списка статей') }}">
                @if($pages->onFirstPage())<span>{{ __('Назад') }}</span>@else<a href="{{ $pages->previousPageUrl() }}">{{ __('Назад') }}</a>@endif
                <span>{{ $pages->currentPage() }} / {{ $pages->lastPage() }}</span>
                @if($pages->hasMorePages())<a href="{{ $pages->nextPageUrl() }}">{{ __('Далее') }}</a>@else<span>{{ __('Далее') }}</span>@endif
            </nav>
        @endif
    </div>
@endsection
