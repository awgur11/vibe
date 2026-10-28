@php
    $definitionToken = Illuminate\Support\Facades\Crypt::encryptString(json_encode([
        'key' => $key, 'user_id' => auth()->id(), 'definition' => ['type' => 'phones', 'languages' => false],
    ], JSON_THROW_ON_ERROR));
    $phones = $settingValues[$key] ?? [];
    $phones = is_array($phones) ? $phones : [];
@endphp
<section class="panel setting-block">
    <form method="POST" action="{{ route('settings.fields.save', $key) }}" data-setting-form data-setting-phones>
        <div class="setting-phones-heading">
            <h2 class="setting-title">{{ $title }}</h2>
            <button class="button secondary" type="button" data-phone-add>{{ __('Додати телефон') }}</button>
        </div>
        @csrf
        <input type="hidden" name="_definition" value="{{ $definitionToken }}">
        <input type="hidden" name="value" value="">
        <div data-phone-list>
            @foreach($phones as $phone)
                <div class="field setting-phone-row" data-phone-row>
                    <label>{{ __('Номер телефону') }}<input type="tel" name="value[]" value="{{ $phone }}" maxlength="40" required autocomplete="tel"></label>
                    <button class="button danger setting-phone-remove" type="button" data-phone-remove title="{{ __('Видалити телефон') }}" aria-label="{{ __('Видалити телефон') }}"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/></svg></button>
                </div>
            @endforeach
        </div>
        <template data-phone-template>
            <div class="field setting-phone-row" data-phone-row>
                <label>{{ __('Номер телефону') }}<input type="tel" name="value[]" maxlength="40" required autocomplete="tel"></label>
                <button class="button danger setting-phone-remove" type="button" data-phone-remove title="{{ __('Видалити телефон') }}" aria-label="{{ __('Видалити телефон') }}"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/></svg></button>
            </div>
        </template>
        <p class="hint">{{ __('До 20 номерів. Після редагування натисніть «Зберегти».') }}</p>
        <div class="actions">
            <button class="button secondary" type="submit">{{ __('Зберегти') }}</button>
        </div>
        <p class="field-error" data-setting-error role="alert"></p>
    </form>
</section>
@include('admin.details.settings.assets')
