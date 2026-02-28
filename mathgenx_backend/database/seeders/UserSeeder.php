<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate([
            'name' => 'Admin',
            'email' => 'admin@mathgenx.test',
            'password' => bcrypt('password'),
        ])->assignRole('admin');

        User::firstOrCreate([
            'name' => 'Educator',
            'email' => 'educator@mathgenx.test',
            'password' => bcrypt('password'),
        ])->assignRole('educator');

        User::firstOrCreate([
            'name' => 'Learner',
            'email' => 'learner@mathgenx.test',
            'password' => bcrypt('password'),
        ])->assignRole('learner');
    }
}
