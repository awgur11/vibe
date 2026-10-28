<?php

namespace App\Models;

use Database\Factories\CatalogLanguagesFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Catalog_languages extends Model
{
    /** @use HasFactory<CatalogLanguagesFactory> */
    use HasFactory;

    protected $table = 'catalogs_languages';

    public $timestamps = false;

    protected $fillable = ['catalog_id', 'lacale', 'title', 'short_content', 'content', 'tag_title', 'tag_description'];

    protected static function newFactory(): CatalogLanguagesFactory
    {
        return CatalogLanguagesFactory::new();
    }

    public function catalog(): BelongsTo
    {
        return $this->belongsTo(Catalog::class);
    }
}
