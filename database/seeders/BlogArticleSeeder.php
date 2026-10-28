<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BlogArticleSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            Language::ensureBase();
            $language = Language::firstOrCreate(['locale' => 'uk'], ['short' => Language::shortForLocale('uk'), 'title' => 'Українська', 'active' => true]);
            foreach ($this->articles() as $article) {
                $page = Page::firstOrCreate(
                    ['type' => 'articles', 'slag' => $article['slag']],
                    ['public' => true, 'is_menu' => false, 'position' => 0],
                );
                $page->translations()->firstOrCreate(
                    ['lacale' => $language->locale],
                    ['title' => $article['title'], 'content' => $article['content'], 'short_content' => $article['content'], 'tag_title' => $article['title']],
                );
            }
        });
    }

    /** @return list<array{slag: string, title: string, content: string}> */
    private function articles(): array
    {
        return [
            ['slag' => 'yak-obraty-filtry-dlia-avto', 'title' => 'Як обрати фільтри для авто', 'content' => 'Підбирайте фільтри за VIN-кодом, моделлю та роком випуску авто. Перед купівлею звірте номер деталі й розміри з каталогом виробника. Якісний повітряний фільтр затримує пил, а оливний допомагає очищувати мастило. Інтервал заміни перевіряйте в сервісній книжці та враховуйте умови щоденної експлуатації.'],
            ['slag' => 'halmivni-kolodky-shcho-vrakhuvaty', 'title' => 'Гальмівні колодки: що врахувати', 'content' => 'Гальмівні колодки потрібно підбирати для модифікації автомобіля. Зіставте каталожний номер, форму та розміри деталей. Скрип, вібрація чи збільшення гальмівного шляху є приводом для огляду системи. Разом із колодками перевірте стан дисків і супортів. Для заміни звертайтеся до кваліфікованого майстра.'],
        ];
    }
}
