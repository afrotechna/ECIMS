<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creditors', function (Blueprint $table) {
            $table->id();
            $table->string('category', 30);
            $table->string('payee_name');
            $table->text('description')->nullable();
            $table->decimal('amount_due', 14, 2);
            $table->date('date_incurred');
            $table->date('due_date')->nullable();
            $table->string('verification_status', 30)->default('not_yet_verified');
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['category']);
            $table->index(['verification_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creditors');
    }
};
