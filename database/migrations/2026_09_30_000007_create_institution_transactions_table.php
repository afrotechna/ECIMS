<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_account_id')->constrained()->restrictOnDelete();
            $table->string('direction', 10);
            $table->decimal('amount', 14, 2);
            $table->date('txn_date');
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('budget_line_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('creditor_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('voided')->default(false);
            $table->string('void_reason')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['cash_account_id']);
            $table->index(['txn_date']);
            $table->index(['direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_transactions');
    }
};
