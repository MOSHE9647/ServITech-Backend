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
        // Generate Tecnologia Category
        $tecnologiaCategory = Category::factory()->create([
            'name' => 'Tecnologia',
            'description' => 'tecnologia',
        ]);

        // Generate Anime Category
        $animeCategory = Category::factory()->create([
            'name' => 'Anime',
            'description' => 'anime',
        ]);

        $tecnologiaSubcategory = Subcategory::factory()->create([
            'name' => 'Gadgets',
            'description' => 'gadgets',
            'category_id' => $tecnologiaCategory->id, // Link subcategory to the Tecnologia category
        ]);

        $animeSubcategory = Subcategory::factory()->create([
            'name' => 'Manga',
            'description' => 'manga',
            'category_id' => $animeCategory->id, // Link subcategory to the Anime category
        ]);

        $article = Article::factory()->create([
            'category_id' => $tecnologiaCategory->id, // Link article to the selected category
            'subcategory_id' => $tecnologiaSubcategory->id, // Link article to the selected subcategory
        ]);
        $article->images()->createMany(
            Image::factory(rand(1, 5))->make()->toArray()
        );

        $article = Article::factory()->create([
            'category_id' => $animeCategory->id, // Link article to the selected category
            'subcategory_id' => $animeSubcategory->id, // Link article to the selected subcategory
        ]);
        $article->images()->createMany(
            Image::factory(rand(1, 5))->make()->toArray()
        );
    }
}
