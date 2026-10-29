@extends('layouts.dashboard')
@section('title', __('Отзывы о товарах'))
@section('content')
<div class="heading"><h1>{{ __('Отзывы о товарах') }}</h1></div>
<div class="panel"><div class="table-wrap"><table>
<thead><tr><th>{{ __('Товар') }}</th><th>{{ __('Имя') }}</th><th>{{ __('Отзыв') }}</th><th>{{ __('Статус') }}</th><th>{{ __('Действия') }}</th></tr></thead>
<tbody>@forelse($comments as $comment)<tr>
<td>@if($comment->product)<a href="{{ route('product', ['product' => $comment->product->slag]) }}">{{ $comment->product->title ?: $comment->product->slag }}</a>@endif<br><small>{{ $comment->created_at->format('d.m.Y H:i') }}</small></td>
<td>{{ $comment->name }}</td><td style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $comment->content }}</td>
<td><form action="{{ route('product-comments.update', $comment) }}" method="POST" data-comment-status-form>
    @csrf @method('PATCH')
    <select class="comment-status-select" name="status" aria-label="{{ __('Статус') }}" data-status="{{ $comment->status }}">
        @foreach(\App\Models\ProductComment::STATUSES as $value => $label)<option value="{{ $value }}" @selected($comment->status === $value)>{{ __($label) }}</option>@endforeach
    </select>
</form></td>
<td><form action="{{ route('product-comments.destroy', $comment) }}" method="POST" onsubmit="return confirm({{ Illuminate\Support\Js::from(__('Удалить отзыв?')) }})">@csrf @method('DELETE')<button class="button danger" type="submit">{{ __('Удалить') }}</button></form></td>
</tr>@empty<tr><td colspan="5">{{ __('Отзывов пока нет.') }}</td></tr>@endforelse</tbody></table></div>
@if($comments->hasPages())<nav class="pagination">@if($comments->previousPageUrl())<a href="{{ $comments->previousPageUrl() }}">{{ __('Назад') }}</a>@endif<span>{{ $comments->currentPage() }} / {{ $comments->lastPage() }}</span>@if($comments->nextPageUrl())<a href="{{ $comments->nextPageUrl() }}">{{ __('Далее') }}</a>@endif</nav>@endif
</div>
@endsection

@push('styles')
<style>
.comment-status-select{min-width:190px;max-width:100%;padding:10px 34px 10px 12px;border:1px solid #d9d5ee;border-radius:9px;background-color:#f6f4ff;color:#5142c1;font:600 14px/1.4 system-ui,sans-serif;cursor:pointer;transition:border-color .15s,box-shadow .15s}.comment-status-select:hover{border-color:#6151d5}.comment-status-select:focus-visible{outline:none;border-color:#6151d5;box-shadow:0 0 0 3px #6151d522}.comment-status-select[data-status=approved]{background-color:#edf8ef;color:#24683b;border-color:#b9dfc2}.comment-status-select[data-status=rejected]{background-color:#fff0f0;color:#a52626;border-color:#f0c5c5}.comment-status-select:disabled{opacity:.65;cursor:wait}.comment-status-select option{background:#fff;color:#20202a}
</style>
@endpush
@push('scripts')
<script>
document.querySelectorAll('[data-comment-status-form]').forEach(form => {
    const select = form.querySelector('select');
    form.addEventListener('submit', event => event.preventDefault());
    select.addEventListener('change', async () => {
        const previous = select.dataset.status;
        const body = new FormData(form);
        select.disabled = true;
        select.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(form.action, {
                method: 'POST', body, credentials: 'same-origin',
                headers: {'Accept': 'application/json'},
                signal: AbortSignal.timeout(15000),
            });
            const data = await response.json();
            if (!response.ok) throw new Error('Status update failed');
            select.value = select.dataset.status = data.status;
            window.adminToast(data.message);
        } catch (error) {
            select.value = previous;
            window.adminToast({{ Illuminate\Support\Js::from(__('Не удалось сохранить статус. Попробуйте ещё раз.')) }}, 'error');
        } finally {
            select.disabled = false;
            select.removeAttribute('aria-busy');
        }
    });
});
</script>
@endpush
