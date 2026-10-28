<?php

namespace App\Models;

use App\Services\ImageService;
use Database\Factories\SlideFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Slide extends Model
{
    /** @use HasFactory<SlideFactory> */
    use HasFactory;

    protected $fillable = ['preview', 'link', 'position', 'public'];

    protected function casts(): array
    {
        return ['position' => 'integer', 'public' => 'boolean'];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(Slide_languages::class, 'slide_id');
    }

    public function translation(): HasOne
    {
        return $this->hasOne(Slide_languages::class, 'slide_id')->where('lacale', app()->getLocale());
    }

    public function getAttribute(mixed $key): mixed
    {
        if (in_array($key, ['title', 'subtitle'], true)) {
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
