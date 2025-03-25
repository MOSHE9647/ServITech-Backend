<?php

namespace Tests\Feature;

use App\Enums\RepairStatus;
use App\Enums\UserRoles;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CreateRepairRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(class: DatabaseSeeder::class);
    }

    public function test_an_authenticated_admin_user_can_create_repair_requests()
    {
        // Given:
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When:
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);
        // dd($response->json());

        // Then:
        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'status', 'message']);
        $response->assertJsonFragment([
            'status' => 200,
            'message'=> __('messages.repair_request.created'),
            'data' => [
                'repairRequest' => array_merge($repairRequest, [
                    'id' => 1,
                    'receipt_number'=> "RR-000000000001",
                    'created_at'=> now()->format('Y-m-d\TH:i:s.000000\Z'),
                    'updated_at'=> now()->format('Y-m-d\TH:i:s.000000\Z'),
                ]),
            ],
        ]);

        $this->assertDatabaseHas('repair_requests', $repairRequest);
    }

    public function test_a_non_authenticated_admin_user_can_not_create_repair_requests()
    {
        // Given:
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        $user = User::create([
            "name" => "Example",
            "last_name" => "Example Example",
            'email' => 'example@example.com',
            'password'=> bcrypt('password'),
        ]);
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists
        $user->assignRole(UserRoles::USER);

        // When:
        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/repair-request/", $repairRequest);
        // dd($response->json());

        // Then:
        $response->assertStatus(403);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 403,
            'message'=> __('User does not have the right roles.'),
        ]);
    }
}
