<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_banks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('question_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_bank_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('material_type')->default('notes');
            $table->string('file_path')->nullable();
            $table->longText('text_content')->nullable();
            $table->timestamps();
        });

        Schema::create('question_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_bank_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('question_material_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['mcq', 'multi_true_false', 'matching', 'short_answer', 'essay']);
            $table->string('difficulty')->default('medium');
            $table->longText('stem');
            $table->json('options')->nullable();
            $table->json('answer_key')->nullable();
            $table->json('rubric')->nullable();
            $table->decimal('marks', 6, 2)->default(1);
            $table->boolean('is_ai_generated')->default(false);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('exam_papers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_bank_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->decimal('total_marks', 8, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('exam_paper_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_paper_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_item_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('question_order');
            $table->decimal('marks', 6, 2)->default(1);
            $table->timestamps();

            $table->unique(['exam_paper_id', 'question_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_paper_items');
        Schema::dropIfExists('exam_papers');
        Schema::dropIfExists('question_items');
        Schema::dropIfExists('question_materials');
        Schema::dropIfExists('question_banks');
    }
};
