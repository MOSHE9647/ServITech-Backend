<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get categories and subcategories by name to find their IDs
        $tecnologiaCategory = Category::where('name', 'tecnologia')->first();
        $animeCategory = Category::where('name', 'anime')->first();

        // Get subcategories by name and category
        $subcategories = [
            'BATERÍA EXTERNA' => Subcategory::where('name', 'BATERÍA EXTERNA')->where('category_id', $tecnologiaCategory->id)->first(),
            'Cargador Celular' => Subcategory::where('name', 'Cargador Celular')->where('category_id', $tecnologiaCategory->id)->first(),
            'Periférico' => Subcategory::where('name', 'Periférico')->where('category_id', $tecnologiaCategory->id)->first(),
            'router' => Subcategory::where('name', 'router')->where('category_id', $tecnologiaCategory->id)->first(),
            'Peluche' => Subcategory::where('name', 'Peluche')->where('category_id', $animeCategory->id)->first(),
            'Poster' => Subcategory::where('name', 'Poster')->where('category_id', $animeCategory->id)->first(),
            'Figura' => Subcategory::where('name', 'Figura')->where('category_id', $animeCategory->id)->first(),
        ];

        $articles = [
            // Artículos de Tecnología
            [
                'category_id' => $tecnologiaCategory->id,
                'subcategory_id' => $subcategories['BATERÍA EXTERNA']->id,
                'name' => 'BATERÍA EXTERNA POWER BANK Editado',
                'description' => 'Batería para carga externa celular alto rendimiento, 10000MAH 20W HYPERGEAR',
                'price' => 13550,
            ],
            [
                'category_id' => $tecnologiaCategory->id,
                'subcategory_id' => $subcategories['BATERÍA EXTERNA']->id,
                'name' => 'BATERIA EXTERNA POWER BANK',
                'description' => 'Batería para carga externa celular alto rendimiento, 20000MAH 20W HYPERGEAR',
                'price' => 18900,
            ],
            [
                'category_id' => $tecnologiaCategory->id,
                'subcategory_id' => $subcategories['Cargador Celular']->id,
                'name' => 'CARGADOR TIPO C',
                'description' => 'A TIPO C 25W 2 ENTRADAS (USB/TIPO C) HYPERGEAR',
                'price' => 10500,
            ],
            [
                'category_id' => $tecnologiaCategory->id,
                'subcategory_id' => $subcategories['Cargador Celular']->id,
                'name' => 'Cargador tipo c / cabeza',
                'description' => 'TIPO C 1 ENTRADA 30W HYPERGEAR',
                'price' => 7900,
            ],
            [
                'category_id' => $tecnologiaCategory->id,
                'subcategory_id' => $subcategories['Periférico']->id,
                'name' => 'MOUSE',
                'description' => 'MOUSE CW902 ALAMBRICO ONIKUMA',
                'price' => 3950,
            ],
            [
                'category_id' => $tecnologiaCategory->id,
                'subcategory_id' => $subcategories['Periférico']->id,
                'name' => 'Mouse',
                'description' => 'MOUSE CW905 ALAMBRICO ONIKUMA',
                'price' => 4950,
            ],
            [
                'category_id' => $tecnologiaCategory->id,
                'subcategory_id' => $subcategories['Periférico']->id,
                'name' => 'TECLADO',
                'description' => 'TECLADO ALAMBRICO MECANICO G38 RGB ONIKUMA',
                'price' => 16900,
            ],
            [
                'category_id' => $tecnologiaCategory->id,
                'subcategory_id' => $subcategories['Periférico']->id,
                'name' => 'TECLADO',
                'description' => 'TECLADO G32 ALAMBRICO ONIKUMA',
                'price' => 7500,
            ],
            [
                'category_id' => $tecnologiaCategory->id,
                'subcategory_id' => $subcategories['Periférico']->id,
                'name' => 'TECLADO',
                'description' => 'TECLADO MECANICO G29 RGB ONIKUMA NEGRO',
                'price' => 16900,
            ],
            [
                'category_id' => $tecnologiaCategory->id,
                'subcategory_id' => $subcategories['router']->id,
                'name' => 'Router',
                'description' => 'Xiaomi Router AC1200',
                'price' => 21500,
                'deleted_at' => '2025-06-09 07:35:39',
            ],

            // Artículos de Anime
            [
                'category_id' => $animeCategory->id,
                'subcategory_id' => $subcategories['Peluche']->id,
                'name' => 'FUNKO DEMON SLAYER',
                'description' => 'PELUCHE FUNKO DEMON SLAYER KITMESU NO YAIBA INOSUKE',
                'price' => 8990,
            ],
            [
                'category_id' => $animeCategory->id,
                'subcategory_id' => $subcategories['Peluche']->id,
                'name' => 'PELUCHE FUNKO TANJIRO',
                'description' => 'PELUCHE FUNKO DEMON SLAYER KITMESU NO YAIBA TANJIRO',
                'price' => 9500,
            ],
            [
                'category_id' => $animeCategory->id,
                'subcategory_id' => $subcategories['Poster']->id,
                'name' => 'Poster Varios',
                'description' => 'Poster de anime varios',
                'price' => 1500,
            ],
            [
                'category_id' => $animeCategory->id,
                'subcategory_id' => $subcategories['Figura']->id,
                'name' => 'Figura Soge King',
                'description' => 'FUNKO POP ONE PIECE SNIPER KING #1514',
                'price' => 8200,
            ],
            [
                'category_id' => $animeCategory->id,
                'subcategory_id' => $subcategories['Figura']->id,
                'name' => 'Figura DEIDARA NARUTO',
                'description' => 'FUNKO POP- DEIDARA NARUTO #1434',
                'price' => 8200,
            ],
            [
                'category_id' => $animeCategory->id,
                'subcategory_id' => $subcategories['Peluche']->id,
                'name' => 'FUNKO DEMON SLAYER',
                'description' => 'PELUCHE FUNKO DEMON SLAYER KITMESU NO YAIBA INOSUKE',
                'price' => 8990,
            ],
            [
                'category_id' => $animeCategory->id,
                'subcategory_id' => $subcategories['Peluche']->id,
                'name' => 'PELUCHE FUNKO TANJIRO',
                'description' => 'PELUCHE FUNKO DEMON SLAYER KITMESU NO YAIBA TANJIRO',
                'price' => 8990,
            ],
            [
                'category_id' => $animeCategory->id,
                'subcategory_id' => $subcategories['Poster']->id,
                'name' => 'Poster Varios',
                'description' => 'Poster de anime varios',
                'price' => 1500,
            ],
            [
                'category_id' => $animeCategory->id,
                'subcategory_id' => $subcategories['Figura']->id,
                'name' => 'Figura Soge King',
                'description' => 'FUNKO POP ONE PIECE SNIPER KING #1514',
                'price' => 8200,
            ],
            [
                'category_id' => $animeCategory->id,
                'subcategory_id' => $subcategories['Figura']->id,
                'name' => 'Figura DEIDARA NARUTO Edicion limitada',
                'description' => 'FUNKO POP- DEIDARA NARUTO #1434',
                'price' => 9990,
            ],
        ];

        foreach ($articles as $article) {
            Article::create($article);
        }
    }
}
