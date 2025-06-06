<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Category;
use App\Models\Image;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Generate categories and their associated subcategories
        Category::factory(5)->create()->each(function ($category) {
            Subcategory::factory(10)->create([
                'category_id' => $category->id, // Link subcategories to the parent category
            ]);
        });

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

        $article = Article::factory()->create([
            'category_id' => $category->id, // Link article to the selected category
            'subcategory_id' => $subcategory->id, // Link article to the selected subcategory
        ]);
        $article->images()->createMany(
            Image::factory(3)->make()->toArray() // Create 3 images for the article
        );
    }
}
