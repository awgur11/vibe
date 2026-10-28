<?php

namespace App\Models;

use App\Services\ImageService;
use Database\Factories\CatalogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Catalog extends Model
{
    /** @use HasFactory<CatalogFactory> */
    use HasFactory;

    protected $fillable = ['parent_id', 'position', 'preview', 'slag', 'public', 'popular'];

    protected function casts(): array
    {
        return ['parent_id' => 'integer', 'position' => 'integer', 'public' => 'boolean', 'popular' => 'boolean'];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function fvalues(): BelongsToMany
    {
        return $this->belongsToMany(Fvalue::class, 'catalog_fvalue');
    }

    public function filters(): BelongsToMany
    {
        return $this->belongsToMany(Filter::class, 'catalog_filter');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(Catalog_languages::class, 'catalog_id');
    }

    public function translation(): HasOne
    {
        return $this->hasOne(Catalog_languages::class, 'catalog_id')->where('lacale', app()->getLocale());
    }

    public function getAttribute(mixed $key): mixed
    {
        if (in_array($key, ['title', 'short_content', 'content', 'tag_title', 'tag_description'], true)) {
            return $this->translation?->getAttribute($key);
        }

        return parent::getAttribute($key);
    }

    public function translated(string $field, Language $language): ?string
    {
        return $this->translations->firstWhere('lacale', $language->locale)?->getAttribute($field);
    }

    public function previewUrl(?string $name = null): ?string
    {
        if (! $this->preview) {
            return null;
        }

        $path = $this->preview;
        if ($name !== null) {
            $thumbnail = ImageService::thumbnailPath($path, $name);
            if (Storage::disk('public')->exists($thumbnail)) {
                $path = $thumbnail;
            }
        }

        return Storage::disk('public')->url($path);
    }
}
