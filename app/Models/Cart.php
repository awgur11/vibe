<?php

namespace App\Models;

use Database\Factories\CartFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cart extends Model
{
    /** @use HasFactory<CartFactory> */
    use HasFactory;

    use MassPrunable;

    public const string CLIENT_COOKIE = 'cart_client';

    public const int MAX_QUANTITY = 999;

    protected $fillable = ['client_id', 'product_id', 'quantity', 'expires_at'];

    protected function casts(): array
    {
        return ['product_id' => 'integer', 'quantity' => 'integer', 'expires_at' => 'datetime'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function prunable(): Builder
    {
        return static::where('expires_at', '<=', now());
    }
}
