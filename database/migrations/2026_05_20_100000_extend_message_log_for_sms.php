<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('message_log', function (Blueprint $table) {
      $table->foreignId('semester_id')->nullable()->after('student_id')->constrained()->nullOnDelete();
      $table->string('recipient_role', 20)->nullable()->after('recipient');
      $table->string('template', 50)->nullable()->after('subject');
      $table->string('external_id', 64)->nullable()->after('status');
      $table->text('error_message')->nullable()->after('external_id');
    });
  }

  public function down(): void
  {
    Schema::table('message_log', function (Blueprint $table) {
      $table->dropConstrainedForeignId('semester_id');
      $table->dropColumn(['recipient_role', 'template', 'external_id', 'error_message']);
    });
  }
};
