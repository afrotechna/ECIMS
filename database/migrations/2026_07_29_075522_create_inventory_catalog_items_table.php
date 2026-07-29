<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_catalog_items', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        if (Schema::hasTable('inventory_items')) {
            $now = now();
            DB::table('inventory_items')
                ->whereNotNull('name')
                ->distinct()
                ->pluck('name')
                ->each(function (string $name) use ($now) {
                    DB::table('inventory_catalog_items')->insertOrIgnore([
                        'name' => $name,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_catalog_items');
    }
};
