<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Adv_languages extends Model
{
    protected $table = 'advs_languages';

    public $timestamps = false;

    protected $fillable = ['adv_id', 'lacale', 'title', 'description'];

    public function adv(): BelongsTo
    {
        return $this->belongsTo(Adv::class);
    }
}
