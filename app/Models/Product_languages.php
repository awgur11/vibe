<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product_languages extends Model
{
    protected $table = 'products_languages';

    public $timestamps = false;

    protected $fillable = ['product_id', 'lacale', 'title', 'description', 'tag_title', 'tag_description'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
