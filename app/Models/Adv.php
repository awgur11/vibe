<?php

namespace App\Models;

use Database\Factories\AdvFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Adv extends Model
{
    /** @use HasFactory<AdvFactory> */
    use HasFactory;

    protected $fillable = ['icon', 'position', 'public'];

    public const array ICONS = [
        'i-truck' => 'Доставка',
        'i-box' => 'Каталог',
        'i-shield' => 'Гарантія',
        'i-headset' => 'Підтримка',
        'i-gear' => 'Підбір',
        'i-trophy' => 'Досвід',
        'i-users' => 'Команда',
        'i-check' => 'Якість',
    ];

    protected function casts(): array
    {
        return ['position' => 'integer', 'public' => 'boolean'];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(Adv_languages::class, 'adv_id');
    }

    public function translation(): HasOne
    {
        return $this->hasOne(Adv_languages::class, 'adv_id')->where('lacale', app()->getLocale());
    }

    public function getAttribute(mixed $key): mixed
    {
        if (in_array($key, ['title', 'description'], true)) {
            return $this->translation?->getAttribute($key);
        }

        return parent::getAttribute($key);
    }

    public function translated(string $field, Language $language): ?string
    {
        return $this->translations->firstWhere('lacale', $language->locale)?->getAttribute($field);
    }
}
