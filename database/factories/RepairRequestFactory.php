<?php

namespace Database\Factories;

use App\Models\RepairRequest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RepairRequest>
 */
class RepairRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'receipt_number' => RepairRequest::generateReceiptNumber(),
            'customer_name' => $this->faker->name(),
            'customer_phone' => $this->faker->phoneNumber(),
            'customer_email' => $this->faker->safeEmail(),
            'article_name' => $this->faker->word(),
            'article_type' => $this->faker->randomElement(['Laptop', 'Smartphone', 'Console']),
            'article_brand' => $this->faker->company(),
            'article_model' => strtoupper($this->faker->lexify('?????')),
            'article_serialnumber' => strtoupper($this->faker->lexify('??????????')),
            'article_problem' => $this->faker->sentence(),
            'article_accesories' => $this->faker->sentence(),
            'repair_status' => $this->faker->randomElement(['Pending', 'In Progress', 'Completed']),
            'repair_details' => $this->faker->paragraph(),
            'repair_price' => $this->faker->randomFloat(2, 2000, 50000),
            'received_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'repaired_at' => $this->faker->optional()->dateTimeBetween('now', '+6 months'),
        ];
    }
}
