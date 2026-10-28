<?php

namespace Database\Seeders;

use App\Models\Adv;
use App\Models\Language;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdvSeeder extends Seeder
{
    public function run(): void
    {
        $baseLocale = Language::ensureBase()->locale;
        $records = [
            ['i-truck', [
                'ru' => ['Быстрая доставка', 'Отправка по всей Украине удобной службой доставки'],
                'uk' => ['Швидка доставка', 'Відправлення по всій Україні зручною службою доставки'],
            ]],
            ['i-box', [
                'ru' => ['Большой выбор', 'Запчасти и аксессуары для разных марок автомобилей'],
                'uk' => ['Великий вибір', 'Запчастини та аксесуари для різних марок автомобілів'],
            ]],
            ['i-shield', [
                'ru' => ['Гарантия качества', 'Работаем с проверенными брендами и поставщиками'],
                'uk' => ['Гарантія якості', 'Працюємо з перевіреними брендами та постачальниками'],
            ]],
            ['i-headset', [
                'ru' => ['Помощь в подборе', 'Поможем найти подходящие детали по параметрам авто'],
                'uk' => ['Допомога у підборі', 'Допоможемо знайти відповідні деталі за параметрами авто'],
            ]],
        ];

        DB::transaction(function () use ($records, $baseLocale): void {
            foreach ($records as $position => [$icon, $translations]) {
                $adv = Adv::firstOrCreate(['icon' => $icon], ['position' => $position, 'public' => true]);
                $translations[$baseLocale] ??= $translations['ru'];
                foreach ($translations as $locale => [$title, $description]) {
                    $adv->translations()->firstOrCreate(['lacale' => $locale], compact('title', 'description'));
                }
            }
        });
    }
}
