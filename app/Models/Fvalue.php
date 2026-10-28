<?php

namespace App\Models;

use Database\Factories\FvalueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Fvalue extends Model
{
    /** @use HasFactory<FvalueFactory> */
    use HasFactory;

    protected $fillable = ['filter_id', 'slag', 'position'];

    protected function casts(): array
    {
        return ['filter_id' => 'integer', 'position' => 'integer'];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'fvalue_product');
    }

    public function catalogs(): BelongsToMany
    {
        return $this->belongsToMany(Catalog::class, 'catalog_fvalue');
    }

    public function filter(): BelongsTo
    {
        return $this->belongsTo(Filter::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(Fvalue_languages::class, 'fvalue_id');
    }

    public function translation(): HasOne
    {
        return $this->hasOne(Fvalue_languages::class, 'fvalue_id')->where('lacale', app()->getLocale());
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
