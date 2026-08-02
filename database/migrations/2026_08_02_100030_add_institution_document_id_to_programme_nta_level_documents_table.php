<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('programme_nta_level_documents', function (Blueprint $table) {
            $table->foreignId('institution_document_id')->nullable()->after('uploaded_by')
                ->constrained('institution_documents')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('programme_nta_level_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('institution_document_id');
        });
    }
};
