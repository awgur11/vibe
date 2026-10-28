@extends('layouts.dashboard')
@section('title', __('Бренды'))
@section('content')
    <div class="heading"><h1>{{ __('Бренды') }}</h1><button class="button" type="button" data-brand-create>{{ __('Добавить бренд') }}</button></div>
    <div class="panel" data-reorder data-reorder-url="{{ route('reorder.update', ['resource' => 'brands']) }}" data-reorder-token="{{ csrf_token() }}">
        <p class="hint" data-reorder-status role="status" aria-live="polite"></p>
        <div data-reorder-list>
            @forelse($brands as $brand)
                <div class="brand-row" data-reorder-id="{{ $brand->id }}">
                    <button class="button secondary" type="button" data-reorder-handle aria-label="{{ __('Перетягуйте рядки') }}" style="touch-action:none;cursor:grab">↕</button>
                    <div class="brand-row-content"><strong>{{ $brand->name }}</strong><p>{{ $brand->translated('title', $baseLanguage) }}</p></div>
                    <span class="badge">{{ $brand->public ? __('Опубліковано') : __('Приховано') }}</span>
                    <div class="actions">
                        <button class="button secondary" type="button" data-brand-edit="{{ $brand->id }}">{{ __('Редагувати') }}</button>
                        <form method="POST" action="{{ route('brands.destroy', $brand) }}" onsubmit="return confirm({{ Illuminate\Support\Js::from(__('Удалить этот бренд?')) }})">
                            @csrf @method('DELETE')
                            <button class="button danger" type="submit">{{ __('Видалити') }}</button>
                        </form>
                    </div>
                </div>
            @empty
                <p>{{ __('Бренды ещё не добавлены.') }}</p>
            @endforelse
        </div>
    </div>
    <dialog id="brand-dialog" aria-labelledby="brand-dialog-title">
        <form method="POST" id="brand-form" action="{{ old('_brand_id') ? route('brands.update', ['brand' => (int) old('_brand_id')]) : route('brands.store') }}">
            @csrf
            <input type="hidden" name="_method" value="{{ old('_brand_id') ? 'PUT' : 'POST' }}">
            <input type="hidden" name="_brand_id" value="{{ old('_brand_id') }}">
            <h2 id="brand-dialog-title">{{ old('_brand_id') ? __('Редактирование бренда') : __('Новый бренд') }}</h2>
            @include('admin.details.form.textarea', ['name' => 'name', 'title' => __('Название бренда'), 'languages' => false, 'required' => true, 'rows' => 2, 'maxlength' => 255])
            @include('admin.details.form.input', ['name' => 'title', 'title' => __('Подсказка при наведении'), 'required' => true])
            @include('admin.details.form.input', ['name' => 'position', 'title' => __('Позиція'), 'languages' => false, 'type' => 'number', 'min' => 0, 'data' => ['max' => 4294967295]])
            <div class="field">
                <input type="hidden" name="public" value="0">
                <label class="check" for="brand-public"><input id="brand-public" name="public" type="checkbox" value="1" @checked(old('public', true))> {{ __('Опублікувати') }}</label>
                @error('public')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="actions"><button class="button" type="submit">{{ __('Зберегти') }}</button><button class="button secondary" type="button" data-brand-cancel>{{ __('Скасувати') }}</button></div>
        </form>
    </dialog>
    @include('admin.details.reorder')
    @push('scripts')
        <style>
            .brand-row{display:flex;align-items:center;gap:16px;padding:16px 0;border-bottom:1px solid #e6e6eb}.brand-row-content{flex:1;min-width:0;overflow-wrap:anywhere}.brand-row-content p{margin:6px 0;color:#676774}.brand-row-content small{color:#676774}#brand-dialog{width:min(800px,calc(100vw - 32px));max-height:calc(100vh - 48px);overflow:auto;padding:24px;border:1px solid #e6e6eb;border-radius:16px;color:#20202a;box-shadow:0 20px 80px #20202a33}#brand-dialog::backdrop{background:#20202a80}#brand-dialog h2{margin-top:0}@media(max-width:680px){.brand-row{flex-wrap:wrap}.brand-row-content{flex-basis:60%}}
        </style>
        <script>
            (() => {
                const dialog = document.getElementById('brand-dialog');
                const form = document.getElementById('brand-form');
                const records = {{ Illuminate\Support\Js::from($brands->mapWithKeys(fn ($brand) => [$brand->id => ['url' => route('brands.update', $brand), 'name' => $brand->name, 'position' => $brand->position, 'public' => $brand->public, 'translations' => $brand->translations->keyBy('lacale')->map(fn ($translation) => ['title' => $translation->title])]])) }};
                function openBrandDialog(id = null) {
                    const record = id ? records[id] : null;
                    form.action = record ? record.url : {{ Illuminate\Support\Js::from(route('brands.store')) }};
                    form.elements._method.value = record ? 'PUT' : 'POST';
                    form.elements._brand_id.value = id ?? '';
                    form.elements.name.value = record?.name ?? '';
                    form.elements.position.value = record?.position ?? '';
                    document.getElementById('brand-public').checked = record?.public ?? true;
                    form.querySelectorAll('[data-name="title"] [data-locale]').forEach(input => {
                        const field = input.closest('[data-name]').dataset.name;
                        input.value = record?.translations[input.dataset.locale]?.[field] ?? '';
                    });
                    form.querySelectorAll('[aria-invalid]').forEach(input => input.removeAttribute('aria-invalid'));
                    form.querySelectorAll('.field-error').forEach(error => error.hidden = true);
                    form.querySelectorAll('[data-translation-status]').forEach(status => status.textContent = '');
                    document.getElementById('brand-dialog-title').textContent = record ? {{ Illuminate\Support\Js::from(__('Редактирование бренда')) }} : {{ Illuminate\Support\Js::from(__('Новый бренд')) }};
                    dialog.showModal();
                }
                document.querySelector('[data-brand-create]').addEventListener('click', () => openBrandDialog());
                document.querySelectorAll('[data-brand-edit]').forEach(button => button.addEventListener('click', () => openBrandDialog(button.dataset.brandEdit)));
                document.querySelector('[data-brand-cancel]').addEventListener('click', () => dialog.close());
                document.querySelector('[data-reorder]').addEventListener('reorder:saved', event => {
                    event.detail.ids.forEach((id, index) => records[id].position = index);
                });
                @if($errors->any()) dialog.showModal(); @endif
            })();
        </script>
    @endpush
@endsection
