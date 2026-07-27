<?php

namespace Tests\Feature;

use App\Models\LedgerEntry;
use App\Models\Programme;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ArrearsReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_arrears_report_only_includes_students_with_positive_balances(): void
    {
        $bursar = User::create([
            'name' => 'Bursar User',
            'email' => 'bursar@example.com',
            'role' => 'account_bursar',
            'password' => Hash::make('Password123!'),
            'must_change_password' => false,
            'profile_completed_at' => now(),
        ]);

        $programme = Programme::create([
            'name' => 'Clinical Medicine',
            'code' => 'CM01',
            'level' => 'Ordinary Diploma',
            'duration_years' => 3,
            'is_active' => true,
        ]);

        $withArrears = Student::create([
            'reg_no' => 'REG100',
            'nactvet_reg_no' => 'NACTVET100',
            'first_name' => 'Alpha',
            'last_name' => 'Student',
            'email' => 'alpha@example.com',
            'programme_id' => $programme->id,
            'intake_year' => (int) date('Y'),
            'status' => 'active',
        ]);

        $withoutArrears = Student::create([
            'reg_no' => 'REG200',
            'nactvet_reg_no' => 'NACTVET200',
            'first_name' => 'Beta',
            'last_name' => 'Student',
            'email' => 'beta@example.com',
            'programme_id' => $programme->id,
            'intake_year' => (int) date('Y'),
            'status' => 'active',
        ]);

        LedgerEntry::create([
            'student_id' => $withArrears->id,
            'type' => 'debit',
            'amount' => 700000,
        ]);
        LedgerEntry::create([
            'student_id' => $withArrears->id,
            'type' => 'credit',
            'amount' => 200000,
        ]);

        LedgerEntry::create([
            'student_id' => $withoutArrears->id,
            'type' => 'debit',
            'amount' => 400000,
        ]);
        LedgerEntry::create([
            'student_id' => $withoutArrears->id,
            'type' => 'credit',
            'amount' => 450000,
        ]);

        $response = $this->actingAs($bursar)->get(route('reports.arrears'));

        $response->assertOk();
        $response->assertSee('REG100');
        $response->assertDontSee('REG200');
    }
}
