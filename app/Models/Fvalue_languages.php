<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fvalue_languages extends Model
{
    protected $table = 'fvalues_languages';

    public $timestamps = false;

    protected $fillable = ['fvalue_id', 'lacale', 'title'];

    public function fvalue(): BelongsTo
    {
        return $this->belongsTo(Fvalue::class);
    }
}
