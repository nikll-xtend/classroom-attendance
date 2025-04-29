<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Student;
use App\Models\Classroom;

class AttendanceStoreTest extends TestCase
{
    use RefreshDatabase;


    public function test_store_attendance_success()
    {
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create([
            'classroom_id' => $classroom->id,
        ]);
        $user = User::factory()->create(['role' => 'teacher']);

        $this->mock(\App\Services\Contracts\AbilityCheckerInterface::class, function ($mock) {
            $mock->shouldReceive('can')
                ->with('mark-attendance')
                ->andReturn(true);
        });
        $this->actingAs($user);

        Redis::shouldReceive('get')
            ->with("student:{$student->id}:classroom")
            ->andReturn($classroom->id);

        Redis::shouldReceive('get')
            ->with("attendance:{$classroom->id}:{$student->id}:" . today()->toDateString())
            ->andReturn(null);

        Redis::shouldReceive('setex')
            ->with("attendance:{$classroom->id}:{$student->id}:" . today()->toDateString(), 86400, 'marked')
            ->andReturn(true);




        $response = $this->postJson('/api/attendance', [
            'classroom_id' => $classroom->id,
            'student_id'   => $student->id,
            'status'       => 'present',
            'remarks'      => 'On time',
        ]);
        $response->assertStatus(201);
        $response->assertJson([
            'message' => 'Attendance marked successfully',
        ]);
        $this->assertDatabaseHas('attendances', [
            'classroom_id' => $classroom->id,
            'student_id'   => $student->id,
            'status'       => 'present',
        ]);
    }

    public function test_store_attendance_unauthorized_user()
    {
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create([
            'classroom_id' => $classroom->id,
        ]);
        $user = User::factory()->create();

        $this->mock(\App\Services\Contracts\AbilityCheckerInterface::class, function ($mock) {
            $mock->shouldReceive('can')
                ->with('mark-attendance')
                ->andReturn(false);
        });

        $this->actingAs($user);

        $response = $this->postJson('/api/attendance', [
            'classroom_id' => $classroom->id,
            'student_id'   => $student->id,
            'status'       => 'present',
            'remarks'      => 'On time',
        ]);

        $response->assertStatus(403);
        $response->assertJson(['message' => 'Unauthorized']);
    }

    public function test_store_attendance_validation_error()
    {
        $user = User::factory()->create(['role' => 'teacher']);
        $data = [
            'classroom_id' => 'invalid',
            'student_id' => 'invalid',
            'status' => 'invalid',
            'remarks' => '',
        ];

        $response = $this->actingAs($user)->postJson('/api/attendance', $data);
        $response->assertStatus(400);
        $response->assertJsonStructure([
            'message',
            'errors'
        ]);
    }
}
