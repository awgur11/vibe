<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('catalogs')->nullOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->string('preview')->nullable();
            $table->string('slag')->unique();
            $table->boolean('public')->default(false);
            $table->timestamps();
            $table->index(['parent_id', 'position']);
        });
        Schema::create('catalogs_languages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('catalog_id')->constrained('catalogs')->cascadeOnDelete();
            $table->string('lacale');
            $table->string('title')->nullable();
            $table->text('short_content')->nullable();
            $table->longText('content')->nullable();
            $table->unique(['catalog_id', 'lacale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogs_languages');
        Schema::dropIfExists('catalogs');
    }
};
