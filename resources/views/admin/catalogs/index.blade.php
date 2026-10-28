@extends('layouts.dashboard')
@section('title', __('Каталоги'))
@section('content')
    <div class="heading">
        <div><h1>{{ $parent ? ($parent->translated('title', $baseLanguage) ?? __('Каталог')) : __('Каталоги') }}</h1>
            @if($parent)<a href="{{ route('catalogs.index', ['parent_id' => $parent->parent_id]) }}">← {{ __('До батьківського розділу') }}</a>@else<p>{{ __('Керування каталогами') }}</p>@endif
        </div>
        <button class="button" type="button" data-catalog-create>+ {{ __('Додати каталог') }}</button>
    </div>
    <div class="panel">
        @include('admin.catalogs.branch')
        @if($catalogs->hasPages())
            <nav class="pagination" aria-label="{{ __('Сторінки каталогів') }}">
                @if($catalogs->onFirstPage())<span>{{ __('Назад') }}</span>@else<a href="{{ $catalogs->previousPageUrl() }}">{{ __('Назад') }}</a>@endif
                <span>{{ $catalogs->currentPage() }} / {{ $catalogs->lastPage() }}</span>
                @if($catalogs->hasMorePages())<a href="{{ $catalogs->nextPageUrl() }}">{{ __('Далі') }}</a>@else<span>{{ __('Далі') }}</span>@endif
            </nav>
        @endif
    </div>
    <dialog id="catalog-create-dialog" class="catalog-dialog" aria-labelledby="catalog-create-title">
        <form method="POST" action="{{ route('catalogs.store') }}">
            @csrf
            <input type="hidden" name="parent_id" value="{{ $parent?->id }}">
            <h2 id="catalog-create-title">{{ __('Новий каталог') }}</h2>
            <div class="field"><label for="catalog-title">{{ __('Назва каталогу') }}</label><input id="catalog-title" name="title" value="{{ old('title') }}" required maxlength="255" autofocus @error('title') aria-invalid="true" aria-describedby="catalog-title-error" @enderror>
                @error('title')<p class="field-error" id="catalog-title-error">{{ $message }}</p>@enderror
            </div>
            <div class="actions"><button class="button" type="submit">{{ __('Зберегти') }}</button><button class="button secondary" type="button" data-catalog-cancel>{{ __('Скасувати') }}</button></div>
        </form>
    </dialog>
    @include('admin.details.reorder')
    @push('scripts')
        <style>.catalog-children[hidden]{display:none}.catalog-children>td{padding:0 0 12px 24px}.catalog-children .catalog-branch{border-left:2px solid #d9d5ee;padding-left:12px}.catalog-branch>p:empty{display:none}.catalog-dialog{width:min(480px,calc(100vw - 32px));padding:24px;border:1px solid #e6e6eb;border-radius:16px;color:#20202a;box-shadow:0 20px 80px #20202a33}.catalog-dialog::backdrop{background:#20202a80}.catalog-dialog h2{margin-top:0}</style>
        <script src="{{ asset('js/admin-publication.js') }}" defer></script>
        <script>
            document.addEventListener('click', async (event) => {
                const button = event.target.closest('[data-catalog-toggle]');
                if (!button || button.disabled) return;
                const row = document.getElementById(button.getAttribute('aria-controls'));
                const content = row.querySelector('[data-catalog-children-content]');
                const expanded = button.getAttribute('aria-expanded') !== 'true';
                button.setAttribute('aria-expanded', String(expanded));
                row.hidden = !expanded;
                button.querySelector('[data-catalog-arrow]').textContent = expanded ? '▾' : '▸';
                if (!expanded || content.dataset.loaded) return;
                button.disabled = true;
                content.setAttribute('aria-busy', 'true');
                const status = document.createElement('p');
                status.className = 'hint';
                status.setAttribute('role', 'status');
                status.textContent = {{ Illuminate\Support\Js::from(__('Завантажуємо підкаталоги…')) }};
                content.replaceChildren(status);
                try {
                    const response = await fetch(button.dataset.childrenUrl, { headers: { Accept: 'text/html' } });
                    if (!response.ok || response.redirected || !response.headers.get('content-type')?.includes('text/html')) throw new Error('loading-failed');
                    content.innerHTML = await response.text();
                    content.dataset.loaded = 'true';
                    window.initializeAdminReorder?.(content);
                } catch (error) {
                    status.textContent = {{ Illuminate\Support\Js::from(__('Не вдалося завантажити підкаталоги.')) }};
                    const retry = document.createElement('button');
                    retry.type = 'button';
                    retry.className = 'button secondary';
                    retry.textContent = {{ Illuminate\Support\Js::from(__('Повторити завантаження')) }};
                    retry.addEventListener('click', () => {
                        button.setAttribute('aria-expanded', 'false');
                        button.click();
                    });
                    content.append(retry);
                } finally {
                    button.disabled = false;
                    content.removeAttribute('aria-busy');
                }
            });
            const catalogDialog = document.getElementById('catalog-create-dialog');
            document.querySelector('[data-catalog-create]').addEventListener('click', () => catalogDialog.showModal());
            document.querySelector('[data-catalog-cancel]').addEventListener('click', () => catalogDialog.close());
            @if($errors->has('title') || $errors->has('parent_id')) catalogDialog.showModal(); @endif
        </script>
    @endpush
@endsection
