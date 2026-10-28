<?php

use App\Models\Language;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table): void {
            $table->id();
            $table->string('locale', 35)->unique();
            $table->string('short', 16);
            $table->string('title');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        $locale = config('app.locale');
        DB::table('languages')->insert([
            'locale' => $locale,
            'short' => Language::shortForLocale($locale),
            'title' => Locale::getDisplayName($locale, app()->getLocale()) ?: $locale,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
