@once
    @push('scripts')
        <style>
            [data-reorder-list] .ui-sortable-helper{display:table;background:#fff;box-shadow:0 12px 32px #20202a33;border:2px solid #6151d5;border-radius:9px;z-index:1100;cursor:grabbing}
            [data-reorder-list] .ui-sortable-helper td{background:#f5f2ff}
            [data-reorder-list] .reorder-placeholder{visibility:visible!important;background:#eeeafd}
            [data-reorder-list] .reorder-placeholder td{border-top:2px dashed #8a7bd0;border-bottom:2px dashed #8a7bd0;background:#eeeafd}
            [data-reorder-list] .reorder-placeholder td:first-child{border-left:2px dashed #8a7bd0}
            [data-reorder-list] .reorder-placeholder td:last-child{border-right:2px dashed #8a7bd0}
        </style>
        @once('admin-jquery')
            <script src="{{ asset('vendor/jquery/jquery-3.7.1.min.js') }}"></script>
        @endonce
        <script src="{{ asset('vendor/jquery-ui/jquery-ui-1.14.1.min.js') }}"></script>
        <script src="{{ asset('js/admin-reorder.js') }}" defer></script>
    @endpush
@endonce
