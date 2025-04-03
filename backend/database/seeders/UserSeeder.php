<?php

namespace Database\Seeders;

use App\Enums\UserRoles;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
        ]);

        // Create a regular user with predefined credentials
        User::factory()->withRole(UserRoles::USER->value)->create([
            "name" => "Example",
            "last_name" => "Example Example",
            'email' => 'example@example.com',
            'password'=> bcrypt('password'),
        ]);

        // Create an admin user with predefined credentials
        User::factory()->withRole(UserRoles::ADMIN->value)->create([
            'name' => 'Administrator',
            'email' => 'admin@admin.com',
            'password'=> bcrypt(env('ADMIN_PASSWORD', 'admin1234')), // Use environment variable or default password
        ]);
    }
}
