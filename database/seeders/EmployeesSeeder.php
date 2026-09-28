<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmployeesSeeder extends Seeder
{
    public function run(): void
    {
        $waiterUser = User::factory()->create([
            'name' => 'Juan Mesero',
            'email' => 'mesero@miralto.local',
            'username' => 'mesero',
            'password' => Hash::make('password'),
            'role' => 'waiter',
            'is_active' => true,
        ]);

        Employee::create([
            'name' => 'Juan Mesero',
            'position' => 'Mesero',
            'role' => 'waiter',
            'user_id' => $waiterUser->id,
            'is_active' => true,
        ]);

        $cookUser = User::factory()->create([
            'name' => 'Ana Cocinera',
            'email' => 'cocina@miralto.local',
            'username' => 'cocina',
            'password' => Hash::make('password'),
            'role' => 'cook',
            'is_active' => true,
        ]);

        Employee::create([
            'name' => 'Ana Cocinera',
            'position' => 'Cocinera',
            'role' => 'cook',
            'user_id' => $cookUser->id,
            'is_active' => true,
        ]);

        Employee::create([
            'name' => 'Pedro Auxiliar',
            'position' => 'Auxiliar de cocina',
            'role' => 'other',
            'is_active' => true,
        ]);
    }
}
