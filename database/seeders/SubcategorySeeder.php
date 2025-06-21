<?php

namespace Database\Seeders;

use App\Models\Subcategory;
use Illuminate\Database\Seeder;

class SubcategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subcategories = [
            // Subcategories of Tecnologia (category_id = 2)
            ['category_id' => 2, 'name' => 'router', 'description' => 'equipo de comunicación de red'],
            ['category_id' => 2, 'name' => 'Memoria', 'description' => 'Ram ddr3'],
            ['category_id' => 2, 'name' => 'Cámaras', 'description' => 'Dispositivo de grabación continua'],
            ['category_id' => 2, 'name' => 'Almacenamiento', 'description' => 'Unidad de disco Rígido / Solido / USB  / disco externo'],
            ['category_id' => 2, 'name' => 'Fuente de poder', 'description' => 'Alimentación de corriente FP'],
            ['category_id' => 2, 'name' => 'Periférico', 'description' => 'equipo conexión externa o por usb'],
            ['category_id' => 2, 'name' => 'BATERÍA EXTERNA', 'description' => 'Batería de carga externa'],
            ['category_id' => 2, 'name' => 'Switch', 'description' => 'Equipo de red / extensor'],
            ['category_id' => 2, 'name' => 'UPS', 'description' => 'Equipo de respaldo para perdida de corriente'],
            ['category_id' => 2, 'name' => 'Cargador Celular', 'description' => 'Cargador varios usos tipo c/ ubs lapto'],

            // Subcategories of Anime (category_id = 1)
            ['category_id' => 1, 'name' => 'Poster', 'description' => 'Poster de temática anime'],
            ['category_id' => 1, 'name' => 'Figura', 'description' => 'Figura de temática anime / varias'],
            ['category_id' => 1, 'name' => 'Rompecabezas', 'description' => 'Rompecabezas de anime o tematica varias'],
            ['category_id' => 1, 'name' => 'Llavero', 'description' => 'Llavero de temática anime / varias'],
            ['category_id' => 1, 'name' => 'Peluche', 'description' => 'Peluche de temática anime / varias'],
            ['category_id' => 1, 'name' => 'Camisa', 'description' => 'Camisa de diferentes tallas, temática anime / varias'],
            ['category_id' => 1, 'name' => 'Cobija', 'description' => 'Cobija de temática anime / varias'],
            ['category_id' => 1, 'name' => 'Stikers', 'description' => 'Stiker de anime o temática varias'],
        ];

        foreach ($subcategories as $subcategory) {
            Subcategory::create($subcategory);
        }
    }
}
