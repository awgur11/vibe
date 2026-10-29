<?php

namespace App\Models;

use Database\Factories\ComparisonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comparison extends Model
{
    /** @use HasFactory<ComparisonFactory> */
    use HasFactory;

    protected $fillable = ['client_id', 'product_id'];

    protected function casts(): array
    {
        return ['product_id' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
