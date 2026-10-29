<?php

namespace App\Services;

use App\Models\Catalog;
use App\Models\Page;
use App\Models\Product;

class BreadcrumbService
{
    /** @return list<array{title: string, url: ?string}> */
    public function forPage(?Page $page = null, ?Page $blog = null): array
    {
        $crumbs = [['title' => __('Головна'), 'url' => $page !== null ? route('index') : null]];

        if ($page === null) {
            return $crumbs;
        }

        if ($blog !== null) {
            $crumbs[] = ['title' => $this->title($blog), 'url' => route('page', $blog->slag)];
        }

        $crumbs[] = ['title' => $this->title($page), 'url' => null];

        return $crumbs;
    }

    /** @return list<array{title: string, url: ?string}> */
    public function forTitle(string $title): array
    {
        return [['title' => __('Головна'), 'url' => route('index')], ['title' => $title, 'url' => null]];
    }

    /** @return list<array{title: string, url: ?string}> */
    public function forCatalog(Catalog $catalog): array
    {
        $path = [];
        $seen = [];
        $current = $catalog;
        while ($current !== null && ! isset($seen[$current->id])) {
            $seen[$current->id] = true;
            if ($current->public) {
                array_unshift($path, ['title' => $current->title ?: $current->slag, 'url' => $current->is($catalog) ? null : route('catalog', ['catalogSlag' => $current->slag])]);
            }
            $current = $current->parent;
        }

        return [['title' => __('Головна'), 'url' => route('index')], ...$path];
    }

    /** @return list<array{title: string, url: ?string}> */
    public function forProduct(Product $product): array
    {
        $crumbs = $product->catalog?->public ? $this->forCatalog($product->catalog) : $this->forPage();
        $last = array_key_last($crumbs);
        $crumbs[$last]['url'] = $product->catalog?->public
            ? route('catalog', ['catalogSlag' => $product->catalog->slag]) : route('index');
        $crumbs[] = ['title' => $product->title ?: $product->slag, 'url' => null];

        return $crumbs;
    }

    /** @return list<array{title: string, url: ?string}> */
    public function forRequest(): array
    {
        $title = match (request()->route()?->getName()) {
            'favorites.index' => __('Избранное'),
            'comparison.show' => __('Сравнение'),
            'order.checkout' => __('Оформление заказа'),
            'account.profile' => __('Мой профиль'),
            'account.orders' => __('Мои заказы'),
            'verification.notice' => __('Подтвердите электронную почту'),
            default => null,
        };

        return $title === null ? $this->forPage() : $this->forTitle($title);
    }

    private function title(Page $page): string
    {
        return $page->title ?: $page->slag;
    }
}
