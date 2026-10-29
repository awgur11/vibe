<?php

namespace Tests\Feature;

use App\Models\Catalog;
use App\Models\Product;
use App\Models\ProductComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_page_shows_gallery_characteristics_and_only_approved_comments(): void
    {
        $product = Product::factory()->for(Catalog::factory()->state(['public' => true]))->create(['public' => true, 'available' => 0, 'price' => 120]);
        $product->pictures()->create(['path' => 'products/first.jpg', 'position' => 0]);
        $product->pictures()->create(['path' => 'products/second.jpg', 'position' => 1]);
        ProductComment::factory()->for($product)->create(['content' => 'Visible approved review', 'status' => 'approved']);
        ProductComment::factory()->for($product)->create(['content' => 'Private pending review']);
        ProductComment::factory()->for($product)->create(['content' => 'Private rejected review', 'status' => 'rejected']);
        $this->get(route('product', ['product' => $product->slag]))->assertOk()->assertViewIs('site.product')
            ->assertSee('data-gallery-image', false)->assertSee('products/second.jpg')->assertSee('120.00')
            ->assertSee('Visible approved review')->assertDontSee('Private pending review')->assertDontSee('Private rejected review');
    }

    public function test_guest_comment_is_pending_even_when_approval_is_forged(): void
    {
        $product = Product::factory()->create(['public' => true, 'available' => 0]);
        $this->post(route('product.comments.store', ['product' => $product->slag]), ['name' => 'Guest', 'content' => '<script>alert(1)</script>', 'status' => 'approved'])
            ->assertRedirect(route('product', ['product' => $product->slag]).'#reviews')->assertSessionHas('comment_status');
        $comment = ProductComment::sole();
        $this->assertSame('pending', $comment->status);
        $comment->update(['status' => 'approved']);
        $this->get(route('product', ['product' => $product->slag]))->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)->assertSee('i-no-image');
    }

    public function test_invalid_comments_and_hidden_products_are_blocked(): void
    {
        $product = Product::factory()->create(['public' => true]);
        $this->post(route('product.comments.store', ['product' => $product->slag]), ['name' => '', 'content' => 'x'])->assertSessionHasErrors(['name', 'content']);
        $product->update(['public' => false]);
        $this->get(route('product', ['product' => $product->slag]))->assertNotFound();
        $this->post(route('product.comments.store', ['product' => $product->slag]), ['name' => 'Guest', 'content' => 'Hidden review'])->assertNotFound();
        $this->assertDatabaseCount('product_comments', 0);
    }

    public function test_moderation_is_protected_and_admin_can_approve_reject_and_delete(): void
    {
        $comment = ProductComment::factory()->create();
        $this->get(route('product-comments.index'))->assertRedirect(route('login'));
        $this->patch(route('product-comments.update', $comment), ['status' => 'approved'])->assertRedirect(route('login'));
        $this->delete(route('product-comments.destroy', $comment))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('product-comments.index'))->assertOk()->assertSee($comment->content);
        $this->patch(route('product-comments.update', $comment), ['status' => 'approved', 'content' => 'Forged replacement'])->assertSessionHasNoErrors();
        $this->assertSame('approved', $comment->fresh()->status);
        $this->assertNotSame('Forged replacement', $comment->fresh()->content);
        $this->patch(route('product-comments.update', $comment), ['status' => 'invalid'])->assertSessionHasErrors('status');
        $this->patch(route('product-comments.update', $comment), ['status' => 'rejected'])->assertSessionHasNoErrors();
        $this->assertSame('rejected', $comment->fresh()->status);
        $this->delete(route('product-comments.destroy', $comment))->assertRedirect();
        $this->assertModelMissing($comment);
    }
}
