@extends('layouts.dashboard')
@section('title', __('Слайдер'))
@section('content')
    <div class="heading">
        <div><h1>{{ __('Слайдер') }}</h1><p>{{ __('Керування слайдами') }}</p></div>
        <a class="button" href="{{ route('slides.create') }}">+ {{ __('Додати слайд') }}</a>
    </div>
    <div class="panel" data-reorder data-reorder-url="{{ route('reorder.update', ['resource' => 'slides']) }}" data-reorder-token="{{ csrf_token() }}">
        <p class="hint" data-reorder-status role="status" aria-live="polite"></p>
        <div class="table-wrap"><table>
            <thead><tr><th scope="col">{{ __('Порядок') }}</th><th>{{ __('Заголовок') }}</th><th>{{ __('Посилання') }}</th><th>{{ __('Опубліковано') }}</th><th>{{ __('Дії') }}</th></tr></thead>
            <tbody data-reorder-list>
                @forelse($slides as $slide)
                    <tr data-reorder-id="{{ $slide->id }}">
                        <td><button type="button" class="button secondary" data-reorder-handle title="{{ __('Перетягуйте рядки') }}" aria-label="{{ __('Перемістити слайд') }} {{ $slide->translated('title', $baseLanguage) }}" style="touch-action: none; cursor: grab">↕</button></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px">
                                @if($slide->preview)
                                    <img src="{{ $slide->previewUrl('mini') }}" width="48" height="48" alt="" loading="lazy" style="object-fit: cover; border-radius: 8px; flex-shrink: 0">
                                @else
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" style="color: #6151d5; flex-shrink: 0"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8" cy="9" r="1"/><path d="m3 17 5-5 4 4 4-6 5 7"/></svg>
                                @endif
                                <div><strong>{{ $slide->translated('title', $baseLanguage) ?? '—' }}</strong>@if($slide->translated('subtitle', $baseLanguage))<div class="hint">{{ Illuminate\Support\Str::limit($slide->translated('subtitle', $baseLanguage), 100) }}</div>@endif</div>
                            </div>
                        </td>
                        <td>@if($slide->link)<a href="{{ $slide->link }}">{{ Illuminate\Support\Str::limit($slide->link, 50) }}</a>@else—@endif</td>
                        <td><input type="checkbox" data-publication-toggle data-url="{{ route('slides.publication.update', $slide) }}"
                            data-token="{{ csrf_token() }}" @checked($slide->public)
                            aria-label="{{ __('Опублікувати слайд') }} {{ $slide->translated('title', $baseLanguage) }}"
                            style="width: 18px; height: 18px; accent-color: #6151d5"></td>
                        <td><div class="actions">
                            <a class="button secondary" href="{{ route('slides.edit', $slide) }}">{{ __('Редагувати') }}</a>
                            <form method="POST" action="{{ route('slides.destroy', $slide) }}" onsubmit="return confirm({{ Illuminate\Support\Js::from(__('Видалити цей слайд?')) }})">
                                @csrf @method('DELETE')<button class="button danger" type="submit">{{ __('Видалити') }}</button>
                            </form>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="5">{{ __('Слайди ще не додано.') }}</td></tr>
                @endforelse
            </tbody>
        </table></div>
        @if($slides->hasPages())
            <nav class="pagination" aria-label="{{ __('Сторінки списку слайдів') }}">
                @if($slides->onFirstPage())<span>{{ __('Назад') }}</span>@else<a href="{{ $slides->previousPageUrl() }}">{{ __('Назад') }}</a>@endif
                <span>{{ $slides->currentPage() }} / {{ $slides->lastPage() }}</span>
                @if($slides->hasMorePages())<a href="{{ $slides->nextPageUrl() }}">{{ __('Далі') }}</a>@else<span>{{ __('Далі') }}</span>@endif
            </nav>
        @endif
    </div>
    @include('admin.details.reorder')
    @push('scripts')
        <script src="{{ asset('js/admin-publication.js') }}" defer></script>
    @endpush
@endsection
