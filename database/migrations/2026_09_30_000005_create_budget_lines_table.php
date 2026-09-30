<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('financial_year', 9);
            $table->string('item_description');
            $table->decimal('annual_budget', 14, 2)->default(0);
            $table->timestamps();

            $table->index(['financial_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_lines');
    }
};
