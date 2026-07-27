<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->decimal('ca_test1', 5, 2)->nullable()->after('ca_mark');
            $table->decimal('ca_test2', 5, 2)->nullable()->after('ca_test1');
            $table->decimal('ca_assignment1', 5, 2)->nullable()->after('ca_test2');
            $table->decimal('ca_assignment2', 5, 2)->nullable()->after('ca_assignment1');
            $table->decimal('ca_practical', 5, 2)->nullable()->after('ca_assignment2');
        });

        Schema::table('courses', function (Blueprint $table) {
            if (!Schema::hasColumn('courses', 'has_practical')) {
                $table->boolean('has_practical')->default(false)->after('exam_weight');
            }
        });
    }

    public function down(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->dropColumn(['ca_test1', 'ca_test2', 'ca_assignment1', 'ca_assignment2', 'ca_practical']);
        });
        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'has_practical')) {
                $table->dropColumn('has_practical');
            }
        });
    }
};
