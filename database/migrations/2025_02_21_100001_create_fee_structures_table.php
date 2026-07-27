<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('academic_year');
            $table->foreignId('programme_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('tuition', 12, 0)->default(0);
            $table->decimal('nhif', 10, 0)->default(0);
            $table->decimal('nactvet_qa', 10, 0)->default(0);
            $table->decimal('accommodation', 10, 0)->default(0);
            $table->decimal('other_charges', 10, 0)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['academic_year', 'programme_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_structures');
    }
};
