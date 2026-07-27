<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10)->comment('debit or credit');
            $table->decimal('amount', 12, 0);
            $table->string('description')->nullable();
            $table->string('reference_type', 50)->nullable()->comment('e.g. payment, fee, refund');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('balance_after', 12, 0)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
