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
        User::factory()->create([
            'name' => 'Root User',
            'email' => 'root@example.com',
            'password' => bcrypt('Abc.123*'),
            'email_verified_at' => now()
        ]);
    }
}
