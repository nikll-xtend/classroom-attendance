<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Classroom;


class ClassroomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classrooms = [
            ['name' => 'Class 1A'],
            ['name' => 'Class 2B'],
            ['name' => 'Class 3C'],
            ['name' => 'Class 4D'],
        ];
        foreach ($classrooms as $classroom) {
            Classroom::create($classroom);
        }
    }
}
