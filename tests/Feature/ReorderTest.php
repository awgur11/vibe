<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReorderTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_reorder(): void
    {
        $this->patchJson(route('reorder.update', 'pages'), ['ids' => [1]])->assertUnauthorized();
    }

    public function test_reordering_a_paginated_subset_preserves_surrounding_records_and_excludes_articles(): void
    {
        $pages = Page::factory()->count(25)->create(['type' => 'pages', 'position' => 0]);
        $article = Page::factory()->create(['type' => 'articles', 'position' => 77]);
        $originalIds = $pages->sortByDesc('id')->modelKeys();
        $visibleIds = array_slice($originalIds, 20);
        $expectedIds = [...array_slice($originalIds, 0, 20), ...array_reverse($visibleIds)];
        $this->actingAs(User::factory()->create());

        $this->patchJson(route('reorder.update', 'pages'), ['ids' => array_reverse($visibleIds)])->assertOk();

        $this->assertSame($expectedIds, Page::where('type', 'pages')->orderBy('position')->pluck('id')->all());
        $this->assertSame(77, $article->fresh()->position);
        $this->assertSame(range(0, 24), Page::where('type', 'pages')->orderBy('position')->pluck('position')->all());
        $this->get(route('pages.index', ['page' => 2]))->assertOk()->assertSee('data-reorder-list', false);
    }

    public function test_invalid_or_out_of_scope_ids_do_not_change_positions(): void
    {
        $page = Page::factory()->create(['position' => 5]);
        $article = Page::factory()->create(['type' => 'articles', 'position' => 8]);
        $this->actingAs(User::factory()->create());

        foreach ([[], [$page->id, $page->id], [$page->id, 999999], [$page->id, $article->id], ['bad']] as $ids) {
            $this->patchJson(route('reorder.update', 'pages'), ['ids' => $ids])->assertUnprocessable();
            $this->assertSame(5, $page->fresh()->position);
            $this->assertSame(8, $article->fresh()->position);
        }
        $this->patchJson(route('reorder.update', 'unknown'), ['ids' => [$page->id]])->assertNotFound();
    }

    public function test_controller_supports_another_configured_resource_without_changes(): void
    {
        config(['reordering.resources.articles' => [
            'model' => Page::class, 'column' => 'position', 'where' => [['type', '=', 'articles']],
        ]]);
        $first = Page::factory()->create(['type' => 'articles', 'position' => 0]);
        $second = Page::factory()->create(['type' => 'articles', 'position' => 1]);
        $page = Page::factory()->create(['type' => 'pages', 'position' => 9]);
        $this->actingAs(User::factory()->create());

        $this->patchJson(route('reorder.update', 'articles'), ['ids' => [$second->id, $first->id]])->assertOk();

        $this->assertSame(0, $second->fresh()->position);
        $this->assertSame(1, $first->fresh()->position);
        $this->assertSame(9, $page->fresh()->position);
    }
}
