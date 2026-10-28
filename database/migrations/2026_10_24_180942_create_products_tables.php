<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('catalog_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slag')->unique();
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('discount', 5, 2)->default(0);
            $table->decimal('final_price', 12, 2)->default(0);
            $table->timestamps();
        });
        Schema::create('products_languages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('lacale');
            $table->string('title')->nullable();
            $table->longText('description')->nullable();
            $table->string('tag_title')->nullable();
            $table->text('tag_description')->nullable();
            $table->unique(['product_id', 'lacale']);
        });
        Schema::create('fvalue_product', function (Blueprint $table): void {
            $table->foreignId('fvalue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['fvalue_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fvalue_product');
        Schema::dropIfExists('products_languages');
        Schema::dropIfExists('products');
    }
};
