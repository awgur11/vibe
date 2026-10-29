<?php

namespace App\Services;

use App\Models\Catalog;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class SeoService
{
    /** @param array<string, mixed> $data */
    public function render(array $data = []): HtmlString
    {
        $seo = match (request()->route()?->getName()) {
            'index' => $this->forIndex(),
            'product' => ($data['product'] ?? null) instanceof Product ? $this->forProduct($data['product']) : $this->defaults(),
            'catalog' => ($data['catalog'] ?? null) instanceof Catalog ? $this->forCatalog($data['catalog']) : $this->defaults(),
            'page', 'article' => ($data['page'] ?? null) instanceof Page ? $this->forPage($data['page']) : $this->defaults(),
            default => $this->forOther($data),
        };
        ['title' => $title, 'description' => $description] = $seo;

        $privatePage = request()->routeIs('account.*', 'order.*', 'favorites.*', 'comparison.*', 'verification.*');
        $index = ! $privatePage && setting('robots_index', true) ? 'index' : 'noindex';
        $follow = setting('robots_follow', true) ? 'follow' : 'nofollow';
        $tags = [
            '<title>'.e($this->text($title)).'</title>',
            '<meta name="description" content="'.e($this->text($description)).'">',
            '<meta name="robots" content="'.$index.', '.$follow.'">',
        ];
        $favicon = setting('favicon');
        if (is_string($favicon) && $favicon !== '') {
            $url = str_starts_with($favicon, '/') || str_starts_with($favicon, 'https://') || str_starts_with($favicon, 'http://')
                ? $favicon : Storage::disk('public')->url($favicon);
            $tags[] = '<link rel="icon" href="'.e($url).'">';
        }

        return new HtmlString(implode("\n", $tags));
    }

    /** @return array{title: string, description: string} */
    public function forProduct(Product $product): array
    {
        $name = $product->title ?: $product->slag;
        $defaults = $this->defaults();
        $titleTemplate = $this->text(setting('product_title_seo_tag'));
        $descriptionTemplate = $this->text(setting('product_description_seo_tag'));

        return [
            'title' => $this->text($product->tag_title) ?: ($titleTemplate !== '' ? str_replace('s%', $name, $titleTemplate) : $name),
            'description' => $this->text($product->tag_description) ?: ($descriptionTemplate !== '' ? str_replace('s%', $name, $descriptionTemplate) : $defaults['description']),
        ];
    }

    /** @return array{title: string, description: string} */
    public function forCatalog(Catalog $catalog): array
    {
        return [
            'title' => $this->text($catalog->tag_title) ?: ($catalog->title ?: $catalog->slag),
            'description' => $this->text($catalog->tag_description) ?: ($this->text($catalog->short_content) ?: $this->defaults()['description']),
        ];
    }

    /** @return array{title: string, description: string} */
    public function forPage(Page $page): array
    {
        return [
            'title' => $this->text($page->tag_title) ?: ($page->title ?: $page->slag),
            'description' => $this->text($page->tag_description) ?: ($this->text($page->short_content) ?: $this->defaults()['description']),
        ];
    }

    /** @return array{title: string, description: string} */
    public function forIndex(): array
    {
        $defaults = $this->defaults();

        return [
            'title' => $this->text(setting('index_title_seo_tag')) ?: $defaults['title'],
            'description' => $this->text(setting('index_description_seo_tag')) ?: $defaults['description'],
        ];
    }

    /** @return array{title: string, description: string} */
    private function defaults(): array
    {
        return ['title' => $this->text(setting('site_title')) ?: config('app.name'), 'description' => $this->text(setting('site_description'))];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{title: string, description: string}
     */
    private function forOther(array $data): array
    {
        $seo = $this->defaults();
        $crumbs = $data['crumbs'] ?? [];
        if (count($crumbs) > 1) {
            $seo['title'] = $crumbs[array_key_last($crumbs)]['title'].' — '.$seo['title'];
        }

        return $seo;
    }

    private function text(?string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', strip_tags($value ?? '')) ?? '');
    }
}
