<?php

namespace Database\Seeders;

use App\Enums\UserRoles;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtain all the roles from the UserRoles enum
        // and create them in the database
        // using the Spatie Permission package
        $roles = UserRoles::cases();
        foreach ($roles as $role) {
            Role::create(['name' => $role]);
        }
    }
}
