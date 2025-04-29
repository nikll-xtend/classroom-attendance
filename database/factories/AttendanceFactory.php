<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Student;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'classroom_id' => Classroom::factory(),
            'student_id'   => Student::factory(),
            'status'       => $this->faker->randomElement(['present', 'absent']),
            'remarks'      => $this->faker->sentence(),
            'marked_by'    => 1,
            'marked_at'    => now(),
        ];
    }
}
