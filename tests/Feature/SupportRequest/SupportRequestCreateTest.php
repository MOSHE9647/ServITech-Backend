<?php

namespace Tests\Feature\SupportRequest;

use App\Enums\UserRoles;
use App\Models\SupportRequest;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportRequestCreateTest extends TestCase
{
    use RefreshDatabase; // Reset the database after each test

    /**
     * Set up the test environment.
     * This method seeds the database before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            UserSeeder::class,
        ]);
    }

    /**
     * Test that an authenticated user can create a support request.
     * This ensures that only authenticated users can create support requests successfully.
     */
    public function test_an_authenticated_user_can_create_a_support_request(): void
    {
        // Given: An existing authenticated user and valid support request data
        $supportRequestData = [
            'date' => now()->format('Y-m-d'),
            'location' => '123 Main Street, Test City',
            'detail' => 'The system is not responding correctly and needs immediate attention.',
        ];

        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        // When: The user attempts to create a support request
        $response = $this->apiAs($user, 'POST', route('support-request.store'), $supportRequestData);

        // Then: The request should succeed, and the support request should be stored in the database
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'supportRequest' => [
                    'id',
                    'user_id',
                    'date',
                    'location',
                    'detail',
                    'created_at',
                    'updated_at'
                ]
            ]
        ]);

        $response->assertJsonFragment([
            'message' => __('messages.common.created', ['item' => __('messages.entities.support_request.singular')])
        ]);

        // Verify the support request was created in the database
        $this->assertDatabaseHas('support_requests', [
            'user_id' => $user->id,
            'location' => $supportRequestData['location'],
            'detail' => $supportRequestData['detail'],
        ]);

        // Verify the response contains the correct data
        $responseData = $response->json();
        $supportRequestData = $responseData['data']['supportRequest'];
        $this->assertEquals($user->id, $supportRequestData['user_id']);
        $this->assertEquals('123 Main Street, Test City', $supportRequestData['location']);
        $this->assertEquals('The system is not responding correctly and needs immediate attention.', $supportRequestData['detail']);
    }

    /**
     * Test that an authenticated admin user can create a support request.
     * This ensures that admin users can also create support requests.
     */
    public function test_an_authenticated_admin_user_can_create_a_support_request(): void
    {
        // Given: An existing authenticated admin user and valid support request data
        $supportRequestData = [
            'date' => now()->addDays(1)->format('Y-m-d'),
            'location' => 'Admin Office - Floor 5',
            'detail' => 'Network connectivity issues in the server room.',
        ];

        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        // When: The admin attempts to create a support request
        $response = $this->apiAs($admin, 'POST', route('support-request.store'), $supportRequestData);

        // Then: The request should succeed
        $response->assertStatus(201);
        $response->assertJsonStructure(['status', 'message', 'data' => ['supportRequest']]);

        // Verify the support request was created for the admin
        $this->assertDatabaseHas('support_requests', [
            'user_id' => $admin->id,
            'location' => $supportRequestData['location'],
            'detail' => $supportRequestData['detail'],
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
            'date' => now()->format('Y-m-d'),
            'location' => '123 Main Street',
            'detail' => 'The system is not responding.',
        ];

        // When: A non-authenticated user attempts to create a support request
        $response = $this->postJson(route('support-request.store'), $supportRequestData);

        // Then: The request should fail with a 401 Unauthorized status
        $response->assertStatus(401);
        $response->assertJsonStructure(['status', 'message']);

        // Verify no support request was created
        $this->assertDatabaseMissing('support_requests', [
            'location' => $supportRequestData['location'],
            'detail' => $supportRequestData['detail'],
        ]);
    }

    /**
     * Test that the date field is required.
     * This ensures that missing the date field returns a 422 status with validation errors.
     */
    public function test_date_must_be_required(): void
    {
        // Given: An authenticated user and support request data with a missing date
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $supportRequestData = [
            'location' => '123 Main Street',
            'detail' => 'The system is not responding.',
        ];

        // When: The user attempts to create a support request without date
        $response = $this->apiAs($user, 'POST', route('support-request.store'), $supportRequestData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['date']]);

        // Check that the error message exists
        $responseData = $response->json();
        $this->assertArrayHasKey('date', $responseData['errors']);
    }

    /**
     * Test that the date must be a valid date.
     * This ensures that invalid date values return a 422 status with validation errors.
     */
    public function test_date_must_be_a_valid_date(): void
    {
        // Given: An authenticated user and support request data with an invalid date
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $supportRequestData = [
            'date' => 'invalid-date-format',
            'location' => '123 Main Street',
            'detail' => 'The system is not responding.',
        ];

        // When: The user attempts to create a support request with invalid date
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
        // Given: An authenticated user and support request data with a missing location
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $supportRequestData = [
            'date' => now()->format('Y-m-d'),
            'detail' => 'The system is not responding.',
        ];

        // When: The user attempts to create a support request without location
        $response = $this->apiAs($user, 'POST', route('support-request.store'), $supportRequestData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['location']]);

        // Check that the error message exists
        $responseData = $response->json();
        $this->assertArrayHasKey('location', $responseData['errors']);
    }

    /**
     * Test that the location must be a string.
     * This ensures that non-string values for location return validation errors.
     */
    public function test_location_must_be_a_string(): void
    {
        // Given: An authenticated user and support request data with non-string location
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $supportRequestData = [
            'date' => now()->format('Y-m-d'),
            'location' => 12345, // Non-string value
            'detail' => 'The system is not responding.',
        ];

        // When: The user attempts to create a support request with invalid location
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
        // Given: An authenticated user and support request data with a missing detail
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $supportRequestData = [
            'date' => now()->format('Y-m-d'),
            'location' => '123 Main Street',
        ];

        // When: The user attempts to create a support request without detail
        $response = $this->apiAs($user, 'POST', route('support-request.store'), $supportRequestData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['detail']]);

        // Check that the error message exists
        $responseData = $response->json();
        $this->assertArrayHasKey('detail', $responseData['errors']);
    }

    /**
     * Test that the detail must be a string.
     * This ensures that non-string values for detail return validation errors.
     */
    public function test_detail_must_be_a_string(): void
    {
        // Given: An authenticated user and support request data with non-string detail
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $supportRequestData = [
            'date' => now()->format('Y-m-d'),
            'location' => '123 Main Street',
            'detail' => ['array', 'instead', 'of', 'string'], // Non-string value
        ];

        // When: The user attempts to create a support request with invalid detail
        $response = $this->apiAs($user, 'POST', route('support-request.store'), $supportRequestData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['detail']]);
    }

    /**
     * Test that support request can be created with different date formats.
     * This ensures that various valid date formats are accepted.
     */
    public function test_support_request_can_be_created_with_different_date_formats(): void
    {
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        // Test different valid date formats
        $dateFormats = [
            now()->format('Y-m-d'),           // 2025-06-17
            now()->format('Y-m-d H:i:s'),     // 2025-06-17 14:30:00
            now()->format('m/d/Y'),           // 06/17/2025
            now()->toISOString(),             // ISO 8601 format
        ];

        foreach ($dateFormats as $index => $dateFormat) {
            $supportRequestData = [
                'date' => $dateFormat,
                'location' => "Location {$index}",
                'detail' => "Detail for test {$index}",
            ];

            $response = $this->apiAs($user, 'POST', route('support-request.store'), $supportRequestData);

            $response->assertStatus(201);
            $this->assertDatabaseHas('support_requests', [
                'user_id' => $user->id,
                'location' => "Location {$index}",
                'detail' => "Detail for test {$index}",
            ]);
        }
    }

    /**
     * Test that support request creation returns the correct message and structure.
     * This ensures that the API returns the expected response format.
     */
    public function test_successful_creation_returns_correct_message_and_structure(): void
    {
        // Given: An authenticated user and valid support request data
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $supportRequestData = [
            'date' => now()->format('Y-m-d'),
            'location' => 'Test Location for Structure',
            'detail' => 'Test detail for response structure validation',
        ];

        // When: The user creates a support request
        $response = $this->apiAs($user, 'POST', route('support-request.store'), $supportRequestData);

        // Then: The response should have the correct structure and message
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'supportRequest' => [
                    'id',
                    'user_id',
                    'date',
                    'location',
                    'detail',
                    'created_at',
                    'updated_at'
                ]
            ]
        ]);        $response->assertJsonFragment([
            'status' => 201,
            'message' => __('messages.common.created', ['item' => __('messages.entities.support_request.singular')])
        ]);
    }
      /**
     * Test that user_id is automatically assigned to the authenticated user.
     * This ensures that the support request is correctly associated with the authenticated user.
     */
    public function test_user_id_is_automatically_assigned_to_authenticated_user(): void
    {
        // Given: Two different authenticated users
        $user1 = User::role(UserRoles::USER)->first();
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user1, 'User not found');
        $this->assertNotNull($admin, 'Admin user not found');
        $this->assertNotEquals($user1->id, $admin->id, 'Users should be different');

        // First test with user1
        $supportRequestData1 = [
            'date' => now()->format('Y-m-d'),
            'location' => 'Location for User 1',
            'detail' => 'Test for user 1 association',
        ];

        $response1 = $this->apiAs($user1, 'POST', route('support-request.store'), $supportRequestData1);
        $response1->assertStatus(201);

        // Check the response to see what user_id was actually used
        $responseData1 = $response1->json();
        $this->assertEquals($user1->id, $responseData1['data']['supportRequest']['user_id'], 'First request should use user1 ID');

        // Clear any authentication caches (if needed for JWT)
        auth()->logout();

        // Second test with admin
        $supportRequestData2 = [
            'date' => now()->format('Y-m-d'),
            'location' => 'Location for Admin',
            'detail' => 'Test for admin association',
        ];

        $response2 = $this->apiAs($admin, 'POST', route('support-request.store'), $supportRequestData2);
        $response2->assertStatus(201);

        // Check the response to see what user_id was actually used
        $responseData2 = $response2->json();
        $this->assertEquals($admin->id, $responseData2['data']['supportRequest']['user_id'], 'Second request should use admin ID');

        // Then: Each support request should be associated with the correct user
        $this->assertDatabaseHas('support_requests', [
            'user_id' => $user1->id,
            'location' => 'Location for User 1',
            'detail' => 'Test for user 1 association',
        ]);

        $this->assertDatabaseHas('support_requests', [
            'user_id' => $admin->id,
            'location' => 'Location for Admin',
            'detail' => 'Test for admin association',
        ]);
    }

    /**
     * Test that extra fields in the request are ignored.
     * This ensures that only expected fields are processed.
     */
    public function test_extra_fields_in_request_are_ignored(): void
    {
        // Given: An authenticated user and support request data with extra fields
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $supportRequestData = [
            'date' => now()->format('Y-m-d'),
            'location' => 'Test Location',
            'detail' => 'Test detail',
            'extra_field' => 'This should be ignored',
            'another_extra' => 'This too should be ignored',
            'user_id' => 999, // This should be overridden
        ];

        // When: The user creates a support request
        $response = $this->apiAs($user, 'POST', route('support-request.store'), $supportRequestData);

        // Then: The request should succeed and only valid fields should be stored
        $response->assertStatus(201);

        // Verify the support request was created with correct user_id (not the one from request)
        $this->assertDatabaseHas('support_requests', [
            'user_id' => $user->id, // Should be the authenticated user, not 999
            'location' => 'Test Location',
            'detail' => 'Test detail',
        ]);

        // Verify extra fields are not stored (this is handled by mass assignment protection)
        $supportRequest = SupportRequest::where('location', 'Test Location')->first();
        $this->assertNull($supportRequest->extra_field ?? null);
        $this->assertNull($supportRequest->another_extra ?? null);
    }
}