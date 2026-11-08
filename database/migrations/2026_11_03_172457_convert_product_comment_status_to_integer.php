<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('product_comments')->update([
            'status' => DB::raw("CASE status WHEN 'pending' THEN '0' WHEN 'approved' THEN '1' WHEN 'rejected' THEN '2' ELSE status END"),
        ]);

        Schema::table('product_comments', function (Blueprint $table): void {
            $table->unsignedTinyInteger('status')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('product_comments', function (Blueprint $table): void {
            $table->string('status', 20)->default('pending')->change();
        });

        DB::table('product_comments')->update([
            'status' => DB::raw("CASE status WHEN '0' THEN 'pending' WHEN '1' THEN 'approved' WHEN '2' THEN 'rejected' ELSE status END"),
        ]);
    }
};
