@extends('layouts.dashboard')
@section('title', __('Страницы'))
@section('content')
    <div class="heading">
        <div><h1>{{ __('Страницы') }}</h1><p>{{ __('Управление страницами сайта') }}</p></div>
        <a class="button" href="{{ route('pages.create') }}">+ {{ __('Добавить страницу') }}</a>
    </div>
    <div class="panel" data-reorder data-reorder-url="{{ route('reorder.update', ['resource' => 'pages']) }}" data-reorder-token="{{ csrf_token() }}">
        <p class="hint" data-reorder-status role="status" aria-live="polite"></p>
        <div class="table-wrap"><table>
            <thead><tr><th scope="col">{{ __('Порядок') }}</th><th>{{ __('Заголовок') }}</th><th>{{ __('Опубликована') }}</th><th>{{ __('Действия') }}</th></tr></thead>
            <tbody data-reorder-list>
            @forelse($pages as $page)
                <tr data-reorder-id="{{ $page->id }}">
                    <td>
                        <button type="button" class="button secondary" data-reorder-handle title="{{ __('Перетягуйте рядки') }}" aria-label="{{ __('Перемістити сторінку') }} {{ $page->translated('title', $baseLanguage) }}" style="touch-action: none; cursor: grab">↕</button>
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 12px">
                            @if($page->preview)
                                <img src="{{ $page->previewUrl('mini') }}" alt="" width="48" height="48" loading="lazy" style="object-fit: cover; border-radius: 8px; flex-shrink: 0">
                            @else
                                <span style="display: inline-flex; align-items: center; justify-content: center; width: 48px; height: 48px; border-radius: 8px; background: #eeeafd; color: #6151d5; flex-shrink: 0" aria-hidden="true">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M14 3H5v18h14V8Z"/><path d="M14 3v5h5M8 12h8M8 16h8"/></svg>
                                </span>
                            @endif
                            <span>{{ $page->translated('title', $baseLanguage) ?? '—' }}</span>
                        </div>
                    </td>
                    <td>
                        <input type="checkbox" data-publication-toggle data-url="{{ route('pages.publication.update', $page) }}"
                            data-token="{{ csrf_token() }}" @checked($page->public)
                            aria-label="{{ __('Опублікувати сторінку') }} {{ $page->translated('title', $baseLanguage) }}"
                            style="width: 18px; height: 18px; accent-color: #6151d5">
                    </td>
                    <td><div class="actions">
                        <a class="button secondary" href="{{ route('pages.edit', $page) }}">{{ __('Редактировать') }}</a>
                        <form method="POST" action="{{ route('pages.destroy', $page) }}" onsubmit="return confirm({{ Illuminate\Support\Js::from(__('Удалить эту страницу?')) }})">
                            @csrf @method('DELETE')<button class="button danger" type="submit">{{ __('Удалить') }}</button>
                        </form>
                    </div></td>
                </tr>
            @empty
                <tr><td colspan="4">{{ __('Страницы ещё не добавлены.') }}</td></tr>
            @endforelse
            </tbody>
        </table></div>
        @if($pages->hasPages())
            <nav class="pagination" aria-label="{{ __('Страницы списка страниц') }}">
                @if($pages->onFirstPage())<span>{{ __('Назад') }}</span>@else<a href="{{ $pages->previousPageUrl() }}">{{ __('Назад') }}</a>@endif
                <span>{{ $pages->currentPage() }} / {{ $pages->lastPage() }}</span>
                @if($pages->hasMorePages())<a href="{{ $pages->nextPageUrl() }}">{{ __('Далее') }}</a>@else<span>{{ __('Далее') }}</span>@endif
            </nav>
        @endif
    </div>
    @include('admin.details.reorder')
    @push('scripts')
        <script src="{{ asset('js/admin-publication.js') }}" defer></script>
    @endpush
@endsection
