<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hostels', function (Blueprint $table) {
            $table->unsignedTinyInteger('block_count')->default(14)->after('code')->comment('Number of blocks in this hostel');
            $table->unsignedTinyInteger('rooms_per_block')->default(4)->after('block_count')->comment('Rooms per block');
            $table->unsignedTinyInteger('beds_per_room')->default(8)->after('rooms_per_block')->comment('Student berths: 4 double-decker beds = 8');
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->unsignedTinyInteger('block_number')->nullable()->after('hostel_id')->comment('1-based block index');
            $table->unsignedTinyInteger('room_in_block')->nullable()->after('block_number')->comment('1-based room within block');
            $table->unique(['hostel_id', 'block_number', 'room_in_block'], 'rooms_hostel_block_room_uq');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropUnique('rooms_hostel_block_room_uq');
            $table->dropColumn(['block_number', 'room_in_block']);
        });

        Schema::table('hostels', function (Blueprint $table) {
            $table->dropColumn(['block_count', 'rooms_per_block', 'beds_per_room']);
        });
    }
};
