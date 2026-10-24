<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

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
            'email_verified_at' => now(),
        ]);
    }
}
