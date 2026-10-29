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

    public const array STATUSES = ['pending' => 'Ожидает модерации', 'approved' => 'Одобрен', 'rejected' => 'Отклонён'];

    protected $fillable = ['name', 'content', 'status'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
