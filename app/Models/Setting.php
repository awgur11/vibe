<?php

namespace App\Models;

use App\Services\SettingsService;
use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    protected $fillable = ['key', 'value'];

    protected static function booted(): void
    {
        $invalidate = function (Setting $setting): void {
            app(SettingsService::class)->forget();
            if ($setting->getConnection()->transactionLevel() > 0) {
                $setting->getConnection()->afterCommit(fn () => app(SettingsService::class)->forget());
            }
        };

        static::saved($invalidate);
        static::deleted($invalidate);
    }
}
