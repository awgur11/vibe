<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalogs', function (Blueprint $table): void {
            $table->string('icon', 100)->nullable();
        });

        $icons = [
            'avtoaksessuary' => 'accessories', 'zapchasti' => 'parts', 'avtohimiya' => 'chemicals',
            'masla' => 'oil', 'instrument' => 'tools', 'akkumulyatory' => 'battery',
            'stekloochistiteli' => 'wipers', 'diski' => 'wheel', 'shiny' => 'tire',
            'krepezh-koles' => 'fasteners',
        ];
        DB::table('catalogs')->whereNull('parent_id')->update(['icon' => 'catalog-icon catalog-icon-box']);
        foreach ($icons as $slug => $icon) {
            DB::table('catalogs')->whereNull('parent_id')->where('slag', $slug)->update(['icon' => 'catalog-icon catalog-icon-'.$icon]);
        }
    }

    public function down(): void
    {
        Schema::table('catalogs', function (Blueprint $table): void {
            $table->dropColumn('icon');
        });
    }
};
