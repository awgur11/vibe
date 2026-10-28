<?php

namespace App\Models;

use Database\Factories\PageLanguagesFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Page_languages extends Model
{
    /** @use HasFactory<PageLanguagesFactory> */
    use HasFactory;

    protected $table = 'pages_languages';

    public $timestamps = false;

    protected $fillable = ['menu', 'title', 'content', 'short_content', 'lacale', 'page_id', 'tag_title', 'tag_description'];

    protected static function newFactory(): PageLanguagesFactory
    {
        return PageLanguagesFactory::new();
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'page_id');
    }
}
