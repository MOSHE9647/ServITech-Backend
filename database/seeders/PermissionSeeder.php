<?php

namespace Database\Seeders;

use App\Enums\UserRoles;
use File;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all the model files
        $modelsPath = app_path('Models');
        $modelFiles = File::allFiles($modelsPath);

        // Define the permissions
        $permissions = ['create', 'read', 'update', 'delete'];

        // Create a list with all the permissions and assign them to the admin role
        $adminPermissions = [];
        foreach ($modelFiles as $file) {
            $modelName = pathinfo($file, PATHINFO_FILENAME); // Get the model name
            $modelNameLower = strtolower($modelName);             // Convert the model name to lowercase
            $modelNamePlural = Str::plural($modelNameLower);       // Get the plural form of the model name

            // Create the permissions for the model
            foreach ($permissions as $permission) {
                // Create the permission for the admin role
                $permission = Permission::create(['name' => "{$permission} {$modelNamePlural}"]);
                $adminPermissions[] = $permission; // Store the permission in the array
            }
        }

        // Assign the permissions to the admin role
        $adminRole = Role::where('name',UserRoles::ADMIN)->first();
        $adminRole->syncPermissions($adminPermissions);
    }
}
