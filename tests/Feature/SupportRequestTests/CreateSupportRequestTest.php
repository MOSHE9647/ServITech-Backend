<?php

namespace Tests\Feature\SupportRequestTests;

use App\Enums\UserRoles;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSupportRequestTest extends TestCase
{
    use RefreshDatabase; // Use RefreshDatabase to reset the database after each test

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class); // Seed the database with necessary data for the tests
    }

    /**
     * Test that an authenticated user can create a support request.
     * This ensures that only authenticated users can create support requests successfully.
     */
    public function test_an_authenticated_user_can_create_a_support_request(): void
    {
        // Given: An existing authenticated user and valid support request data
        $supportRequestData = [
            'date'      => now()->format('Y-m-d H:i:s'),
            'location'  => '123 Main Street',
            'detail'    => 'The system is not responding.',
        ];

        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user); // Ensure the user exists

        // When: The user attempts to create a support request
        $response = $this->apiAs($user, 'POST', route('support-request.store'), $supportRequestData);
        // dd($response->json());

        // Then: The request should succeed, and the support request should be stored in the database
        $response->assertStatus(201);
        $response->assertJsonStructure(['status', 'message', 'data' => ['supportRequest']]);
        $this->assertDatabaseHas('support_requests', [
            'date'=> $supportRequestData['date'],
            'location' => $supportRequestData['location'],
            'detail' => $supportRequestData['detail'],
            'user_id' => $user->id,
        ]);
    }

    /**
     * Test that a non-authenticated user cannot create a support request.
     * This ensures that only authenticated users can create support requests.
     */
    public function test_a_non_authenticated_user_cannot_create_a_support_request(): void
    {
        // Given: Valid support request data
        $supportRequestData = [
            'date' => now()->toDateString(),
            'location' => '123 Main Street',
            'detail' => 'The system is not responding.',
        ];

        // When: A non-authenticated user attempts to create a support request
        $response = $this->postJson(route('support-request.store'), $supportRequestData);

        // Then: The request should fail with a 401 Unauthorized status
        $response->assertStatus(401);
        $response->assertJsonStructure(['status', 'message']);
        $this->assertDatabaseMissing('support_requests', $supportRequestData);
    }

    /**
     * Test that the date field is required.
     * This ensures that missing the date field returns a 422 status with validation errors.
     */
    public function test_date_must_be_required(): void
    {
        // Given: An existing authenticated user and support request data with a missing date
        $user = User::factory()->create();
        $supportRequestData = [
            'location' => '123 Main Street',
            'detail' => 'The system is not responding.',
        ];

        // When: The user attempts to create a support request
        $response = $this->apiAs($user, 'POST', route('support-request.store'), $supportRequestData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['date']]);
    }

    /**
     * Test that the date must be a valid date.
     * This ensures that invalid date values return a 422 status with validation errors.
     */
    public function test_date_must_be_a_valid_date(): void
    {
        // Given: An existing authenticated user and support request data with an invalid date
        $user = User::factory()->create();
        $supportRequestData = [
            'date' => 'invalid-date',
            'location' => '123 Main Street',
            'detail' => 'The system is not responding.',
        ];

        // When: The user attempts to create a support request
        $response = $this->apiAs($user, 'POST', route('support-request.store'), $supportRequestData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['date']]);
    }

    /**
     * Test that the location field is required.
     * This ensures that missing the location field returns a 422 status with validation errors.
     */
    public function test_location_must_be_required(): void
    {
        // Given: An existing authenticated user and support request data with a missing location
        $user = User::factory()->create();
        $supportRequestData = [
            'date' => now()->toDateString(),
            'detail' => 'The system is not responding.',
        ];

        // When: The user attempts to create a support request
        $response = $this->apiAs($user, 'POST', route('support-request.store'), $supportRequestData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['location']]);
    }

    /**
     * Test that the detail field is required.
     * This ensures that missing the detail field returns a 422 status with validation errors.
     */
    public function test_detail_must_be_required(): void
    {
        // Given: An existing authenticated user and support request data with a missing detail
        $user = User::factory()->create();
        $supportRequestData = [
            'date' => now()->toDateString(),
            'location' => '123 Main Street',
        ];

        // When: The user attempts to create a support request
        $response = $this->apiAs($user, 'POST', route('support-request.store'), $supportRequestData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['detail']]);
    }
}