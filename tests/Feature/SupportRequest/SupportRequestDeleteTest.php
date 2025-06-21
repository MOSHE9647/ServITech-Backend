<?php

namespace Tests\Feature\SupportRequest;

use App\Enums\UserRoles;
use App\Models\SupportRequest;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportRequestDeleteTest extends TestCase
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
     * Test that an authenticated user can delete their own support request.
     * This ensures that only the owner of the support request can delete it successfully.
     */
    public function test_an_authenticated_user_can_delete_their_own_support_request(): void
    {
        // Given: An authenticated user and their own support request
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $supportRequest = SupportRequest::factory()->create([
            'user_id' => $user->id,
            'date' => now()->format('Y-m-d'),
            'location' => 'Test Location for Delete',
            'detail' => 'Test detail for deletion test',
        ]);

        // When: The user attempts to delete their support request
        $response = $this->apiAs($user, 'DELETE', route('support-request.destroy', $supportRequest));

        // Then: The request should succeed, and the support request should be deleted from the database
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'message']);
        $response->assertJsonFragment([
            'message' => __('messages.common.deleted', ['item' => __('messages.entities.support_request.singular')])
        ]);

        // Verify the support request was soft deleted
        $this->assertSoftDeleted('support_requests', ['id' => $supportRequest->id]);
    }

    /**
     * Test that an authenticated admin user can delete their own support request.
     * This ensures that admin users can also delete their own support requests.
     */
    public function test_an_authenticated_admin_user_can_delete_their_own_support_request(): void
    {
        // Given: An authenticated admin user and their own support request
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $supportRequest = SupportRequest::factory()->create([
            'user_id' => $admin->id,
            'date' => now()->format('Y-m-d'),
            'location' => 'Admin Test Location',
            'detail' => 'Admin test detail for deletion',
        ]);

        // When: The admin attempts to delete their support request
        $response = $this->apiAs($admin, 'DELETE', route('support-request.destroy', $supportRequest));

        // Then: The request should succeed
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'message']);

        // Verify the support request was soft deleted
        $this->assertSoftDeleted('support_requests', ['id' => $supportRequest->id]);
    }

    /**
     * Test that a non-authenticated user cannot delete a support request.
     * This ensures that only authenticated users can delete support requests.
     */
    public function test_a_non_authenticated_user_cannot_delete_a_support_request(): void
    {
        // Given: An existing support request
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $supportRequest = SupportRequest::factory()->create([
            'user_id' => $user->id,
        ]);

        // When: A non-authenticated user attempts to delete the support request
        $response = $this->deleteJson(route('support-request.destroy', $supportRequest));

        // Then: The request should fail with a 401 Unauthorized status
        $response->assertStatus(401);
        $response->assertJsonStructure(['status', 'message']);

        // Verify the support request was not deleted
        $this->assertDatabaseHas('support_requests', [
            'id' => $supportRequest->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * Test that an authenticated user cannot delete another user's support request.
     * This ensures that users can only delete their own support requests.
     */
    public function test_an_authenticated_user_cannot_delete_another_users_support_request(): void
    {
        // Given: Two different users
        $user1 = User::role(UserRoles::USER)->first();
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user1, 'User 1 not found');
        $this->assertNotNull($admin, 'Admin user not found');
        $this->assertNotEquals($user1->id, $admin->id, 'Users should be different');

        // And: A support request belonging to the admin user
        $supportRequest = SupportRequest::factory()->create([
            'user_id' => $admin->id,
            'date' => now()->format('Y-m-d'),
            'location' => 'Admin Only Location',
            'detail' => 'This belongs to admin user',
        ]);

        // When: User1 attempts to delete Admin's support request
        $response = $this->apiAs($user1, 'DELETE', route('support-request.destroy', $supportRequest));

        // Then: The request should fail with a 404 Not Found status (for security reasons)
        $response->assertStatus(404);
        $response->assertJsonStructure(['status', 'message']);
        $response->assertJsonFragment([
            'message' => __('messages.common.not_found', ['item' => __('messages.entities.support_request.singular')])
        ]);

        // Verify the support request was not deleted
        $this->assertDatabaseHas('support_requests', [
            'id' => $supportRequest->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * Test that deleting a non-existent support request returns a 404 error.
     * This ensures that the API handles non-existent resources gracefully.
     */
    public function test_deleting_a_non_existent_support_request_returns_404(): void
    {
        // Given: An authenticated user and a non-existent support request ID
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $nonExistentSupportRequestId = 999999;

        // When: The user attempts to delete the non-existent support request
        $response = $this->apiAs($user, 'DELETE', route('support-request.destroy', $nonExistentSupportRequestId));

        // Then: The request should fail with a 404 Not Found status
        $response->assertStatus(404);
    }

    /**
     * Test that an admin cannot delete regular user's support requests.
     * This ensures that even admins can only delete their own support requests.
     */
    public function test_an_admin_cannot_delete_regular_users_support_request(): void
    {
        // Given: A regular user and an admin user
        $user = User::role(UserRoles::USER)->first();
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, 'User not found');
        $this->assertNotNull($admin, 'Admin user not found');

        // And: A support request belonging to the regular user
        $supportRequest = SupportRequest::factory()->create([
            'user_id' => $user->id,
            'date' => now()->format('Y-m-d'),
            'location' => 'User Only Location',
            'detail' => 'This belongs to regular user',
        ]);

        // When: Admin attempts to delete the user's support request
        $response = $this->apiAs($admin, 'DELETE', route('support-request.destroy', $supportRequest));

        // Then: The request should fail with a 404 Not Found status
        $response->assertStatus(404);
        $response->assertJsonStructure(['status', 'message']);

        // Verify the support request was not deleted
        $this->assertDatabaseHas('support_requests', [
            'id' => $supportRequest->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * Test that multiple support requests can be deleted independently.
     * This ensures that deletion of one request doesn't affect others.
     */
    public function test_multiple_support_requests_can_be_deleted_independently(): void
    {
        // Given: A user with multiple support requests
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $supportRequest1 = SupportRequest::factory()->create([
            'user_id' => $user->id,
            'location' => 'Location 1',
            'detail' => 'Detail 1',
        ]);

        $supportRequest2 = SupportRequest::factory()->create([
            'user_id' => $user->id,
            'location' => 'Location 2', 
            'detail' => 'Detail 2',
        ]);

        $supportRequest3 = SupportRequest::factory()->create([
            'user_id' => $user->id,
            'location' => 'Location 3',
            'detail' => 'Detail 3',
        ]);

        // When: User deletes the second support request
        $response = $this->apiAs($user, 'DELETE', route('support-request.destroy', $supportRequest2));

        // Then: Only the second request should be deleted
        $response->assertStatus(200);
        $this->assertSoftDeleted('support_requests', ['id' => $supportRequest2->id]);

        // And: The other requests should remain unaffected
        $this->assertDatabaseHas('support_requests', [
            'id' => $supportRequest1->id,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('support_requests', [
            'id' => $supportRequest3->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * Test that deleting already deleted support request returns 404.
     * This ensures that soft-deleted resources are properly handled.
     */
    public function test_deleting_already_deleted_support_request_returns_404(): void
    {
        // Given: A user with a support request that is already soft deleted
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $supportRequest = SupportRequest::factory()->create([
            'user_id' => $user->id,
            'date' => now()->format('Y-m-d'),
            'location' => 'Already Deleted Location',
            'detail' => 'This will be deleted first',
        ]);

        // First deletion
        $supportRequest->delete();
        $this->assertSoftDeleted('support_requests', ['id' => $supportRequest->id]);

        // When: User attempts to delete the same support request again
        $response = $this->apiAs($user, 'DELETE', route('support-request.destroy', $supportRequest->id));

        // Then: The request should fail with a 404 Not Found status
        $response->assertStatus(404);
    }

    /**
     * Test that successful deletion returns the correct message structure.
     * This ensures that the API returns the expected response format.
     */
    public function test_successful_deletion_returns_correct_message_structure(): void
    {
        // Given: A user with a support request
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $supportRequest = SupportRequest::factory()->create([
            'user_id' => $user->id,
            'date' => now()->format('Y-m-d'),
            'location' => 'Structure Test Location',
            'detail' => 'Testing response structure',
        ]);

        // When: User deletes the support request
        $response = $this->apiAs($user, 'DELETE', route('support-request.destroy', $supportRequest));        // Then: The response should have the correct structure and message
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data'
        ]);

        $response->assertJsonFragment([
            'status' => 200,
            'message' => __('messages.common.deleted', ['item' => __('messages.entities.support_request.singular')])
        ]);

        // Verify that data field is empty for delete operations
        $responseData = $response->json();
        $this->assertEmpty($responseData['data']);
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

        // When: A non-authenticated user attempts to delete any support request
        $response = $this->deleteJson(route('support-request.destroy', $supportRequest));

        // Then: Should get 401 Unauthorized, not 404 Not Found
        $response->assertStatus(401);
        $response->assertJsonStructure(['status', 'message']);

        // Verify the support request was not deleted
        $this->assertDatabaseHas('support_requests', [
            'id' => $supportRequest->id,
            'deleted_at' => null,
        ]);
    }
}