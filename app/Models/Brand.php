<?php

namespace App\Models;

use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Brand extends Model
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory;

    protected $fillable = ['name', 'position', 'public'];

    protected function casts(): array
    {
        return ['position' => 'integer', 'public' => 'boolean'];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(Brand_languages::class, 'brand_id');
    }

    public function translation(): HasOne
    {
        return $this->hasOne(Brand_languages::class, 'brand_id')->where('lacale', app()->getLocale());
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
