@php
    $kind = $fieldType;
    $definition = [
        'type' => $kind,
        'languages' => $languages ?? false,
        'default' => $default ?? ($kind === 'checkbox' ? false : ($kind === 'preview' ? null : '')),
        'max' => $max ?? ($kind === 'input' ? 255 : 1000),
        'options' => $options ?? [],
        'input_type' => $input_type ?? 'text',
    ];
    if (isset($regex)) {
        $definition['regex'] = $regex;
    }
    $definitionToken = Illuminate\Support\Facades\Crypt::encryptString(json_encode([
        'key' => $key, 'user_id' => auth()->id(), 'definition' => $definition,
    ], JSON_THROW_ON_ERROR));
    $multilingual = $definition['languages'];
    $stored = $settingValues[$key] ?? $definition['default'];
    $fieldLanguages = $multilingual ? $site_languages : collect([null]);
@endphp
<section class="panel setting-block">
    <h2 class="setting-title">{{ $title }}</h2>
    @if(isset($hint))<p class="hint">{{ $hint }}</p>@endif
    @if($kind === 'preview')
        @foreach($fieldLanguages as $language)
            @php
                $imagePath = $multilingual ? (is_array($stored) ? ($stored[$language->locale] ?? null) : null) : $stored;
                $imageUrl = is_string($imagePath) && $imagePath !== '' ? Illuminate\Support\Facades\Storage::disk('public')->url($imagePath) : null;
                $fieldId = 'setting-'.$key.($language ? '-'.$language->id : '');
            @endphp
            <form method="POST" enctype="multipart/form-data" action="{{ route('settings.fields.save', $key) }}" data-setting-form data-setting-image data-preview-url="{{ $imageUrl }}">
                @csrf
                <input type="hidden" name="_definition" value="{{ $definitionToken }}">
                @if($language)<input type="hidden" name="locale" value="{{ $language->locale }}"><h3 class="setting-language">{{ $language->short }} — {{ $language->title }}</h3>@endif
                <div class="setting-image-layout">
                    <div class="setting-image-box">
                        <img data-setting-preview @if($imageUrl) src="{{ $imageUrl }}" @endif alt="{{ $title }}" @if(!$imageUrl) hidden @endif>
                        <span data-setting-placeholder @if($imageUrl) hidden @endif>{{ __('Зображення відсутнє') }}</span>
                    </div>
                    <div class="setting-image-controls">
                        <div class="field"><label for="{{ $fieldId }}-file">{{ __('Вибрати зображення') }}</label><input id="{{ $fieldId }}-file" type="file" name="preview" accept="image/jpeg,image/png,image/gif,image/webp" data-setting-autosave></div>
                        <p class="hint">{{ __('validation.upload_hint', ['size' => App\Services\UploadLimits::label()]) }}</p>
                        <div class="field"><label for="{{ $fieldId }}-url">{{ __('Або посилання на зображення') }}</label><input id="{{ $fieldId }}-url" type="url" name="preview_url" placeholder="https://example.com/image.png" maxlength="2048" data-setting-autosave></div>
                        <div class="actions">
                            <button class="button secondary" type="submit">{{ __('Зберегти') }}</button>
                            <button class="button danger" type="submit" name="remove_preview" value="1" formnovalidate data-setting-remove @if(!$imageUrl) hidden @endif>{{ __('Видалити зображення') }}</button>
                        </div>
                    </div>
                </div>
                <p class="field-error" data-setting-error role="alert"></p>
            </form>
        @endforeach
    @else
        <form method="POST" action="{{ route('settings.fields.save', $key) }}" data-setting-form class="{{ $multilingual && in_array($kind, ['input', 'textarea']) ? 'multilingual-block' : '' }}">
            @csrf
            <input type="hidden" name="_definition" value="{{ $definitionToken }}">
            @foreach($fieldLanguages as $language)
                @php
                    $fieldName = $multilingual ? 'value['.$language->locale.']' : 'value';
                    $fieldValue = $multilingual ? (is_array($stored) ? ($stored[$language->locale] ?? $definition['default']) : $definition['default']) : $stored;
                    $fieldValue = is_scalar($fieldValue) ? $fieldValue : '';
                    $fieldId = 'setting-'.$key.($language ? '-'.$language->id : '');
                    $fieldLabel = $language ? $language->short.' — '.$language->title : $title;
                @endphp
                <div class="field">
                    @if($kind === 'checkbox')
                        <input type="hidden" name="{{ $fieldName }}" value="0">
                        <label class="check" for="{{ $fieldId }}"><input id="{{ $fieldId }}" type="checkbox" name="{{ $fieldName }}" value="1" @checked(filter_var($fieldValue, FILTER_VALIDATE_BOOLEAN)) data-setting-autosave> {{ $fieldLabel }}</label>
                    @elseif($kind === 'radio')
                        <fieldset class="setting-options"><legend>{{ $fieldLabel }}</legend>
                            @foreach($definition['options'] as $optionValue => $optionLabel)
                                <label class="check"><input type="radio" name="{{ $fieldName }}" value="{{ $optionValue }}" @checked((string) $fieldValue === (string) $optionValue) data-setting-autosave> {{ __($optionLabel) }}</label>
                            @endforeach
                        </fieldset>
                    @else
                        <label for="{{ $fieldId }}">{{ $fieldLabel }} @if($language?->isBase())<span class="badge base">{{ __('Базова') }}</span>@endif</label>
                        @if($kind === 'textarea')
                            <textarea id="{{ $fieldId }}" name="{{ $fieldName }}" class="form-input-value" @if($language) data-locale="{{ $language->locale }}" @endif rows="{{ $rows ?? 3 }}" maxlength="{{ $definition['max'] ?? 1000 }}">{{ $fieldValue }}</textarea>
                        @else
                            <input id="{{ $fieldId }}" name="{{ $fieldName }}" class="form-input-value" @if($language) data-locale="{{ $language->locale }}" @endif type="{{ $input_type ?? 'text' }}" value="{{ $fieldValue }}" maxlength="{{ $definition['max'] ?? 1000 }}">
                        @endif
                    @endif
                </div>
            @endforeach
            <div class="actions setting-field-actions">
                <button class="button secondary" type="submit">{{ __('Зберегти') }}</button>
                @if($multilingual && in_array($kind, ['input', 'textarea']) && $fieldLanguages->count() > 1)
                    <button type="button" class="button secondary translate-button" data-translate-fields
                        data-url="{{ route('translations.store') }}"
                        data-empty-message="{{ __('Заповніть поле хоча б однією мовою.') }}"
                        data-error-message="{{ __('Не вдалося перекласти такими мовами:') }}"
                        data-success-message="{{ __('Переклад завершено. Натисніть «Зберегти», щоб зберегти зміни.') }}"
                        data-loading-message="{{ __('Перекладаємо…') }}">{{ __('Перекласти іншими мовами') }}</button>
                @endif
            </div>
            @if($multilingual && in_array($kind, ['input', 'textarea']) && $fieldLanguages->count() > 1)
                <p class="hint" data-translation-status role="status" aria-live="polite"></p>
            @endif
            <p class="field-error" data-setting-error role="alert"></p>
        </form>
    @endif
</section>
@include('admin.details.settings.assets')
