<?php

namespace App\Services;

class UploadLimits
{
    public static function bytes(): ?int
    {
        return self::fromIniValues(ini_get('upload_max_filesize'), ini_get('post_max_size'));
    }

    public static function fromIniValues(string $uploadMaxFilesize, string $postMaxSize): ?int
    {
        $limits = array_filter([
            ini_parse_quantity($uploadMaxFilesize),
            ini_parse_quantity($postMaxSize),
        ], fn (int $limit): bool => $limit > 0);

        return $limits === [] ? null : min($limits);
    }

    /** @return list<string> */
    public static function rules(): array
    {
        $bytes = self::bytes();

        return $bytes === null ? [] : ['max:'.($bytes / 1024)];
    }

    public static function label(): string
    {
        $bytes = self::bytes();
        if ($bytes === null) {
            return __('validation.unlimited');
        }

        foreach (['ГБ' => 1024 ** 3, 'МБ' => 1024 ** 2, 'КБ' => 1024] as $unit => $divisor) {
            if ($bytes >= $divisor) {
                return rtrim(rtrim(number_format($bytes / $divisor, 2, '.', ''), '0'), '.').' '.$unit;
            }
        }

        return $bytes.' Б';
    }
}
