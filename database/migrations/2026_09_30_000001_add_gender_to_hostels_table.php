<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Splits the single seeded "Hostel" row (14 blocks, no gender) into a Male Hostel
 * (blocks 1-6, unchanged numbering) and a Female Hostel (old blocks 7-14, renumbered
 * 1-8) so allocation can be restricted to a student's own gender. Only 6 blocks per
 * gender exist on the real campus, so the two renumbered overflow blocks (old 13-14 ->
 * new 7-8) are marked inactive rather than deleted, preserving any existing room/
 * allocation foreign keys.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hostels', function (Blueprint $table) {
            $table->string('gender', 10)->nullable()->after('code');
        });

        $male = DB::table('hostels')->orderBy('id')->first();

        if ($male) {
            DB::table('hostels')->where('id', $male->id)->update([
                'name' => 'Male Hostel',
                'gender' => 'male',
                'block_count' => 6,
                'updated_at' => now(),
            ]);

            $femaleId = DB::table('hostels')->insertGetId([
                'name' => 'Female Hostel',
                'code' => null,
                'gender' => 'female',
                'block_count' => 6,
                'rooms_per_block' => $male->rooms_per_block,
                'beds_per_room' => $male->beds_per_room,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $movedRooms = DB::table('rooms')
                ->where('hostel_id', $male->id)
                ->where('block_number', '>', 6)
                ->get();

            foreach ($movedRooms as $room) {
                $newBlock = $room->block_number - 6;
                DB::table('rooms')->where('id', $room->id)->update([
                    'hostel_id' => $femaleId,
                    'block_number' => $newBlock,
                    'name' => \App\Models\Room::generatedCode($newBlock, (int) $room->room_in_block),
                    'is_active' => $newBlock > 6 ? false : $room->is_active,
                    'updated_at' => now(),
                ]);
            }
        }

        Schema::table('hostels', function (Blueprint $table) {
            $table->string('gender', 10)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        $female = DB::table('hostels')->where('gender', 'female')->first();
        $male = DB::table('hostels')->where('gender', 'male')->first();

        if ($female && $male) {
            DB::table('rooms')->where('hostel_id', $female->id)->get()->each(function ($room) use ($male) {
                $newBlock = $room->block_number + 6;
                DB::table('rooms')->where('id', $room->id)->update([
                    'hostel_id' => $male->id,
                    'block_number' => $newBlock,
                    'name' => \App\Models\Room::generatedCode($newBlock, (int) $room->room_in_block),
                    'is_active' => true,
                    'updated_at' => now(),
                ]);
            });
            DB::table('hostels')->where('id', $female->id)->delete();
            DB::table('hostels')->where('id', $male->id)->update([
                'name' => 'Hostel',
                'block_count' => 14,
                'updated_at' => now(),
            ]);
        }

        Schema::table('hostels', function (Blueprint $table) {
            $table->dropColumn('gender');
        });
    }
};
