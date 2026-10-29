@extends('layouts.dashboard')
@section('title', __('Отзывы о товарах'))
@section('content')
<div class="heading"><h1>{{ __('Отзывы о товарах') }}</h1></div>
<div class="panel"><div class="table-wrap"><table>
<thead><tr><th>{{ __('Товар') }}</th><th>{{ __('Имя') }}</th><th>{{ __('Отзыв') }}</th><th>{{ __('Статус') }}</th><th>{{ __('Действия') }}</th></tr></thead>
<tbody>@forelse($comments as $comment)<tr>
<td>@if($comment->product)<a href="{{ route('product', ['product' => $comment->product->slag]) }}">{{ $comment->product->title ?: $comment->product->slag }}</a>@endif<br><small>{{ $comment->created_at->format('d.m.Y H:i') }}</small></td>
<td>{{ $comment->name }}</td><td style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $comment->content }}</td><td>{{ __(\App\Models\ProductComment::STATUSES[$comment->status] ?? $comment->status) }}</td>
<td><form action="{{ route('product-comments.update', $comment) }}" method="POST">@csrf @method('PATCH')<label for="comment-status-{{ $comment->id }}">{{ __('Статус') }}</label><select id="comment-status-{{ $comment->id }}" name="status">@foreach(\App\Models\ProductComment::STATUSES as $value => $label)<option value="{{ $value }}" @selected($comment->status === $value)>{{ __($label) }}</option>@endforeach</select><button class="button" type="submit">{{ __('Сохранить') }}</button></form>
<form action="{{ route('product-comments.destroy', $comment) }}" method="POST" onsubmit="return confirm({{ Illuminate\Support\Js::from(__('Удалить отзыв?')) }})">@csrf @method('DELETE')<button class="button danger" type="submit">{{ __('Удалить') }}</button></form></td>
</tr>@empty<tr><td colspan="5">{{ __('Отзывов пока нет.') }}</td></tr>@endforelse</tbody></table></div>
@if($comments->hasPages())<nav class="pagination">@if($comments->previousPageUrl())<a href="{{ $comments->previousPageUrl() }}">{{ __('Назад') }}</a>@endif<span>{{ $comments->currentPage() }} / {{ $comments->lastPage() }}</span>@if($comments->nextPageUrl())<a href="{{ $comments->nextPageUrl() }}">{{ __('Далее') }}</a>@endif</nav>@endif
</div>
@endsection
