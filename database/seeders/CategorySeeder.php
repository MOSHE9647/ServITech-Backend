<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'anime', 'description' => 'anime'],
            ['name' => 'tecnologia', 'description' => 'tecnologia'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
