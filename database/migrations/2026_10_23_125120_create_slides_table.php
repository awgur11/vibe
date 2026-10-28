<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slides', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('subtitle')->nullable();
            $table->string('preview')->nullable();
            $table->string('link', 2048)->nullable();
            $table->unsignedInteger('position')->default(0)->index();
            $table->boolean('public')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slides');
    }
};
