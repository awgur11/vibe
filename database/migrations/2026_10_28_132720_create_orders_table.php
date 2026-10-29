<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->uuid('client_id')->index();
            $table->string('customer_type', 20);
            $table->string('name');
            $table->string('phone', 40);
            $table->string('email', 254)->nullable();
            $table->string('company_name')->nullable();
            $table->text('legal_address')->nullable();
            $table->string('tax_id', 32)->nullable();
            $table->string('kpp', 32)->nullable();
            $table->string('payment_method', 32);
            $table->decimal('total', 18, 2);
            $table->string('status', 20)->default('new');
            $table->timestamps();
        });
        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('articule')->nullable();
            $table->unsignedSmallInteger('quantity');
            $table->decimal('price', 12, 2);
            $table->decimal('total', 18, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
