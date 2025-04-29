<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\Classroom;

class BulkAttendanceTest extends TestCase
{
    use RefreshDatabase;
    public function test_bulk_mark_attendance_success()
    {
        $user = User::factory()->create(['role' => 'teacher']);
        $classroom = Classroom::factory()->create();
        $students = Student::factory(3)->create();

        $data = [
            'attendance' => $students->map(function ($student) use ($classroom) {
                return [
                    'classroom_id' => $classroom->id,
                    'student_id' => $student->id,
                    'status' => 'present',
                    'marked_by' => $classroom->id, 
                    'remarks' => 'On time',
                ];
            })->toArray(),
        ];

        $response = $this->actingAs($user)->postJson('/api/attendance/bulk', $data);
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Bulk attendance marked successfully']);
    }

    public function test_bulk_mark_attendance_validation_error()
    {
        $user = User::factory()->create(['role' => 'teacher']);
        $data = [
            'attendance' => [
                [
                    'classroom_id' => 'invalid',
                    'student_id' => 'invalid',
                    'status' => 'invalid',
                ],
            ],
        ];
        $response = $this->actingAs($user)->postJson('/api/attendance/bulk', $data);
        $response->assertStatus(400);
    }
}
