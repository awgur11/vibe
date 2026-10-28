<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('filters', function (Blueprint $table): void {
            $table->id();
            $table->string('slag')->unique();
            $table->unsignedInteger('position')->default(0)->index();
            $table->timestamps();
        });
        Schema::create('filters_languages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('filter_id')->constrained()->cascadeOnDelete();
            $table->string('lacale');
            $table->string('title')->nullable();
            $table->unique(['filter_id', 'lacale']);
        });
        Schema::create('catalog_filter', function (Blueprint $table): void {
            $table->foreignId('catalog_id')->constrained()->cascadeOnDelete();
            $table->foreignId('filter_id')->constrained()->cascadeOnDelete();
            $table->primary(['catalog_id', 'filter_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_filter');
        Schema::dropIfExists('filters_languages');
        Schema::dropIfExists('filters');
    }
};
