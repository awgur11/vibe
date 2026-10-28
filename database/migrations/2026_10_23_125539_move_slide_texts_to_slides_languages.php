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
        Schema::create('slides_languages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('slide_id')->constrained('slides')->cascadeOnDelete();
            $table->string('lacale');
            $table->string('title')->nullable();
            $table->text('subtitle')->nullable();
            $table->unique(['slide_id', 'lacale']);
        });

        DB::table('slides')->orderBy('id')->chunkById(100, function (Collection $slides): void {
            foreach ($slides as $slide) {
                DB::table('slides_languages')->insert([
                    'slide_id' => $slide->id,
                    'lacale' => config('app.locale'),
                    'title' => $slide->title,
                    'subtitle' => $slide->subtitle,
                ]);
            }
        });

        Schema::table('slides', function (Blueprint $table): void {
            $table->dropColumn(['title', 'subtitle']);
        });
    }

    public function down(): void
    {
        Schema::table('slides', function (Blueprint $table): void {
            $table->string('title')->default('');
            $table->text('subtitle')->nullable();
        });

        DB::table('slides_languages')->where('lacale', config('app.locale'))->orderBy('id')->chunkById(100, function (Collection $translations): void {
            foreach ($translations as $translation) {
                DB::table('slides')->where('id', $translation->slide_id)->update([
                    'title' => $translation->title ?? '',
                    'subtitle' => $translation->subtitle,
                ]);
            }
        });

        Schema::dropIfExists('slides_languages');
    }
};
