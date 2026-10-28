<?php

namespace App\Models;

use App\Services\ImageService;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory;

    protected $table = 'pages';

    protected $fillable = ['type', 'template', 'slag', 'preview', 'icon', 'parent_id', 'category_id', 'position', 'public', 'is_menu'];

    protected function casts(): array
    {
        return ['template' => 'string', 'public' => 'boolean', 'is_menu' => 'boolean', 'position' => 'integer'];
    }

    public function getFhdAttribute(): ?string
    {
        return $this->previewUrl('fhd');
    }

    public function getOriginAttribute(): ?string
    {
        return $this->previewUrl();
    }

    public function previewUrl(?string $name = null): ?string
    {
        $path = $this->preview;

        if (! $path) {
            return null;
        }

        if (str_starts_with($path, '/') || str_starts_with($path, 'https://') || str_starts_with($path, 'http://')) {
            return $path;
        }

        if ($name !== null) {
            $thumbnail = ImageService::thumbnailPath($path, $name);
            if (Storage::disk('public')->exists($thumbnail)) {
                $path = $thumbnail;
            }
        }

        return Storage::disk('public')->url($path);
    }

    /** @return list<string> */
    public function previewPaths(): array
    {
        if (! $this->preview || ! str_starts_with($this->preview, 'pages/')) {
            return [];
        }

        return ImageService::paths($this->preview);
    }

    public function getAttribute(mixed $key): mixed
    {
        if (in_array($key, ['menu', 'title', 'content', 'short_content', 'tag_title', 'tag_description'], true)) {
            return $this->translation?->getAttribute($key);
        }

        return parent::getAttribute($key);
    }

    public function translation(): HasOne
    {
        return $this->hasOne(Page_languages::class, 'page_id')->where('lacale', app()->getLocale());
    }

    public function translations(): HasMany
    {
        return $this->hasMany(Page_languages::class, 'page_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function translated(string $field, Language $language): ?string
    {
        return $this->translations->firstWhere('lacale', $language->locale)?->getAttribute($field);
    }
}
