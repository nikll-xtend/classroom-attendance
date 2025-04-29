<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\Classroom;

class AttendanceUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_attendance_success()
    {
        $user = User::factory()->create(['role' => 'teacher']);
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
            'status' => 'present',
            'remarks' => 'On time',
            'marked_by' => $user->id
        ]);

        $data = [
            'status' => 'absent',
            'remarks' => 'Sick leave',
        ];

        $response = $this->actingAs($user)->putJson("/api/attendance/{$attendance->id}", $data);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Attendance updated successfully']);
        $attendance->refresh();
        $this->assertEquals('absent', $attendance->status);
    }

    public function test_update_attendance_unauthorized_user()
    {
        $creator = User::factory()->create(['role' => 'teacher']);
        $otherUser = User::factory()->create(['role' => 'teacher']);
        $attendance = Attendance::factory()->create(['marked_by' => $creator->id]);
        $data = [
            'status' => 'absent',
            'remarks' => 'Sick leave',
        ];
        $response = $this->actingAs($otherUser)->putJson("/api/attendance/{$attendance->id}", $data);
        $response->assertStatus(403);
    }
}
