<?php

namespace Database\Seeders;

use App\Models\Admin\Category;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed the permissions and roles
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
        ]);

        // Create an admin user
        $adminUser = User::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin@admin.com',
            'password'=> bcrypt('admin1234'),
        ]);

        // Assign the admin role to the admin user
        $adminRole = Role::where('name','admin')->first();
        $adminUser->assignRole($adminRole);

        Category::factory(10)->create();
    }
}
