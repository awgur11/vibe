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
        DB::table('languages')->orderBy('id')->each(function (object $language): void {
            if (! preg_match('/^\p{L}{3}$/u', $language->short)) {
                DB::table('languages')->where('id', $language->id)->update([
                    'short' => Language::shortForLocale($language->locale),
                ]);
            }
        });

        Schema::table('languages', function (Blueprint $table): void {
            $table->string('short', 3)->change();
        });
    }

    public function down(): void
    {
        Schema::table('languages', function (Blueprint $table): void {
            $table->string('short', 16)->change();
        });
    }
};
