<?php

namespace Tests\Feature\RepairRequestTests;

use App\Enums\RepairStatus;
use App\Enums\UserRoles;
use App\Models\RepairRequest;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UpdateRepairRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_authenticated_admin_user_can_update_repair_request()
    {
        // Given: An authenticated admin user and an existing repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists
        $repairRequest = RepairRequest::factory()->create();

        // When: The admin user updates the repair request
        $updateData = [
            'article_serialnumber' => 'SN654321',
            'article_accesories'   => 'Cargador, funda, mouse',
            'repair_status'        => RepairStatus::COMPLETED,
            'repair_details'       => 'Reparación completada con éxito',
            'repair_price'         => 2000.75,
            'repaired_at'          => now()->toDateString(),
        ];

        $response = $this->apiAs($user, 'PUT', "{$this->apiBase}/repair-request/{$repairRequest->receipt_number}", $updateData);

        // Then: The request should succeed, and the repair request should be updated in the database
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'message', 'data']);
        $response->assertJsonFragment(['status' => 200, 'message' => __('messages.repair_request.updated')]);

        $this->assertDatabaseHas('repair_requests', $updateData);
    }

    public function test_non_authenticated_user_cannot_update_repair_request()
    {
        // Given: An existing repair request
        $repairRequest = RepairRequest::factory()->create();

        // When: A non-authenticated user attempts to update the repair request
        $updateData = [
            'article_serialnumber' => 'SN654321',
            'article_accesories'   => 'Cargador, funda, mouse',
            'repair_status'        => RepairStatus::COMPLETED,
            'repair_details'       => 'Reparación completada con éxito',
            'repair_price'         => 2000.75,
            'repaired_at'          => now()->toDateString(),
        ];

        $response = $this->putJson("{$this->apiBase}/repair-request/{$repairRequest->receipt_number}", $updateData);

        // Then: The request should fail with a 401 Unauthorized status
        $response->assertStatus(401);
        $response->assertJsonStructure(['message']);
    }

    public function test_authenticated_admin_user_cannot_update_repair_request_with_invalid_identifier()
    {
        // Given: An authenticated admin user
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        // When: The admin user attempts to update a repair request with an invalid identifier
        $updateData = [
            'article_serialnumber' => 'SN654321',
            'article_accesories'   => 'Cargador, funda, mouse',
            'repair_status'        => RepairStatus::COMPLETED,
            'repair_details'       => 'Reparación completada con éxito',
            'repair_price'         => 2000.75,
            'repaired_at'          => now()->toDateString(),
        ];

        $response = $this->apiAs($user, 'PUT', "{$this->apiBase}/repair-request/invalid-identifier", $updateData);

        // Then: The request should fail with a 404 Not Found status
        $response->assertStatus(404);
        $response->assertJsonStructure(['message']);
    }

    // Tests for 'article_serialnumber'
    public function test_article_serialnumber_can_be_nullable()
    {
        // Given: An authenticated admin user and an existing repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $repairRequest = RepairRequest::factory()->create();

        // When: The admin user updates the repair request with a nullable article_serialnumber
        $updateData = [
            'article_serialnumber' => null,
            'article_accesories'   => 'Cargador, funda, mouse',
            'repair_status'        => RepairStatus::COMPLETED,
            'repair_details'       => 'Reparación completada con éxito',
            'repair_price'         => 2000.75,
            'repaired_at'          => now()->toDateString(),
        ];
        $response = $this->apiAs($user, 'PUT', "{$this->apiBase}/repair-request/{$repairRequest->receipt_number}", $updateData);

        // Then: The request should succeed, and the repair request should be updated in the database
        $response->assertStatus(200);
        $this->assertDatabaseHas('repair_requests', ['id' => $repairRequest->id, 'article_serialnumber' => null]);
    }

    public function test_article_serialnumber_must_be_a_string()
    {
        // Given: An authenticated admin user and an existing repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $repairRequest = RepairRequest::factory()->create();

        // When: The admin user updates the repair request with an invalid article_serialnumber
        $updateData = [
            'article_serialnumber' => 123456, // Invalid type
            'article_accesories'   => 'Cargador, funda, mouse',
            'repair_status'        => RepairStatus::COMPLETED,
            'repair_details'       => 'Reparación completada con éxito',
            'repair_price'         => 2000.75,
            'repaired_at'          => now()->toDateString(),
        ];
        $response = $this->apiAs($user, 'PUT', "{$this->apiBase}/repair-request/{$repairRequest->receipt_number}", $updateData);
        // dd($response->json());

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['article_serialnumber']);
    }

    public function test_article_serialnumber_must_have_min_6_characters()
    {
        // Given: An authenticated admin user and an existing repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $repairRequest = RepairRequest::factory()->create();

        // When: The admin user updates the repair request with a too short article_serialnumber
        $updateData = [
            'article_serialnumber' => '123', // Too short
            'article_accesories'   => 'Cargador, funda, mouse',
            'repair_status'        => RepairStatus::COMPLETED,
            'repair_details'       => 'Reparación completada con éxito',
            'repair_price'         => 2000.75,
            'repaired_at'          => now()->toDateString(),
        ];
        $response = $this->apiAs($user, 'PUT', "{$this->apiBase}/repair-request/{$repairRequest->receipt_number}", $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['article_serialnumber']);
    }

    // Tests for 'article_accesories'
    public function test_article_accesories_can_be_nullable()
    {
        // Given: An authenticated admin user and an existing repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $repairRequest = RepairRequest::factory()->create();

        // When: The admin user updates the repair request with a nullable article_accesories
        $updateData = [
            'article_serialnumber' => 'SN654321',
            'article_accesories'   => null,
            'repair_status'        => RepairStatus::COMPLETED,
            'repair_details'       => 'Reparación completada con éxito',
            'repair_price'         => 2000.75,
            'repaired_at'          => now()->toDateString(),
        ];
        $response = $this->apiAs($user, 'PUT', "{$this->apiBase}/repair-request/{$repairRequest->receipt_number}", $updateData);

        // Then: The request should succeed, and the repair request should be updated in the database
        $response->assertStatus(200);
        $this->assertDatabaseHas('repair_requests', ['id' => $repairRequest->id, 'article_accesories' => null]);
    }

    public function test_article_accesories_must_be_a_string()
    {
        // Given: An authenticated admin user and an existing repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $repairRequest = RepairRequest::factory()->create();

        // When: The admin user updates the repair request with an invalid article_accesories
        $updateData = [
            'article_serialnumber' => 'SN654321',
            'article_accesories'   => 12345, // Invalid type
            'repair_status'        => RepairStatus::COMPLETED,
            'repair_details'       => 'Reparación completada con éxito',
            'repair_price'         => 2000.75,
            'repaired_at'          => now()->toDateString(),
        ];
        $response = $this->apiAs($user, 'PUT', "{$this->apiBase}/repair-request/{$repairRequest->receipt_number}", $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['article_accesories']);
    }

    public function test_article_accesories_must_have_min_3_characters()
    {
        // Given: An authenticated admin user and an existing repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $repairRequest = RepairRequest::factory()->create();

        // When: The admin user updates the repair request with a too short article_accesories
        $updateData = [
            'article_serialnumber' => 'SN654321',
            'article_accesories'   => 'AB', // Too short
            'repair_status'        => RepairStatus::COMPLETED,
            'repair_details'       => 'Reparación completada con éxito',
            'repair_price'         => 2000.75,
            'repaired_at'          => now()->toDateString(),
        ];
        $response = $this->apiAs($user, 'PUT', "{$this->apiBase}/repair-request/{$repairRequest->receipt_number}", $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['article_accesories']);
    }

    // Tests for 'repair_status'
    public function test_repair_status_is_required()
    {
        // Given: An authenticated admin user and an existing repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $repairRequest = RepairRequest::factory()->create();

        // When: The admin user updates the repair request with a missing repair_status
        $updateData = [
            'article_serialnumber' => 'SN654321',
            'article_accesories'   => 'Cargador, funda, mouse',
            'repair_details'       => 'Reparación completada con éxito',
            'repair_price'         => 2000.75,
            'repaired_at'          => now()->toDateString(),
        ];
        $response = $this->apiAs($user, 'PUT', "{$this->apiBase}/repair-request/{$repairRequest->receipt_number}", $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['repair_status']);
    }

    public function test_repair_status_must_be_a_valid_enum()
    {
        // Given: An authenticated admin user and an existing repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $repairRequest = RepairRequest::factory()->create();

        // When: The admin user updates the repair request with an invalid repair_status
        $updateData = [
            'article_serialnumber' => 'SN654321',
            'article_accesories'   => 'Cargador, funda, mouse',
            'repair_status'        => 'invalid_enum', // Invalid enum
            'repair_details'       => 'Reparación completada con éxito',
            'repair_price'         => 2000.75,
            'repaired_at'          => now()->toDateString(),
        ];
        $response = $this->apiAs($user, 'PUT', "{$this->apiBase}/repair-request/{$repairRequest->receipt_number}", $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['repair_status']);
    }

    // Tests for 'repair_details'
    public function test_repair_details_can_be_nullable()
    {
        // Given: An authenticated admin user and an existing repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $repairRequest = RepairRequest::factory()->create();

        // When: The admin user updates the repair request with a nullable repair_details
        $updateData = [
            'repair_details'       => null, // Nullable field
            'article_serialnumber' => 'SN654321',
            'article_accesories'   => 'Cargador, funda, mouse',
            'repair_status'        => RepairStatus::COMPLETED,
            'repair_price'         => 2000.75,
            'repaired_at'          => now()->toDateString(),
        ];
        $response = $this->apiAs($user, 'PUT', "{$this->apiBase}/repair-request/{$repairRequest->receipt_number}", $updateData);

        // Then: The request should succeed, and the repair request should be updated in the database
        $response->assertStatus(200);
        $this->assertDatabaseHas('repair_requests', ['id' => $repairRequest->id, 'repair_details' => null]);
    }

    public function test_repair_details_must_be_a_string()
    {
        // Given: An authenticated admin user and an existing repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $repairRequest = RepairRequest::factory()->create();

        // When: The admin user updates the repair request with an invalid repair_details
        $updateData = [
            'repair_details'       => 12345, // Invalid type
            'article_serialnumber' => 'SN654321',
            'article_accesories'   => 'Cargador, funda, mouse',
            'repair_status'        => RepairStatus::COMPLETED,
            'repair_price'         => 2000.75,
            'repaired_at'          => now()->toDateString(),
        ];
        $response = $this->apiAs($user, 'PUT', "{$this->apiBase}/repair-request/{$repairRequest->receipt_number}", $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['repair_details']);
    }

    public function test_repair_details_must_have_min_3_characters()
    {
        // Given: An authenticated admin user and an existing repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $repairRequest = RepairRequest::factory()->create();

        // When: The admin user updates the repair request with a too short repair_details
        $updateData = [
            'repair_details'       => 'AB', // Too short
            'article_serialnumber' => 'SN654321',
            'article_accesories'   => 'Cargador, funda, mouse',
            'repair_status'        => RepairStatus::COMPLETED,
            'repair_price'         => 2000.75,
            'repaired_at'          => now()->toDateString(),
        ];
        $response = $this->apiAs($user, 'PUT', "{$this->apiBase}/repair-request/{$repairRequest->receipt_number}", $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['repair_details']);
    }

    // Tests for 'repair_price'
    public function test_repair_price_can_be_nullable()
    {
        // Given: An authenticated admin user and an existing repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $repairRequest = RepairRequest::factory()->create();

        // When: The admin user updates the repair request with a nullable repair_price
        $updateData = [
            'repair_price'         => null, // Nullable field
            'article_serialnumber' => 'SN654321',
            'article_accesories'   => 'Cargador, funda, mouse',
            'repair_status'        => RepairStatus::COMPLETED,
            'repair_details'       => 'Reparación completada con éxito',
            'repaired_at'          => now()->toDateString(),
        ];
        $response = $this->apiAs($user, 'PUT', "{$this->apiBase}/repair-request/{$repairRequest->receipt_number}", $updateData);

        // Then: The request should succeed, and the repair request should be updated in the database
        $response->assertStatus(200);
        $this->assertDatabaseHas('repair_requests', ['id' => $repairRequest->id, 'repair_price' => null]);
    }

    public function test_repair_price_must_be_numeric()
    {
        // Given: An authenticated admin user and an existing repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $repairRequest = RepairRequest::factory()->create();

        // When: The admin user updates the repair request with an invalid repair_price
        $updateData = [
            'repair_price'         => 'not_a_number', // Invalid type
            'article_serialnumber' => 'SN654321',
            'article_accesories'   => 'Cargador, funda, mouse',
            'repair_status'        => RepairStatus::COMPLETED,
            'repair_details'       => 'Reparación completada con éxito',
            'repaired_at'          => now()->toDateString(),
        ];
        $response = $this->apiAs($user, 'PUT', "{$this->apiBase}/repair-request/{$repairRequest->receipt_number}", $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['repair_price']);
    }

    // Tests for 'repaired_at'
    public function test_repaired_at_can_be_nullable()
    {
        // Given: An authenticated admin user and an existing repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $repairRequest = RepairRequest::factory()->create();

        // When: The admin user updates the repair request with a nullable repaired_at
        $updateData = [
            'repaired_at'          => null, // Nullable field
            'repair_price'         => 2000.75,
            'article_serialnumber' => 'SN654321',
            'article_accesories'   => 'Cargador, funda, mouse',
            'repair_status'        => RepairStatus::COMPLETED,
            'repair_details'       => 'Reparación completada con éxito',
        ];
        $response = $this->apiAs($user, 'PUT', "{$this->apiBase}/repair-request/{$repairRequest->receipt_number}", $updateData);

        // Then: The request should succeed, and the repair request should be updated in the database
        $response->assertStatus(200);
        $this->assertDatabaseHas('repair_requests', ['id' => $repairRequest->id, 'repaired_at' => null]);
    }

    public function test_repaired_at_must_be_a_valid_date()
    {
        // Given: An authenticated admin user and an existing repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $repairRequest = RepairRequest::factory()->create();

        // When: The admin user updates the repair request with an invalid repaired_at
        $updateData = [
            'repaired_at'          => 'invalid_date', // Invalid date
            'repair_price'         => 2000.75,
            'article_serialnumber' => 'SN654321',
            'article_accesories'   => 'Cargador, funda, mouse',
            'repair_status'        => RepairStatus::COMPLETED,
            'repair_details'       => 'Reparación completada con éxito',
        ];
        $response = $this->apiAs($user, 'PUT', "{$this->apiBase}/repair-request/{$repairRequest->receipt_number}", $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['repaired_at']);
    }
}
