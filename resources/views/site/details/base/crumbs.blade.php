<nav class="pt-4" aria-label="{{ __('Хлібні крихти') }}">
    <ol class="breadcrumb mb-0">
        @foreach($crumbs as $crumb)
            @if($crumb['url'] !== null)
                <li class="breadcrumb-item"><a href="{{ $crumb['url'] }}">{{ $crumb['title'] }}</a></li>
            @else
                <li class="breadcrumb-item active" aria-current="page">{{ $crumb['title'] }}</li>
            @endif
        @endforeach
    </ol>
</nav>
