<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Brand_languages extends Model
{
    protected $table = 'brands_languages';

    public $timestamps = false;

    protected $fillable = ['brand_id', 'lacale', 'title'];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
}
