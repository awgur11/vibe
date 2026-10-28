<div class="catalog-branch" data-reorder data-reorder-url="{{ route('reorder.update', 'catalogs') }}" data-reorder-token="{{ csrf_token() }}" data-reorder-scope="{{ json_encode(['parent_id' => $parent?->id], JSON_THROW_ON_ERROR) }}">
    <p class="hint" data-reorder-status role="status" aria-live="polite"></p>
    <div class="table-wrap"><table data-reorder-list>
            @forelse($catalogs as $catalog)
                <tbody data-reorder-id="{{ $catalog->id }}">
                <tr>
                    <td><button class="button secondary" type="button" data-reorder-handle title="{{ __('Перетягуйте рядки') }}" aria-label="{{ __('Перемістити каталог') }} {{ $catalog->translated('title', $baseLanguage) }}" style="touch-action: none; cursor: grab">↕</button></td>
                    <td><div style="display: flex; align-items: center; gap: 12px">
                        @if($catalog->preview)
                            <img src="{{ $catalog->previewUrl('mini') }}" width="48" height="48" alt="" loading="lazy" style="object-fit: cover; border-radius: 8px">
                        @else
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" style="color: #6151d5; flex-shrink: 0"><path d="M3 7h7l2 2h9v11H3Z"/><path d="M3 7V4h7l2 3"/></svg>
                        @endif
                        <a href="{{ route('catalogs.edit', $catalog) }}">{{ $catalog->translated('title', $baseLanguage) ?? '—' }}</a>
                    </div></td>
                    <td>
                        @if($catalog->children_count > 0)
                            <button class="button secondary" type="button" data-catalog-toggle data-children-url="{{ route('catalogs.children', $catalog) }}" aria-expanded="false" aria-controls="catalog-children-{{ $catalog->id }}">
                                <span data-catalog-arrow aria-hidden="true">▸</span> {{ __('Підкаталоги') }} ({{ $catalog->children_count }})
                            </button>
                        @else
                            <span class="hint">—</span>
                        @endif
                    </td>
                    <td><input type="checkbox" data-publication-toggle data-url="{{ route('catalogs.publication.update', $catalog) }}" data-token="{{ csrf_token() }}" @checked($catalog->public) aria-label="{{ __('Опублікувати каталог') }} {{ $catalog->translated('title', $baseLanguage) }}" style="width: 18px; height: 18px; accent-color: #6151d5"></td>
                    <td><div class="actions">
                        <a class="button secondary" href="{{ route('catalogs.edit', $catalog) }}">{{ __('Редагувати') }}</a>
                        <form method="POST" action="{{ route('catalogs.destroy', $catalog) }}" onsubmit="return confirm({{ Illuminate\Support\Js::from(__('Видалити цей каталог? Підкаталоги залишаться в кореневому розділі.')) }})">
                            @csrf @method('DELETE')<button class="button danger" type="submit">{{ __('Видалити') }}</button>
                        </form>
                    </div></td>
                </tr>
                @if($catalog->children_count > 0)
                    <tr id="catalog-children-{{ $catalog->id }}" class="catalog-children" hidden>
                        <td colspan="5">
                            <div data-catalog-children-content></div>
                        </td>
                    </tr>
                @endif
                </tbody>
            @empty
                <tbody><tr><td colspan="5">{{ __('Каталоги ще не додано.') }}</td></tr></tbody>
            @endforelse
    </table></div>
</div>
