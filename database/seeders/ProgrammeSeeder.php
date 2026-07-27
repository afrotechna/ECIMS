<?php

namespace Database\Seeders;

use App\Models\Programme;
use Illuminate\Database\Seeder;

class ProgrammeSeeder extends Seeder
{
    public function run(): void
    {
        $programmes = [
            ['name' => 'Clinical Medicine', 'code' => 'CMT', 'level' => 'Ordinary Diploma', 'duration_years' => 3],
            ['name' => 'Medical Laboratory Science', 'code' => 'MLT', 'level' => 'Ordinary Diploma', 'duration_years' => 3],
        ];

        foreach ($programmes as $p) {
            Programme::firstOrCreate(['code' => $p['code']], $p);
        }
    }
}
