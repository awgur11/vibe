@once
    @push('scripts')
        <style>
            .settings-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:24px}.setting-block{min-width:0}.setting-title{margin:0 0 20px;font-size:19px}.setting-language{font-size:15px;margin:0 0 12px}.setting-options{padding:0;border:0;margin:0}.setting-options legend{margin-bottom:12px;font-weight:600}.setting-options .check{font-weight:400}.setting-options input[type=radio]{width:18px!important;height:18px;accent-color:#6151d5}.setting-block .field-error:empty{display:none}.setting-image-layout{display:grid;grid-template-columns:minmax(110px,160px) minmax(0,1fr);gap:20px}.setting-image-box{display:flex;align-items:center;justify-content:center;min-height:150px;padding:12px;border:1px dashed #cfcfd9;border-radius:10px;background:#f5f5f7;color:#676774;font-size:14px;text-align:center}.setting-image-box img{max-width:100%;max-height:180px;object-fit:contain}.setting-image-box [hidden]{display:none}.setting-image-controls{min-width:0}.setting-block form+form{border-top:1px solid #e6e6eb;padding-top:20px;margin-top:20px}.settings-full{grid-column:1/-1}@media(max-width:1100px){.settings-grid{grid-template-columns:1fr}}@media(max-width:500px){.setting-image-layout{grid-template-columns:1fr}}
            .setting-phones-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:20px}.setting-phones-heading .setting-title{margin:0}.setting-phones-heading .button{white-space:normal;text-align:center}.setting-phone-row{display:flex;align-items:flex-end;gap:10px}.setting-phone-row>label{flex:1;min-width:0;margin:0}.setting-phone-row .setting-phone-remove{flex-shrink:0;width:44px;height:44px;padding:0}
            .setting-field-actions{justify-content:space-between;flex-wrap:nowrap}.setting-field-actions .translate-button{margin-left:auto;white-space:normal;text-align:center}.setting-block [data-translation-status]:empty{display:none}
        </style>
        <script src="{{ asset('js/admin-settings.js') }}?v={{ filemtime(public_path('js/admin-settings.js')) }}" defer></script>
    @endpush
@endonce

@include('admin.details.translate')
