<?php

namespace Database\Seeders;

use App\Enums\UserRoles;
use App\Models\Article;
use App\Models\Category;
use App\Models\Image;
use App\Models\RepairRequest;
use App\Models\Subcategory;
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
        // Seed the permissions and roles using the respective seeders
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
        ]);

        // Create an admin user with predefined credentials
        $adminUser = User::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin@admin.com',
            'password'=> bcrypt(env('ADMIN_PASSWORD', 'admin1234')), // Use environment variable or default password
        ]);

        // Assign the "Admin" role to the created admin user
        $adminRole = Role::where('name', UserRoles::ADMIN)->first();
        $adminUser->assignRole($adminRole);

        // Generate categories and their associated subcategories
        Category::factory(5)->create()->each(function ($category) {
            Subcategory::factory(10)->create([
                'category_id' => $category->id, // Link subcategories to the parent category
            ]);
        });

        // Create repair requests and attach related images
        // RepairRequest::factory(10)->create()->each(function ($repairRequest) {
        //     $repairRequest->images()->createMany(
        //         Image::factory(2)->make()->toArray() // Generate and attach 2 images per repair request
        //     );
        // });

        // Create an article and associate it with a random category and subcategory
        $category = Category::inRandomOrder()->first();
        if (!$category) {
            $this->command->error('No categories found. Please ensure categories are seeded.'); // Error if no categories exist
            return;
        }

        $subcategory = $category->subcategories()->inRandomOrder()->first();
        if (!$subcategory) {
            $this->command->error('No subcategories found for the selected category.'); // Error if no subcategories exist
            return;
        }

        Article::factory()->create([
            'category_id' => $category->id, // Link article to the selected category
            'subcategory_id' => $subcategory->id, // Link article to the selected subcategory
        ]);
    }
}
