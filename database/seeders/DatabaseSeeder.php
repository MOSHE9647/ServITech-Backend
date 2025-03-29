<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed the database using the respective seeders
        $this->call([
            UserSeeder::class,
            ArticleSeeder::class,
        ]);

        // Create repair requests and attach related images
        // RepairRequest::factory(10)->create()->each(function ($repairRequest) {
        //     $repairRequest->images()->createMany(
        //         Image::factory(2)->make()->toArray() // Generate and attach 2 images per repair request
        //     );
        // });
    }
}
