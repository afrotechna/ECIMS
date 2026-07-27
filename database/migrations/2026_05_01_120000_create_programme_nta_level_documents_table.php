<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programme_nta_level_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programme_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('nta_level')->comment('4=first year, 5=second, 6=third');
            $table->string('document_type', 40);
            $table->string('file_path', 500);
            $table->string('original_name', 255)->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['programme_id', 'nta_level', 'document_type'], 'prog_nta_lvl_doc_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programme_nta_level_documents');
    }
};
