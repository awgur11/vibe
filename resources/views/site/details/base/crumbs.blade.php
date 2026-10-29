@if(!empty($crumbs))
<nav class="site-crumbs" aria-label="{{ __('Хлебные крошки') }}">
    <ol class="breadcrumb">
        @foreach($crumbs as $crumb)
            @if($crumb['url'] !== null)
                <li class="breadcrumb-item"><a href="{{ $crumb['url'] }}">{{ $crumb['title'] }}</a></li>
            @else
                <li class="breadcrumb-item active" aria-current="page">{{ $crumb['title'] }}</li>
            @endif
        @endforeach
    </ol>
</nav>
@endif
