<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('graduation_clearances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('library_cleared', 10)->default('no'); // yes, no
            $table->string('finance_cleared', 10)->default('no');
            $table->string('accommodation_cleared', 10)->default('no');
            $table->string('academic_cleared', 10)->default('no');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('graduation_clearances');
    }
};
