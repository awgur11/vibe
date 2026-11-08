<?php

namespace App\Models;

use Database\Factories\ProductCommentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductComment extends Model
{
    /** @use HasFactory<ProductCommentFactory> */
    use HasFactory;

    public const array STATUSES = [0 => 'Ожидает модерации', 1 => 'Одобрен', 2 => 'Отклонён'];

    protected $fillable = ['name', 'content', 'status'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['status' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
