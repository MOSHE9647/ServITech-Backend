<?php

namespace Tests\Feature\SupportRequest;

use App\Enums\UserRoles;
use App\Models\SupportRequest;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportRequestUpdateTest extends TestCase
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
     * Test that an authenticated user can update their own support request.
     * This ensures that users can update their own support request details.
     */
    public function test_an_authenticated_user_can_update_their_own_support_request(): void
    {
        // Given: An authenticated user with a support request
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $supportRequest = SupportRequest::factory()->create([
            'user_id' => $user->id,
            'date' => now()->subDay()->format('Y-m-d'),
            'location' => 'Original Location',
            'detail' => 'Original detail description',
        ]);

        $updateData = [
            'date' => now()->format('Y-m-d'),
            'location' => 'Updated Location',
            'detail' => 'Updated detail description',
        ];

        // When: The user attempts to update their support request
        $response = $this->apiAs($user, 'PUT', route('support-request.update', $supportRequest), $updateData);

        // Then: The request should succeed and the support request should be updated
        $response->assertStatus(200);
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
            'message' => __('messages.common.updated', ['item' => __('messages.entities.support_request.singular')])
        ]);

        // Verify the support request was updated in the database
        $this->assertDatabaseHas('support_requests', [
            'id' => $supportRequest->id,
            'user_id' => $user->id,
            'location' => 'Updated Location',
            'detail' => 'Updated detail description',
        ]);

        // Verify the response contains the updated data
        $responseData = $response->json();
        $updatedSupportRequest = $responseData['data']['supportRequest'];
        $this->assertEquals('Updated Location', $updatedSupportRequest['location']);
        $this->assertEquals('Updated detail description', $updatedSupportRequest['detail']);
    }

    /**
     * Test that an authenticated admin user can update their own support request.
     * This ensures that admin users can also update their own support requests.
     */
    public function test_an_authenticated_admin_user_can_update_their_own_support_request(): void
    {
        // Given: An authenticated admin user with a support request
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $supportRequest = SupportRequest::factory()->create([
            'user_id' => $admin->id,
            'date' => now()->subDay()->format('Y-m-d'),
            'location' => 'Admin Original Location',
            'detail' => 'Admin original detail',
        ]);

        $updateData = [
            'date' => now()->format('Y-m-d'),
            'location' => 'Admin Updated Location',
            'detail' => 'Admin updated detail',
        ];

        // When: The admin attempts to update their support request
        $response = $this->apiAs($admin, 'PUT', route('support-request.update', $supportRequest), $updateData);

        // Then: The request should succeed
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'message', 'data' => ['supportRequest']]);

        // Verify the support request was updated in the database
        $this->assertDatabaseHas('support_requests', [
            'id' => $supportRequest->id,
            'user_id' => $admin->id,
            'location' => 'Admin Updated Location',
            'detail' => 'Admin updated detail',
        ]);
    }

    /**
     * Test that a non-authenticated user cannot update a support request.
     * This ensures that authentication is required to update support requests.
     */
    public function test_a_non_authenticated_user_cannot_update_a_support_request(): void
    {
        // Given: An existing support request
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $supportRequest = SupportRequest::factory()->create([
            'user_id' => $user->id,
            'location' => 'Original Location',
            'detail' => 'Original detail',
        ]);

        $updateData = [
            'date' => now()->format('Y-m-d'),
            'location' => 'Attempted Update Location',
            'detail' => 'Attempted update detail',
        ];

        // When: A non-authenticated user attempts to update the support request
        $response = $this->putJson(route('support-request.update', $supportRequest), $updateData);

        // Then: The request should fail with a 401 Unauthorized status
        $response->assertStatus(401);
        $response->assertJsonStructure(['status', 'message']);

        // Verify the support request was not updated in the database
        $this->assertDatabaseHas('support_requests', [
            'id' => $supportRequest->id,
            'location' => 'Original Location',
            'detail' => 'Original detail',
        ]);

        $this->assertDatabaseMissing('support_requests', [
            'id' => $supportRequest->id,
            'location' => 'Attempted Update Location',
        ]);
    }

    /**
     * Test that updating a non-existent support request returns a 404 error.
     * This ensures that the API handles non-existent resources gracefully.
     */
    public function test_updating_a_non_existent_support_request_returns_404(): void
    {
        // Given: An authenticated user and a non-existent support request ID
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $nonExistentSupportRequestId = 999999;

        $updateData = [
            'date' => now()->format('Y-m-d'),
            'location' => 'Non-existent Location',
            'detail' => 'Non-existent detail',
        ];

        // When: The user attempts to update the non-existent support request
        $response = $this->apiAs($user, 'PUT', route('support-request.update', $nonExistentSupportRequestId), $updateData);

        // Then: The request should fail with a 404 Not Found status
        $response->assertStatus(404);
    }

    /**
     * Test that the date field is required when updating a support request.
     * This ensures that missing the date field returns a 422 status with validation errors.
     */
    public function test_date_must_be_required_when_updating(): void
    {
        // Given: An authenticated user with a support request
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $supportRequest = SupportRequest::factory()->create([
            'user_id' => $user->id,
        ]);

        $updateData = [
            'location' => 'Updated Location',
            'detail' => 'Updated detail',
        ];

        // When: The user attempts to update the support request without a date
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
        // Given: An authenticated user with a support request
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $supportRequest = SupportRequest::factory()->create([
            'user_id' => $user->id,
        ]);

        $updateData = [
            'date' => 'invalid-date-format',
            'location' => 'Updated Location',
            'detail' => 'Updated detail',
        ];

        // When: The user attempts to update the support request with an invalid date
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
        // Given: An authenticated user with a support request
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $supportRequest = SupportRequest::factory()->create([
            'user_id' => $user->id,
        ]);

        $updateData = [
            'date' => now()->format('Y-m-d'),
            'detail' => 'Updated detail',
        ];

        // When: The user attempts to update the support request without a location
        $response = $this->apiAs($user, 'PUT', route('support-request.update', $supportRequest), $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['location']]);
    }

    /**
     * Test that the location must be a string when updating a support request.
     * This ensures that non-string location values return a 422 status with validation errors.
     */
    public function test_location_must_be_a_string_when_updating(): void
    {
        // Given: An authenticated user with a support request
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $supportRequest = SupportRequest::factory()->create([
            'user_id' => $user->id,
        ]);

        $updateData = [
            'date' => now()->format('Y-m-d'),
            'location' => 12345, // Non-string value
            'detail' => 'Updated detail',
        ];

        // When: The user attempts to update the support request with a non-string location
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
        // Given: An authenticated user with a support request
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $supportRequest = SupportRequest::factory()->create([
            'user_id' => $user->id,
        ]);

        $updateData = [
            'date' => now()->format('Y-m-d'),
            'location' => 'Updated Location',
        ];

        // When: The user attempts to update the support request without a detail
        $response = $this->apiAs($user, 'PUT', route('support-request.update', $supportRequest), $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['detail']]);
    }

    /**
     * Test that the detail must be a string when updating a support request.
     * This ensures that non-string detail values return a 422 status with validation errors.
     */
    public function test_detail_must_be_a_string_when_updating(): void
    {
        // Given: An authenticated user with a support request
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $supportRequest = SupportRequest::factory()->create([
            'user_id' => $user->id,
        ]);

        $updateData = [
            'date' => now()->format('Y-m-d'),
            'location' => 'Updated Location',
            'detail' => 67890, // Non-string value
        ];

        // When: The user attempts to update the support request with a non-string detail
        $response = $this->apiAs($user, 'PUT', route('support-request.update', $supportRequest), $updateData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['detail']]);
    }

    /**
     * Test that support request can be updated with different date formats.
     * This ensures that various valid date formats are accepted.
     */
    public function test_support_request_can_be_updated_with_different_date_formats(): void
    {
        // Given: An authenticated user with a support request
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $supportRequest = SupportRequest::factory()->create([
            'user_id' => $user->id,
        ]);

        // Test different valid date formats
        $validDateFormats = [
            now()->format('Y-m-d'),           // 2025-06-17
            now()->format('Y-m-d H:i:s'),     // 2025-06-17 14:30:00
            now()->format('m/d/Y'),           // 06/17/2025
        ];

        foreach ($validDateFormats as $index => $dateFormat) {
            $updateData = [
                'date' => $dateFormat,
                'location' => "Date Format Test {$index}",
                'detail' => "Testing date format {$index}",
            ];

            // When: The user updates the support request with different date formats
            $response = $this->apiAs($user, 'PUT', route('support-request.update', $supportRequest), $updateData);

            // Then: The request should succeed
            $response->assertStatus(200);
            $response->assertJsonStructure(['status', 'message', 'data' => ['supportRequest']]);
        }
    }

    /**
     * Test that updating a soft deleted support request returns 404.
     * This ensures that soft-deleted resources are properly handled.
     */
    public function test_updating_soft_deleted_support_request_returns_404(): void
    {
        // Given: An authenticated user with a support request that is soft deleted
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $supportRequest = SupportRequest::factory()->create([
            'user_id' => $user->id,
            'location' => 'Original Location',
            'detail' => 'Original detail',
        ]);

        // Soft delete the support request
        $supportRequest->delete();
        $this->assertSoftDeleted('support_requests', ['id' => $supportRequest->id]);

        $updateData = [
            'date' => now()->format('Y-m-d'),
            'location' => 'Updated Location',
            'detail' => 'Updated detail',
        ];

        // When: User attempts to update the soft deleted support request
        $response = $this->apiAs($user, 'PUT', route('support-request.update', $supportRequest->id), $updateData);

        // Then: The request should fail with a 404 Not Found status
        $response->assertStatus(404);
    }

    /**
     * Test that successful update returns the correct message structure.
     * This ensures that the API returns the expected response format.
     */
    public function test_successful_update_returns_correct_message_structure(): void
    {
        // Given: An authenticated user with a support request
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $supportRequest = SupportRequest::factory()->create([
            'user_id' => $user->id,
            'location' => 'Original Location',
            'detail' => 'Original detail',
        ]);

        $updateData = [
            'date' => now()->format('Y-m-d'),
            'location' => 'Structure Test Location',
            'detail' => 'Testing response structure',
        ];

        // When: User updates the support request
        $response = $this->apiAs($user, 'PUT', route('support-request.update', $supportRequest), $updateData);

        // Then: The response should have the correct structure and message
        $response->assertStatus(200);
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
            'status' => 200,
            'message' => __('messages.common.updated', ['item' => __('messages.entities.support_request.singular')])
        ]);

        // Verify the returned data contains the updated values
        $responseData = $response->json();
        $updatedSupportRequest = $responseData['data']['supportRequest'];
        $this->assertEquals('Structure Test Location', $updatedSupportRequest['location']);
        $this->assertEquals('Testing response structure', $updatedSupportRequest['detail']);
    }

    /**
     * Test that the endpoint handles edge cases gracefully.
     * This ensures robustness of the update functionality.
     */
    public function test_endpoint_handles_edge_cases_gracefully(): void
    {
        // Given: An authenticated user with a support request
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $supportRequest = SupportRequest::factory()->create([
            'user_id' => $user->id,
        ]);

        // Test with minimal valid data
        $minimalData = [
            'date' => now()->format('Y-m-d'),
            'location' => 'A', // Very short location
            'detail' => 'B',   // Very short detail
        ];

        // When: User updates with minimal data
        $response = $this->apiAs($user, 'PUT', route('support-request.update', $supportRequest), $minimalData);

        // Then: The request should succeed
        $response->assertStatus(200);
        $this->assertDatabaseHas('support_requests', [
            'id' => $supportRequest->id,
            'location' => 'A',
            'detail' => 'B',
        ]);

        // Test with long data
        $longData = [
            'date' => now()->format('Y-m-d'),
            'location' => str_repeat('Long Location ', 10),
            'detail' => str_repeat('Long detail text ', 20),
        ];

        // When: User updates with long data
        $response = $this->apiAs($user, 'PUT', route('support-request.update', $supportRequest), $longData);

        // Then: The request should succeed
        $response->assertStatus(200);

        // Test with special characters
        $specialData = [
            'date' => now()->format('Y-m-d'),
            'location' => 'Location with special chars: áéíóú ñ @#$%',
            'detail' => 'Detail with special chars: ¡¿ ""&><',
        ];

        // When: User updates with special characters
        $response = $this->apiAs($user, 'PUT', route('support-request.update', $supportRequest), $specialData);

        // Then: The request should succeed
        $response->assertStatus(200);
        $this->assertDatabaseHas('support_requests', [
            'id' => $supportRequest->id,
            'location' => 'Location with special chars: áéíóú ñ @#$%',
            'detail' => 'Detail with special chars: ¡¿ ""&><',
        ]);
    }

    /**
     * Test that extra fields in the request are ignored.
     * This ensures that only expected fields are processed.
     */
    public function test_extra_fields_in_request_are_ignored(): void
    {
        // Given: An authenticated user with a support request
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $supportRequest = SupportRequest::factory()->create([
            'user_id' => $user->id,
            'location' => 'Original Location',
            'detail' => 'Original detail',
        ]);

        $updateData = [
            'date' => now()->format('Y-m-d'),
            'location' => 'Updated Location',
            'detail' => 'Updated detail',
            'extra_field' => 'This should be ignored',
            'user_id' => 999, // This should be ignored
            'id' => 999, // This should be ignored
        ];

        // When: User updates the support request with extra fields
        $response = $this->apiAs($user, 'PUT', route('support-request.update', $supportRequest), $updateData);

        // Then: The request should succeed and extra fields should be ignored
        $response->assertStatus(200);
        
        // Verify that the original user_id and id are preserved
        $this->assertDatabaseHas('support_requests', [
            'id' => $supportRequest->id,
            'user_id' => $user->id, // Should remain unchanged
            'location' => 'Updated Location',
            'detail' => 'Updated detail',
        ]);

        // Verify that the extra field was not added to the database
        $this->assertDatabaseMissing('support_requests', [
            'user_id' => 999,
        ]);
    }

    /**
     * Test that the endpoint works correctly with different user roles.
     * This ensures that the update functionality works consistently across user types.
     */
    public function test_endpoint_works_correctly_with_different_user_roles(): void
    {
        // Given: Different types of users
        $regularUser = User::role(UserRoles::USER)->first();
        $adminUser = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($regularUser, 'Regular user not found');
        $this->assertNotNull($adminUser, 'Admin user not found');

        // Create support requests for each user type
        $regularUserRequest = SupportRequest::factory()->create([
            'user_id' => $regularUser->id,
            'location' => 'Regular User Original Location',
            'detail' => 'Regular user original detail',
        ]);

        $adminUserRequest = SupportRequest::factory()->create([
            'user_id' => $adminUser->id,
            'location' => 'Admin User Original Location',
            'detail' => 'Admin user original detail',
        ]);

        // Update data for both users
        $regularUserUpdateData = [
            'date' => now()->format('Y-m-d'),
            'location' => 'Regular User Updated Location',
            'detail' => 'Regular user updated detail',
        ];

        $adminUserUpdateData = [
            'date' => now()->format('Y-m-d'),
            'location' => 'Admin User Updated Location',
            'detail' => 'Admin user updated detail',
        ];

        // When: Each user updates their own support request
        $regularUserResponse = $this->apiAs($regularUser, 'PUT', route('support-request.update', $regularUserRequest), $regularUserUpdateData);
        $adminUserResponse = $this->apiAs($adminUser, 'PUT', route('support-request.update', $adminUserRequest), $adminUserUpdateData);

        // Then: Both should succeed
        $regularUserResponse->assertStatus(200);
        $adminUserResponse->assertStatus(200);

        // Verify data was updated correctly for both users
        $this->assertDatabaseHas('support_requests', [
            'id' => $regularUserRequest->id,
            'user_id' => $regularUser->id,
            'location' => 'Regular User Updated Location',
            'detail' => 'Regular user updated detail',
        ]);

        $this->assertDatabaseHas('support_requests', [
            'id' => $adminUserRequest->id,
            'user_id' => $adminUser->id,
            'location' => 'Admin User Updated Location',
            'detail' => 'Admin user updated detail',
        ]);
    }
}