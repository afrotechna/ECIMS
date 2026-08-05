<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('qualification', 100)->nullable()->after('phone');
            $table->string('education_level', 60)->nullable()->after('qualification');
            $table->string('license_number', 100)->nullable()->after('education_level');
            $table->string('license_board', 150)->nullable()->after('license_number');
            $table->string('employment_type', 30)->nullable()->after('license_board');
        });

        Schema::create('programme_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('programme_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'programme_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programme_user');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['qualification', 'education_level', 'license_number', 'license_board', 'employment_type']);
        });
    }
};
