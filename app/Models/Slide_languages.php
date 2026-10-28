<?php

namespace App\Models;

use Database\Factories\SlideLanguagesFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Slide_languages extends Model
{
    /** @use HasFactory<SlideLanguagesFactory> */
    use HasFactory;

    protected $table = 'slides_languages';

    public $timestamps = false;

    protected $fillable = ['slide_id', 'lacale', 'title', 'subtitle'];

    protected static function newFactory(): SlideLanguagesFactory
    {
        return SlideLanguagesFactory::new();
    }

    public function slide(): BelongsTo
    {
        return $this->belongsTo(Slide::class);
    }
}
