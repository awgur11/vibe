<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalogs_languages', function (Blueprint $table): void {
            $table->string('tag_title')->nullable();
            $table->text('tag_description')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('catalogs_languages', function (Blueprint $table): void {
            $table->dropColumn(['tag_title', 'tag_description']);
        });
    }
};
