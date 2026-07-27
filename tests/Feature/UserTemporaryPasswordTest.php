<?php

namespace Tests\Feature;

use App\Models\Programme;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTemporaryPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_student_user_with_non_predictable_temporary_password(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'password' => Hash::make('Password123!'),
            'must_change_password' => false,
            'profile_completed_at' => now(),
        ]);

        $programme = Programme::create([
            'name' => 'Diploma in Nursing',
            'code' => 'DN01',
            'level' => 'Ordinary Diploma',
            'duration_years' => 3,
            'is_active' => true,
        ]);

        $student = Student::create([
            'reg_no' => 'REG001',
            'nactvet_reg_no' => 'NACTVET001',
            'first_name' => 'Jane',
            'last_name' => 'Mushi',
            'email' => 'jane@example.com',
            'programme_id' => $programme->id,
            'intake_year' => (int) date('Y'),
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'role' => 'student',
            'student_id' => $student->id,
        ]);

        $response->assertRedirect(route('users.index'));

        $createdUser = User::where('email', $student->email)->first();
        $this->assertNotNull($createdUser);
        $this->assertTrue($createdUser->must_change_password);
        $this->assertFalse(Hash::check($student->last_name, $createdUser->password));
    }
}
