<?php

use Illuminate\Support\Str;

function x_slag(string $title): string
{
    $transliterated = strtr(mb_strtolower($title), [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'h', 'ґ' => 'g',
        'д' => 'd', 'е' => 'e', 'є' => 'ye', 'ж' => 'zh', 'з' => 'z',
        'и' => 'y', 'і' => 'i', 'ї' => 'yi', 'й' => 'y', 'к' => 'k',
        'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p',
        'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f',
        'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch',
        'ь' => '', 'ю' => 'yu', 'я' => 'ya', "'" => '', '’' => '', 'ʼ' => '',
    ]);

    return Str::slug($transliterated);
}

function setting(string $key, mixed $default = null, ?string $locale = null): mixed
{
    return app(\App\Services\SettingsService::class)->get($key, $default, $locale);
}
