@php
    $fieldType = $input_type ?? $type ?? 'text';
    $fieldSummernote = $fieldType === 'textarea' && (bool) ($summernote ?? false);
    $isMultilingual = $languages ?? true;
    $fieldTitle = $title ?? $label ?? $name;
    $fieldRequired = (bool) ($required ?? false);
    $fieldMaxlength = $maxlength ?? ($fieldType === 'textarea' ? null : 255);
    $fieldNote = $note ?? $hint ?? null;
    $fieldDefault = $default ?? '';
    $fieldModel = $value ?? null;
    $fieldAttributes = new Illuminate\View\ComponentAttributeBag($data ?? []);
    $fieldLanguages = $isMultilingual ? $site_languages : collect([null]);
    $fieldBaseId = $isMultilingual ? $site_languages->first(fn ($language) => $language->isBase())?->id : null;
    $fieldLabelId = $isMultilingual ? 'languages-'.$name.'-'.$fieldBaseId : ($id ?? $name);
@endphp
<div class="form-block field {{ $isMultilingual ? 'panel multilingual-block' : '' }}" data-name="{{ $name }}">
    <div class="form-block-heading">
        <label class="form-block-title" for="{{ $fieldLabelId }}">{{ $fieldTitle }}:@if($fieldRequired)<span aria-hidden="true"> *</span>@endif</label>
        @if($isMultilingual && $fieldLanguages->count() > 1 && !($readonly ?? false))
        <button type="button" class="button secondary translate-button" data-translate-fields
            data-url="{{ route('translations.store') }}"
            data-empty-message="{{ __('Заполните поле хотя бы на одном языке.') }}"
            data-error-message="{{ __('Не удалось перевести на языки:') }}"
            data-success-message="{{ __('Перевод завершён.') }}"
            data-loading-message="{{ __('Перекладаємо…') }}">{{ __('Перекласти іншими мовами') }}</button>
        @endif
    </div>
    @if($isMultilingual && $fieldLanguages->count() > 1 && !($readonly ?? false))
        <p class="hint" data-translation-status role="status" aria-live="polite"></p>
    @endif
    <div class="row form-field-row">
        @foreach($fieldLanguages as $fieldLanguage)
            @php
                $fieldName = $isMultilingual ? 'languages['.$fieldLanguage->id.']['.$name.']' : $name;
                $fieldError = $isMultilingual ? 'languages.'.$fieldLanguage->id.'.'.$name : ($error ?? $name);
                $fieldId = $isMultilingual ? 'languages-'.$name.'-'.$fieldLanguage->id : ($id ?? $name);
                $storedValue = $isMultilingual
                    ? ($fieldModel?->translations->firstWhere('lacale', $fieldLanguage->locale)?->getAttribute($name) ?? '')
                    : ($fieldModel instanceof Illuminate\Database\Eloquent\Model ? $fieldModel->getAttribute($name) : ($fieldModel ?? ''));
                $storedValue = $storedValue === '' || $storedValue === null ? $fieldDefault : $storedValue;
                $fieldValue = ($readonly ?? false) ? $storedValue : old($fieldError, $storedValue);
                $fieldDescription = trim((isset($fieldNote) ? $fieldLabelId.'-note' : '').' '.($errors->has($fieldError) ? $fieldId.'-error' : ''));
                $isRequired = $fieldRequired && (! $isMultilingual || $fieldLanguage->isBase());
            @endphp
            <div class="col-12 {{ $isMultilingual && $site_languages->count() > 1 && $fieldType !== 'textarea' ? 'col-md-6' : '' }} form-field-column">
                @if($isMultilingual)
                    <label class="language-label" for="{{ $fieldId }}">
                        {{ $fieldLanguage->short }}: {{ $fieldLanguage->title }}
                        @if($fieldLanguage->isBase())<span class="badge base">{{ __('Базовый') }}</span>@endif
                        @if(!$fieldLanguage->active)<span class="badge">{{ __('Неактивен') }}</span>@endif
                    </label>
                @endif
                <div class="input-group">
                    @if($fieldType === 'textarea')
                        <textarea id="{{ $fieldId }}" name="{{ $fieldName }}" rows="{{ $rows ?? 5 }}"
                            @if($fieldSummernote) data-summernote data-upload-url="{{ route('editor-images.store') }}" @endif
                            class="form-control form-input-value {{ $isMultilingual ? 'languages-'.$name : '' }}"
                            @if($isMultilingual) data-locale="{{ $fieldLanguage->locale }}" @endif
                            @required($isRequired) @readonly($readonly ?? false)
                            @if(isset($fieldMaxlength)) maxlength="{{ $fieldMaxlength }}" @endif
                            @if(isset($placeholder)) placeholder="{{ $placeholder }}" @elseif($isMultilingual) placeholder="{{ $fieldLanguage->title }}" @endif
                            @if($fieldDescription !== '') aria-describedby="{{ $fieldDescription }}" @endif
                            @error($fieldError) aria-invalid="true" @enderror
                            {{ $fieldAttributes }}>{{ $fieldValue }}</textarea>
                    @else
                        <input id="{{ $fieldId }}" type="{{ $fieldType }}" name="{{ $fieldName }}" value="{{ $fieldValue }}"
                            class="form-control form-input-value {{ $isMultilingual ? 'languages-'.$name : '' }}"
                            @if($isMultilingual) data-locale="{{ $fieldLanguage->locale }}" @endif
                            @required($isRequired) @readonly($readonly ?? false)
                            @if(isset($fieldMaxlength)) maxlength="{{ $fieldMaxlength }}" @endif
                            @if(isset($min)) min="{{ $min }}" @endif
                            @if(isset($minlength)) minlength="{{ $minlength }}" @endif
                            @if(isset($pattern)) pattern="{{ $pattern }}" @endif
                            @if(isset($placeholder)) placeholder="{{ $placeholder }}" @elseif($isMultilingual) placeholder="{{ $fieldLanguage->title }}" @endif
                            @if(($autofocus ?? false) && $loop->first) autofocus @endif
                            @if($fieldDescription !== '') aria-describedby="{{ $fieldDescription }}" @endif
                            @error($fieldError) aria-invalid="true" @enderror
                            {{ $fieldAttributes }}>
                    @endif
                </div>
                @error($fieldError)<div id="{{ $fieldId }}-error" class="alert alert-danger field-error" role="alert">{{ $message }}</div>@enderror
            </div>
        @endforeach
    </div>
    @if(isset($fieldNote))<p id="{{ $fieldLabelId }}-note" class="text-info hint">{{ $fieldNote }}</p>@endif
</div>

@if($fieldSummernote)
    @once
        @push('scripts')
            <link rel="stylesheet" href="{{ asset('vendor/summernote/summernote-lite.min.css') }}">
            @once('admin-jquery')
                <script src="{{ asset('vendor/jquery/jquery-3.7.1.min.js') }}"></script>
            @endonce
            <script src="{{ asset('vendor/summernote/summernote-lite.min.js') }}"></script>
            <script src="{{ asset('vendor/summernote/summernote-uk-UA.min.js') }}"></script>
            <script src="{{ asset('js/summernote.js') }}"></script>
        @endpush
    @endonce
@endif

@include('admin.details.translate')
