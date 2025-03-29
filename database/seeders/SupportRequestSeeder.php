<?php

namespace Database\Seeders;

use App\Enums\UserRoles;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SupportRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create support requests for the user
        SupportRequest::factory()->count(5)->create([
            'user_id' => User::role(UserRoles::USER)->firstOrFail()->id,
        ]);

        // Create support requests for the admin
        SupportRequest::factory()->count(5)->create([
            'user_id' => User::role(UserRoles::ADMIN)->firstOrFail()->id,
        ]);
    }
}
