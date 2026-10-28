<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('public')->default(false);
            $table->timestamps();
            $table->index(['public', 'position']);
        });
        Schema::create('brands_languages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->string('lacale');
            $table->string('title');
            $table->unique(['brand_id', 'lacale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands_languages');
        Schema::dropIfExists('brands');
    }
};
