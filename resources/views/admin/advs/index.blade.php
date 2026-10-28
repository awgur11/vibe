@extends('layouts.dashboard')
@section('title', __('Преимущества'))
@section('content')
    <div class="heading"><h1>{{ __('Преимущества') }}</h1><button class="button" type="button" data-adv-create>{{ __('Добавить преимущество') }}</button></div>
    <div class="panel" data-reorder data-reorder-url="{{ route('reorder.update', ['resource' => 'advs']) }}" data-reorder-token="{{ csrf_token() }}">
        <p class="hint" data-reorder-status role="status" aria-live="polite"></p>
        <div data-reorder-list>
            @forelse($advs as $adv)
                <div class="adv-row" data-reorder-id="{{ $adv->id }}">
                    <button class="button secondary" type="button" data-reorder-handle aria-label="{{ __('Перетягуйте рядки') }}" style="touch-action:none;cursor:grab">↕</button>
                    <div class="adv-row-content"><strong>{{ $adv->translated('title', $baseLanguage) }}</strong><p>{{ $adv->translated('description', $baseLanguage) }}</p><small>{{ __(App\Models\Adv::ICONS[$adv->icon] ?? $adv->icon) }} · {{ $adv->position }}</small></div>
                    <span class="badge">{{ $adv->public ? __('Опубліковано') : __('Приховано') }}</span>
                    <div class="actions">
                        <button class="button secondary" type="button" data-adv-edit="{{ $adv->id }}">{{ __('Редагувати') }}</button>
                        <form method="POST" action="{{ route('advs.destroy', $adv) }}" onsubmit="return confirm({{ Illuminate\Support\Js::from(__('Удалить это преимущество?')) }})">
                            @csrf @method('DELETE')
                            <button class="button danger" type="submit">{{ __('Видалити') }}</button>
                        </form>
                    </div>
                </div>
            @empty
                <p>{{ __('Преимущества ещё не добавлены.') }}</p>
            @endforelse
        </div>
    </div>
    <dialog id="adv-dialog" aria-labelledby="adv-dialog-title">
        <form method="POST" id="adv-form" action="{{ old('_adv_id') ? route('advs.update', ['adv' => (int) old('_adv_id')]) : route('advs.store') }}">
            @csrf
            <input type="hidden" name="_method" value="{{ old('_adv_id') ? 'PUT' : 'POST' }}">
            <input type="hidden" name="_adv_id" value="{{ old('_adv_id') }}">
            <h2 id="adv-dialog-title">{{ old('_adv_id') ? __('Редактирование преимущества') : __('Новое преимущество') }}</h2>
            <div class="field">
                <label for="adv-icon">{{ __('Иконка') }}</label>
                <select name="icon" id="adv-icon" required>
                    @foreach(App\Models\Adv::ICONS as $icon => $label)
                        <option value="{{ $icon }}" @selected(old('icon', 'i-truck') === $icon)>{{ __($label) }} ({{ $icon }})</option>
                    @endforeach
                </select>
                @error('icon')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>
            @include('admin.details.form.input', ['name' => 'title', 'title' => __('Заголовок'), 'required' => true])
            @include('admin.details.form.textarea', ['name' => 'description', 'title' => __('Описание'), 'rows' => 3, 'maxlength' => 2000])
            @include('admin.details.form.input', ['name' => 'position', 'title' => __('Позиція'), 'languages' => false, 'type' => 'number', 'min' => 0, 'data' => ['max' => 4294967295]])
            <div class="field">
                <input type="hidden" name="public" value="0">
                <label class="check" for="adv-public"><input id="adv-public" name="public" type="checkbox" value="1" @checked(old('public', true))> {{ __('Опублікувати') }}</label>
                @error('public')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="actions"><button class="button" type="submit">{{ __('Зберегти') }}</button><button class="button secondary" type="button" data-adv-cancel>{{ __('Скасувати') }}</button></div>
        </form>
    </dialog>
    @include('admin.details.reorder')
    @push('scripts')
        <style>
            .adv-row{display:flex;align-items:center;gap:16px;padding:16px 0;border-bottom:1px solid #e6e6eb}.adv-row-content{flex:1;min-width:0;overflow-wrap:anywhere}.adv-row-content p{margin:6px 0;color:#676774}.adv-row-content small{color:#676774}#adv-dialog{width:min(800px,calc(100vw - 32px));max-height:calc(100vh - 48px);overflow:auto;padding:24px;border:1px solid #e6e6eb;border-radius:16px;color:#20202a;box-shadow:0 20px 80px #20202a33}#adv-dialog::backdrop{background:#20202a80}#adv-dialog h2{margin-top:0}@media(max-width:680px){.adv-row{flex-wrap:wrap}.adv-row-content{flex-basis:60%}}
        </style>
        <script>
            (() => {
                const dialog = document.getElementById('adv-dialog');
                const form = document.getElementById('adv-form');
                const records = {{ Illuminate\Support\Js::from($advs->mapWithKeys(fn ($adv) => [$adv->id => ['url' => route('advs.update', $adv), 'icon' => $adv->icon, 'position' => $adv->position, 'public' => $adv->public, 'translations' => $adv->translations->keyBy('lacale')->map(fn ($translation) => ['title' => $translation->title, 'description' => $translation->description])]])) }};
                function openAdvDialog(id = null) {
                    const record = id ? records[id] : null;
                    form.action = record ? record.url : {{ Illuminate\Support\Js::from(route('advs.store')) }};
                    form.elements._method.value = record ? 'PUT' : 'POST';
                    form.elements._adv_id.value = id ?? '';
                    form.elements.icon.value = record?.icon ?? 'i-truck';
                    form.elements.position.value = record?.position ?? '';
                    document.getElementById('adv-public').checked = record?.public ?? true;
                    form.querySelectorAll('[data-name="title"] [data-locale], [data-name="description"] [data-locale]').forEach(input => {
                        const field = input.closest('[data-name]').dataset.name;
                        input.value = record?.translations[input.dataset.locale]?.[field] ?? '';
                    });
                    form.querySelectorAll('[aria-invalid]').forEach(input => input.removeAttribute('aria-invalid'));
                    form.querySelectorAll('.field-error').forEach(error => error.hidden = true);
                    form.querySelectorAll('[data-translation-status]').forEach(status => status.textContent = '');
                    document.getElementById('adv-dialog-title').textContent = record ? {{ Illuminate\Support\Js::from(__('Редактирование преимущества')) }} : {{ Illuminate\Support\Js::from(__('Новое преимущество')) }};
                    dialog.showModal();
                }
                document.querySelector('[data-adv-create]').addEventListener('click', () => openAdvDialog());
                document.querySelectorAll('[data-adv-edit]').forEach(button => button.addEventListener('click', () => openAdvDialog(button.dataset.advEdit)));
                document.querySelector('[data-adv-cancel]').addEventListener('click', () => dialog.close());
                document.querySelector('[data-reorder]').addEventListener('reorder:saved', event => {
                    event.detail.ids.forEach((id, index) => records[id].position = index);
                });
                @if($errors->any()) dialog.showModal(); @endif
            })();
        </script>
    @endpush
@endsection
