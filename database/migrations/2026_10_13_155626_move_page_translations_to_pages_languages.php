<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table): void {
            $table->string('type')->nullable();
            $table->string('slag')->nullable();
            $table->string('preview')->nullable();
            $table->string('icon')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('menu')->default(false);
        });

        Schema::create('pages_languages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->string('lacale', 35);
            $table->string('menu')->nullable();
            $table->string('title')->nullable();
            $table->longText('content')->nullable();
            $table->text('short_content')->nullable();
            $table->string('tag_title')->nullable();
            $table->text('tag_description')->nullable();
            $table->unique(['page_id', 'lacale']);
        });

        $locales = DB::table('languages')->pluck('locale', 'id');
        DB::table('pages')->orderBy('id')->chunkById(100, function (Collection $pages) use ($locales): void {
            foreach ($pages as $page) {
                $translations = json_decode($page->translations, true, 512, JSON_THROW_ON_ERROR);
                foreach ($translations as $languageId => $translation) {
                    DB::table('pages_languages')->insert([
                        'page_id' => $page->id,
                        'lacale' => $locales[$languageId] ?? (string) $languageId,
                        'menu' => $translation['menu'] ?? null,
                        'title' => $translation['title'] ?? null,
                        'content' => $translation['content'] ?? null,
                        'short_content' => $translation['short_content'] ?? null,
                        'tag_title' => $translation['tag_title'] ?? null,
                        'tag_description' => $translation['tag_description'] ?? null,
                    ]);
                }
            }
        });

        Schema::table('pages', function (Blueprint $table): void {
            $table->dropColumn('translations');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table): void {
            $table->json('translations')->nullable();
        });
        $languageIds = DB::table('languages')->pluck('id', 'locale');
        DB::table('pages')->orderBy('id')->chunkById(100, function (Collection $pages) use ($languageIds): void {
            foreach ($pages as $page) {
                $translations = [];
                foreach (DB::table('pages_languages')->where('page_id', $page->id)->get() as $translation) {
                    $translations[$languageIds[$translation->lacale] ?? $translation->lacale] = [
                        'menu' => $translation->menu,
                        'title' => $translation->title,
                        'content' => $translation->content,
                        'short_content' => $translation->short_content,
                        'tag_title' => $translation->tag_title,
                        'tag_description' => $translation->tag_description,
                    ];
                }
                DB::table('pages')->where('id', $page->id)->update(['translations' => json_encode($translations, JSON_THROW_ON_ERROR)]);
            }
        });
        Schema::dropIfExists('pages_languages');
        Schema::table('pages', function (Blueprint $table): void {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['type', 'slag', 'preview', 'icon', 'parent_id', 'category_id', 'position', 'menu']);
        });
    }
};
