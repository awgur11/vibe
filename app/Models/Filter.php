<?php

namespace App\Models;

use Database\Factories\FilterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Filter extends Model
{
    /** @use HasFactory<FilterFactory> */
    use HasFactory;

    protected $fillable = ['slag', 'position'];

    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    public function fvalues(): HasMany
    {
        return $this->hasMany(Fvalue::class)->orderBy('position')->orderByDesc('id');
    }

    public function catalogs(): BelongsToMany
    {
        return $this->belongsToMany(Catalog::class, 'catalog_filter');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(Filter_languages::class, 'filter_id');
    }

    public function translation(): HasOne
    {
        return $this->hasOne(Filter_languages::class, 'filter_id')->where('lacale', app()->getLocale());
    }

    public function getAttribute(mixed $key): mixed
    {
        if (in_array($key, ['title'], true)) {
            return $this->translation?->getAttribute($key);
        }

        return parent::getAttribute($key);
    }

    public function translated(string $field, Language $language): ?string
    {
        return $this->translations->firstWhere('lacale', $language->locale)?->getAttribute($field);
    }
}
