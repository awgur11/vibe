<?php

namespace App\Models;

use App\Services\ImageService;
use Database\Factories\PictureFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Picture extends Model
{
    /** @use HasFactory<PictureFactory> */
    use HasFactory;

    protected $fillable = ['path', 'position', 'product_id'];

    protected function casts(): array
    {
        return ['position' => 'integer', 'product_id' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function url(?string $thumbnail = null): string
    {
        $path = $this->path;
        if ($thumbnail !== null) {
            $thumbnailPath = ImageService::thumbnailPath($path, $thumbnail);
            if (Storage::disk('public')->exists($thumbnailPath)) {
                $path = $thumbnailPath;
            }
        }

        return Storage::disk('public')->url($path);
    }
    public function fhd(): string
    {
        return $this->url('fhd');
    }

    public function one(): string
    {
        return $this->url('one');
    }

    public function two(): string
    {
        return $this->url('two');
    }

    public function three(): string
    {
        return $this->url('three');
    }

    public function four(): string
    {
        return $this->url('four');
    }

    public function five(): string
    {
        return $this->url('five');
    }

    public function mini(): string
    {
        return $this->url('mini');
    }

    /** @return array{fhd: string, one: string, two: string, three: string, four: string, five: string, mini: string} */
    public function thumbnails(): array
    {
        $thumbnails = [];

        foreach (array_keys(ImageService::THUMB_WIDTHS) as $name) {
            $thumbnails[$name] = $this->url($name);
        }

        return $thumbnails;
    }

}
