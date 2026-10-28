<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Filter_languages extends Model
{
    protected $table = 'filters_languages';

    public $timestamps = false;

    protected $fillable = ['filter_id', 'lacale', 'title'];

    public function filter(): BelongsTo
    {
        return $this->belongsTo(Filter::class);
    }
}
