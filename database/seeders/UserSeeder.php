<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin User',
            'email' => 'admin1@yopmail.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
        User::create([
            'name' => 'Teacher User',
            'email' => 'teacher1@yopmail.com',
            'password' => bcrypt('password'),
            'role' => 'teacher',
        ]);
        User::create([
            'name' => 'Teacher User2',
            'email' => 'teacher2@yopmail.com',
            'password' => bcrypt('password'),
            'role' => 'teacher',
        ]);
        User::create([
            'name' => 'Teacher User3',
            'email' => 'teacher3@yopmail.com',
            'password' => bcrypt('password'),
            'role' => 'teacher',
        ]);
        User::create([
            'name' => 'Teacher User4',
            'email' => 'teacher4@yopmail.com',
            'password' => bcrypt('password'),
            'role' => 'teacher',
        ]);
        User::create([
            'name' => 'Teacher User5',
            'email' => 'teacher5@yopmail.com',
            'password' => bcrypt('password'),
            'role' => 'teacher',
        ]);
    }
}
