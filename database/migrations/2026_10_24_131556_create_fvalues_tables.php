<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fvalues', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('filter_id')->constrained()->cascadeOnDelete();
            $table->string('slag');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->unique(['filter_id', 'slag']);
            $table->index(['filter_id', 'position']);
        });
        Schema::create('fvalues_languages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('fvalue_id')->constrained()->cascadeOnDelete();
            $table->string('lacale');
            $table->string('title')->nullable();
            $table->unique(['fvalue_id', 'lacale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fvalues_languages');
        Schema::dropIfExists('fvalues');
    }
};
