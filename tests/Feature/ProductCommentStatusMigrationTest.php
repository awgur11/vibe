<?php

namespace Tests\Feature;

use App\Models\ProductComment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductCommentStatusMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_comments_keep_their_status_when_migrated_and_rolled_back(): void
    {
        $migration = require database_path('migrations/2026_11_03_172457_convert_product_comment_status_to_integer.php');
        $migration->down();

        $comments = [];
        foreach (['pending', 'approved', 'rejected'] as $status) {
            $comment = ProductComment::factory()->create();
            DB::table('product_comments')->where('id', $comment->id)->update(['status' => $status]);
            $comments[] = $comment;
        }

        $migration->up();

        foreach ($comments as $status => $comment) {
            $this->assertSame($status, $comment->fresh()->status);
            $this->assertDatabaseHas('product_comments', ['id' => $comment->id, 'status' => $status]);
        }

        $comment = ProductComment::factory()->create();
        DB::table('product_comments')->where('id', $comment->id)->delete();
        $attributes = $comment->getAttributes();
        unset($attributes['status']);
        DB::table('product_comments')->insert($attributes);
        $this->assertSame(0, $comment->fresh()->status);

        $migration->down();

        foreach (['pending', 'approved', 'rejected'] as $index => $status) {
            $this->assertDatabaseHas('product_comments', ['id' => $comments[$index]->id, 'status' => $status]);
        }

        $migration->up();
    }
}
