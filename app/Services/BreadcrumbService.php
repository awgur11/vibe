<?php

namespace App\Services;

use App\Models\Page;

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

    private function title(Page $page): string
    {
        return $page->title ?: $page->slag;
    }
}
