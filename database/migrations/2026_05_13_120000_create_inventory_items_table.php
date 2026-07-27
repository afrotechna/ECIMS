<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('asset_tag', 64)->nullable()->unique()->comment('Institution asset / barcode number');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('category', 100)->default('General');
            $table->string('kind', 20)->default('asset')->comment('item = consumables/supplies; asset = capital equipment');
            $table->unsignedInteger('quantity')->default(1);
            $table->string('unit', 32)->nullable();
            $table->string('location', 255)->nullable();
            $table->string('custodian', 150)->nullable()->comment('Person or office responsible');
            $table->date('acquired_on')->nullable();
            $table->decimal('cost', 14, 2)->nullable();
            $table->string('supplier', 255)->nullable();
            $table->string('serial_number', 128)->nullable();
            $table->string('condition', 32)->default('good');
            $table->string('status', 32)->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['kind', 'category']);
            $table->index('status');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
