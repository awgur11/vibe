<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\HtmlString;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $fillable = ['catalog_id', 'available', 'articule', 'slag', 'price', 'discount', 'public', 'popular', 'position'];

    public const array AVAILABILITY = [
        0 => 'У наявності',
        1 => 'Під замовлення',
        2 => 'Немає в наявності',
    ];

    protected function casts(): array
    {
        return ['available' => 'integer', 'catalog_id' => 'integer', 'price' => 'decimal:2', 'discount' => 'decimal:2', 'final_price' => 'decimal:2', 'public' => 'boolean', 'popular' => 'boolean', 'position' => 'integer'];
    }

    protected $appends = ['preview', 'html_available'];

    protected static function booted(): void
    {
        static::saving(function (Product $product): void {
            $priceInCents = (int) str_replace('.', '', $product->price ?? '0.00');
            $discountInBasisPoints = (int) str_replace('.', '', $product->discount ?? '0.00');
            $finalPriceInCents = intdiv($priceInCents * (10000 - $discountInBasisPoints) + 5000, 10000);
            $product->final_price = intdiv($finalPriceInCents, 100).'.'.str_pad((string) ($finalPriceInCents % 100), 2, '0', STR_PAD_LEFT);
        });
    }

    public function pictures(): HasMany
    {
        return $this->hasMany(Picture::class)->orderBy('position')->orderBy('id');
    }

    public function getHtmlAvailableAttribute(): HtmlString
    {
        $classes = [
            0 => 'in-stock',
            1 => 'pre-order',
            2 => 'out-of-stock',
        ];

        $available = $this->available;

        if (!isset(self::AVAILABILITY[$available])) {
            return new HtmlString('');
        }

        return new HtmlString(sprintf(
            '<span class="%s"> %s</span>',
            $classes[$available],
            e(__(self::AVAILABILITY[$available]))
        ));
    }

    public function getPreviewAttribute(): ?string
    {
        return $this->preview();
    }

    public function preview(?string $thumbnail = null): ?string
    {
        $picture = $this->relationLoaded('pictures')
            ? $this->pictures->sortBy([['position', 'asc'], ['id', 'asc']])->first()
            : $this->pictures()->first();

        return $picture?->url($thumbnail);
    }

    public function catalog(): BelongsTo
    {
        return $this->belongsTo(Catalog::class);
    }

    public function fvalues(): BelongsToMany
    {
        return $this->belongsToMany(Fvalue::class, 'fvalue_product');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(Product_languages::class, 'product_id');
    }

    public function translation(): HasOne
    {
        return $this->hasOne(Product_languages::class, 'product_id')->where('lacale', app()->getLocale());
    }

    public function getAttribute(mixed $key): mixed
    {
        if (in_array($key, ['title', 'description', 'tag_title', 'tag_description'], true)) {
            return $this->translation?->getAttribute($key);
        }

        return parent::getAttribute($key);
    }

    public function translated(string $field, Language $language): ?string
    {
        return $this->translations->firstWhere('lacale', $language->locale)?->getAttribute($field);
    }
}
