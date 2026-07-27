<?php

namespace Database\Seeders;

use App\Models\Semester;
use Illuminate\Database\Seeder;

class SemesterSeeder extends Seeder
{
    public function run(): void
    {
        $year = (int) date('Y');
        foreach ([1, 2] as $num) {
            Semester::firstOrCreate(
                ['academic_year' => $year, 'number' => $num],
                ['name' => Semester::nameForPeriod($num)]
            );
        }
    }
}
