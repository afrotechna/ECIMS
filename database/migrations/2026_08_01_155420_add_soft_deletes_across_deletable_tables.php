<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private array $tables = [
        'programmes',
        'programme_nta_level_documents',
        'courses',
        'semesters',
        'fee_structures',
        'exam_slots',
        'timetable_slots',
        'clinical_rotation_rounds',
        'exam_papers',
        'payment_instalments',
        'users',
        'student_documents',
        'certificate_collections',
        'announcements',
        'hostels',
        'rooms',
        'inventory_items',
        'user_module_permissions',
        'calendar_events',
        'institution_documents',
        'students',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->softDeletes();
                $blueprint->foreignId('deleted_by')->nullable()->after('deleted_at')->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->dropConstrainedForeignId('deleted_by');
                $blueprint->dropSoftDeletes();
            });
        }
    }
};
