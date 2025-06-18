<?php

namespace Tests\Feature\SupportRequest;

use App\Enums\UserRoles;
use App\Models\SupportRequest;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportRequestShowTest extends TestCase
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
     * Test that an authenticated user can view their own support request.
     * This ensures that users can view their own support request details.
     */
    public function test_an_authenticated_user_can_view_their_own_support_request(): void
    {
        // Given: An authenticated user with a support request
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $supportRequest = SupportRequest::factory()->create([
            'user_id' => $user->id,
            'date' => now()->format('Y-m-d'),
            'location' => 'Test Location for Show',
            'detail' => 'Test detail for show functionality',
        ]);

        // When: The user attempts to view their support request
        $response = $this->apiAs($user, 'GET', route('support-request.show', $supportRequest));

        // Then: The request should succeed and return the support request details
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
            'message' => __('messages.common.retrieved', ['item' => __('messages.entities.support_request.singular')])
        ]);

        // Verify the returned data matches the created support request
        $responseData = $response->json();
        $returnedSupportRequest = $responseData['data']['supportRequest'];
        
        $this->assertEquals($supportRequest->id, $returnedSupportRequest['id']);
        $this->assertEquals($user->id, $returnedSupportRequest['user_id']);
        $this->assertEquals('Test Location for Show', $returnedSupportRequest['location']);
        $this->assertEquals('Test detail for show functionality', $returnedSupportRequest['detail']);
    }

    /**
     * Test that an authenticated admin user can view their own support request.
     * This ensures that admin users can also view their own support request details.
     */
    public function test_an_authenticated_admin_user_can_view_their_own_support_request(): void
    {
        // Given: An authenticated admin user with a support request
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $supportRequest = SupportRequest::factory()->create([
            'user_id' => $admin->id,
            'date' => now()->format('Y-m-d'),
            'location' => 'Admin Test Location',
            'detail' => 'Admin test detail for show',
        ]);

        // When: The admin attempts to view their support request
        $response = $this->apiAs($admin, 'GET', route('support-request.show', $supportRequest));

        // Then: The request should succeed
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'message', 'data' => ['supportRequest']]);

        // Verify the returned data belongs to the admin
        $responseData = $response->json();
        $this->assertEquals($admin->id, $responseData['data']['supportRequest']['user_id']);
    }

    /**
     * Test that a non-authenticated user cannot view a support request.
     * This ensures that authentication is required to view support requests.
     */
    public function test_a_non_authenticated_user_cannot_view_a_support_request(): void
    {
        // Given: An existing support request
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $supportRequest = SupportRequest::factory()->create([
            'user_id' => $user->id,
        ]);

        // When: A non-authenticated user attempts to view the support request
        $response = $this->getJson(route('support-request.show', $supportRequest));

        // Then: The request should fail with a 401 Unauthorized status
        $response->assertStatus(401);
        $response->assertJsonStructure(['status', 'message']);
    }

    /**
     * Test that an authenticated user cannot view another user's support request.
     * This ensures that users can only view their own support requests.
     */
    public function test_an_authenticated_user_cannot_view_another_users_support_request(): void
    {
        // Given: Two different users
        $user1 = User::role(UserRoles::USER)->first();
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user1, 'User 1 not found');
        $this->assertNotNull($admin, 'Admin user not found');
        $this->assertNotEquals($user1->id, $admin->id, 'Users should be different');

        // And: A support request belonging to the admin
        $adminSupportRequest = SupportRequest::factory()->create([
            'user_id' => $admin->id,
            'date' => now()->format('Y-m-d'),
            'location' => 'Admin Only Location',
            'detail' => 'This belongs to admin user',
        ]);

        // When: User1 attempts to view Admin's support request
        $response = $this->apiAs($user1, 'GET', route('support-request.show', $adminSupportRequest));

        // Then: The request should fail with a 404 Not Found status (for security reasons)
        $response->assertStatus(404);
        $response->assertJsonStructure(['status', 'message']);
        $response->assertJsonFragment([
            'message' => __('messages.common.not_found', ['item' => __('messages.entities.support_request.singular')])
        ]);
    }

    /**
     * Test that an admin cannot view regular user's support requests.
     * This ensures that even admins can only view their own support requests.
     */
    public function test_an_admin_cannot_view_regular_users_support_request(): void
    {
        // Given: A regular user and an admin user
        $user = User::role(UserRoles::USER)->first();
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, 'User not found');
        $this->assertNotNull($admin, 'Admin user not found');

        // And: A support request belonging to the regular user
        $userSupportRequest = SupportRequest::factory()->create([
            'user_id' => $user->id,
            'date' => now()->format('Y-m-d'),
            'location' => 'User Only Location',
            'detail' => 'This belongs to regular user',
        ]);

        // When: Admin attempts to view the user's support request
        $response = $this->apiAs($admin, 'GET', route('support-request.show', $userSupportRequest));

        // Then: The request should fail with a 404 Not Found status
        $response->assertStatus(404);
        $response->assertJsonStructure(['status', 'message']);
    }

    /**
     * Test that viewing a non-existent support request returns a 404 error.
     * This ensures that the API handles non-existent resources gracefully.
     */
    public function test_viewing_a_non_existent_support_request_returns_404(): void
    {
        // Given: An authenticated user and a non-existent support request ID
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $nonExistentSupportRequestId = 999999;

        // When: The user attempts to view the non-existent support request
        $response = $this->apiAs($user, 'GET', route('support-request.show', $nonExistentSupportRequestId));

        // Then: The request should fail with a 404 Not Found status
        $response->assertStatus(404);
    }

    /**
     * Test that viewing a soft deleted support request returns 404.
     * This ensures that soft-deleted resources are properly handled.
     */
    public function test_viewing_soft_deleted_support_request_returns_404(): void
    {
        // Given: A user with a support request that is soft deleted
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $supportRequest = SupportRequest::factory()->create([
            'user_id' => $user->id,
            'date' => now()->format('Y-m-d'),
            'location' => 'Soft Deleted Location',
            'detail' => 'This will be soft deleted',
        ]);

        // Soft delete the support request
        $supportRequest->delete();
        $this->assertSoftDeleted('support_requests', ['id' => $supportRequest->id]);

        // When: User attempts to view the soft deleted support request
        $response = $this->apiAs($user, 'GET', route('support-request.show', $supportRequest->id));

        // Then: The request should fail with a 404 Not Found status
        $response->assertStatus(404);
    }

    /**
     * Test that all required fields are present in the response.
     * This ensures the API returns complete support request data.
     */
    public function test_all_required_fields_are_present_in_response(): void
    {
        // Given: An authenticated user with a support request
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $supportRequest = SupportRequest::factory()->create([
            'user_id' => $user->id,
            'date' => now()->format('Y-m-d'),
            'location' => 'Complete Data Location',
            'detail' => 'Complete data detail',
        ]);

        // When: The user views their support request
        $response = $this->apiAs($user, 'GET', route('support-request.show', $supportRequest));

        // Then: All required fields should be present
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

        $responseData = $response->json();
        $returnedSupportRequest = $responseData['data']['supportRequest'];

        // Verify all fields have values
        $this->assertNotNull($returnedSupportRequest['id']);
        $this->assertNotNull($returnedSupportRequest['user_id']);
        $this->assertNotNull($returnedSupportRequest['date']);
        $this->assertNotNull($returnedSupportRequest['location']);
        $this->assertNotNull($returnedSupportRequest['detail']);
        $this->assertNotNull($returnedSupportRequest['created_at']);
        $this->assertNotNull($returnedSupportRequest['updated_at']);

        // Verify the data matches what was created
        $this->assertEquals($supportRequest->id, $returnedSupportRequest['id']);
        $this->assertEquals($user->id, $returnedSupportRequest['user_id']);
        $this->assertEquals('Complete Data Location', $returnedSupportRequest['location']);
        $this->assertEquals('Complete data detail', $returnedSupportRequest['detail']);
    }

    /**
     * Test that the response contains the correct success message.
     * This ensures that the API returns properly localized messages.
     */
    public function test_response_contains_correct_success_message(): void
    {
        // Given: An authenticated user with a support request
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $supportRequest = SupportRequest::factory()->create([
            'user_id' => $user->id,
        ]);

        // When: The user views their support request
        $response = $this->apiAs($user, 'GET', route('support-request.show', $supportRequest));

        // Then: The response should contain the correct message
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'status' => 200,
            'message' => __('messages.common.retrieved', ['item' => __('messages.entities.support_request.singular')])
        ]);
    }

    /**
     * Test that the endpoint works with different date formats.
     * This ensures that support requests with various date formats are displayed correctly.
     */
    public function test_endpoint_works_with_different_date_formats(): void
    {
        // Given: An authenticated user
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        // Create support requests with different date formats
        $dateFormats = [
            now()->format('Y-m-d'),           // 2025-06-17
            now()->format('Y-m-d H:i:s'),     // 2025-06-17 14:30:00
        ];

        foreach ($dateFormats as $index => $dateFormat) {
            $supportRequest = SupportRequest::factory()->create([
                'user_id' => $user->id,
                'date' => $dateFormat,
                'location' => "Date Format Test {$index}",
                'detail' => "Testing date format {$index}",
            ]);

            // When: The user views the support request
            $response = $this->apiAs($user, 'GET', route('support-request.show', $supportRequest));

            // Then: The request should succeed
            $response->assertStatus(200);
            $response->assertJsonStructure(['status', 'message', 'data' => ['supportRequest']]);

            $responseData = $response->json();
            $this->assertEquals($user->id, $responseData['data']['supportRequest']['user_id']);
            $this->assertEquals("Date Format Test {$index}", $responseData['data']['supportRequest']['location']);
        }
    }

    /**
     * Test that authentication is required before ownership check.
     * This ensures authentication is checked first, then ownership.
     */
    public function test_authentication_is_required_before_ownership_check(): void
    {
        // Given: Any existing support request
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $supportRequest = SupportRequest::factory()->create([
            'user_id' => $user->id,
        ]);

        // When: A non-authenticated user attempts to view any support request
        $response = $this->getJson(route('support-request.show', $supportRequest));

        // Then: Should get 401 Unauthorized, not 404 Not Found
        $response->assertStatus(401);
        $response->assertJsonStructure(['status', 'message']);
    }

    /**
     * Test that the endpoint handles edge cases gracefully.
     * This ensures robustness of the show functionality.
     */
    public function test_endpoint_handles_edge_cases_gracefully(): void
    {
        // Given: An authenticated user with support requests containing edge case data
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        // Test with minimal data
        $minimalSupportRequest = SupportRequest::factory()->create([
            'user_id' => $user->id,
            'date' => now()->format('Y-m-d'),
            'location' => 'A',  // Very short location
            'detail' => 'B',    // Very short detail
        ]);

        // Test with long data
        $longSupportRequest = SupportRequest::factory()->create([
            'user_id' => $user->id,
            'date' => now()->format('Y-m-d'),
            'location' => str_repeat('Long Location ', 10),
            'detail' => str_repeat('Long detail text ', 20),
        ]);

        // Test with special characters
        $specialSupportRequest = SupportRequest::factory()->create([
            'user_id' => $user->id,
            'date' => now()->format('Y-m-d'),
            'location' => 'Location with special chars: áéíóú ñ @#$%',
            'detail' => 'Detail with special chars: ¡¿ ""&><',
        ]);

        $supportRequests = [$minimalSupportRequest, $longSupportRequest, $specialSupportRequest];

        foreach ($supportRequests as $supportRequest) {
            // When: The user views each support request
            $response = $this->apiAs($user, 'GET', route('support-request.show', $supportRequest));

            // Then: The request should succeed
            $response->assertStatus(200);
            $response->assertJsonStructure(['status', 'message', 'data' => ['supportRequest']]);

            $responseData = $response->json();
            $this->assertEquals($user->id, $responseData['data']['supportRequest']['user_id']);
            $this->assertEquals($supportRequest->id, $responseData['data']['supportRequest']['id']);
        }
    }
}