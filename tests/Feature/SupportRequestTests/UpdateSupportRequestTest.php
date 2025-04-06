<?php

namespace Tests\Feature\SupportRequestTests;

use App\Enums\UserRoles;
use App\Models\SupportRequest;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateSupportRequestTest extends TestCase
{
    use RefreshDatabase; // Use RefreshDatabase to reset the database after each test

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class); // Seed the database with necessary data for the tests
    }

    /**
     * Test that an authenticated user can update a support request.
     * This ensures that only authenticated users can update support requests successfully.
     */
    public function test_an_authenticated_user_can_update_a_support_request(): void
    {
        // Given: An existing authenticated user and a support request
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user); // Ensure the user exists

        $supportRequest = SupportRequest::where('user_id', $user->id)->first();
        $this->assertNotNull($supportRequest); // Ensure the support request exists
        $this->assertEquals($user->id, $supportRequest->user_id); // Ensure the support request belongs to the user

        $updateData = [
            'date' => now()->format('Y-m-d H:i:s'),
            'location' => '456 Updated Street',
            'detail' => 'The system is now partially responding.',
        ];

        // When: The user attempts to update the support request
        $response = $this->apiAs($user, 'PUT', route('support-request.update', $supportRequest), $updateData);

        // Then: The request should succeed, and the support request should be updated in the database
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'message', 'data' => ['supportRequest']]);
        $this->assertDatabaseHas('support_requests', [
            'id' => $supportRequest->id,
            'date' => $updateData['date'],
            'location' => $updateData['location'],
            'detail' => $updateData['detail'],
        ]);
    }

    /**
     * Test that a non-authenticated user cannot update a support request.
     * This ensures that only authenticated users can update support requests.
     */
    public function test_a_non_authenticated_user_cannot_update_a_support_request(): void
    {
        // Given: An existing support request
        $supportRequest = SupportRequest::factory()->create();

        $updateData = [
            'date' => now()->format('Y-m-d H:i:s'),
            'location' => '456 Updated Street',
            'detail' => 'The system is now partially responding.',
        ];

        // When: A non-authenticated user attempts to update the support request
        $response = $this->putJson(route('support-request.update', $supportRequest), $updateData);

        // Then: The request should fail with a 401 Unauthorized status
        $response->assertStatus(401);
        $response->assertJsonStructure(['status', 'message']);
        $this->assertDatabaseMissing('support_requests', $updateData);
    }

    /**
     * Test that the date field is required when updating a support request.
     * This ensures that missing the date field returns a 422 status with validation errors.
     */
    public function test_date_must_be_required_when_updating(): void
    {
        // Given: An existing authenticated user and a support request
        $user = User::role(UserRoles::USER)->first();
        $supportRequest = SupportRequest::factory()->create(['user_id' => $user->id]);

        $updateData = [
            'location' => '456 Updated Street',
            'detail' => 'The system is now partially responding.',
        ];

        // When: The user attempts to update the support request
        $response = $this->apiAs($user, 'PUT', route('support-request.update', $supportRequest), $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['date']]);
    }

    /**
     * Test that the date must be a valid date when updating a support request.
     * This ensures that invalid date values return a 422 status with validation errors.
     */
    public function test_date_must_be_a_valid_date_when_updating(): void
    {
        // Given: An existing authenticated user and a support request
        $user = User::role(UserRoles::USER)->first();
        $supportRequest = SupportRequest::factory()->create(['user_id' => $user->id]);

        $updateData = [
            'date' => 'invalid-date',
            'location' => '456 Updated Street',
            'detail' => 'The system is now partially responding.',
        ];

        // When: The user attempts to update the support request
        $response = $this->apiAs($user, 'PUT', route('support-request.update', $supportRequest), $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['date']]);
    }

    /**
     * Test that the location field is required when updating a support request.
     * This ensures that missing the location field returns a 422 status with validation errors.
     */
    public function test_location_must_be_required_when_updating(): void
    {
        // Given: An existing authenticated user and a support request
        $user = User::role(UserRoles::USER)->first();
        $supportRequest = SupportRequest::factory()->create(['user_id' => $user->id]);

        $updateData = [
            'date' => now()->format('Y-m-d H:i:s'),
            'detail' => 'The system is now partially responding.',
        ];

        // When: The user attempts to update the support request
        $response = $this->apiAs($user, 'PUT', route('support-request.update', $supportRequest), $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['location']]);
    }

    /**
     * Test that the detail field is required when updating a support request.
     * This ensures that missing the detail field returns a 422 status with validation errors.
     */
    public function test_detail_must_be_required_when_updating(): void
    {
        // Given: An existing authenticated user and a support request
        $user = User::role(UserRoles::USER)->first();
        $supportRequest = SupportRequest::factory()->create(['user_id' => $user->id]);

        $updateData = [
            'date' => now()->format('Y-m-d H:i:s'),
            'location' => '456 Updated Street',
        ];

        // When: The user attempts to update the support request
        $response = $this->apiAs($user, 'PUT', route('support-request.update', $supportRequest), $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['detail']]);
    }
}