<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advs', function (Blueprint $table): void {
            $table->id();
            $table->string('icon');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('public')->default(false);
            $table->timestamps();
            $table->index(['public', 'position']);
        });
        Schema::create('advs_languages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('adv_id')->constrained('advs')->cascadeOnDelete();
            $table->string('lacale');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unique(['adv_id', 'lacale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advs_languages');
        Schema::dropIfExists('advs');
    }
};
