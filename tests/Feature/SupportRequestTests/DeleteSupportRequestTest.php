<?php

namespace Tests\Feature\SupportRequestTests;

use App\Enums\UserRoles;
use App\Models\SupportRequest;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteSupportRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class); // Seed the database with necessary data
    }

    /**
     * Test that an authenticated user can delete their own support request.
     * This ensures that only the owner of the support request can delete it successfully.
     */
    public function test_an_authenticated_user_can_delete_their_own_support_request(): void
    {
        // Given: An authenticated user and their own support request
        $user = User::role(UserRoles::USER)->first();
        $supportRequest = SupportRequest::where('user_id', $user->id)->first();
        $this->assertNotNull($supportRequest); // Ensure the support request exists

        // When: The user attempts to delete their support request
        $response = $this->apiAs($user, 'DELETE', route('support-request.destroy', $supportRequest));

        // Then: The request should succeed, and the support request should be deleted from the database
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'message']);
        $this->assertSoftDeleted($supportRequest);
    }

    /**
     * Test that a non-authenticated user cannot delete a support request.
     * This ensures that only authenticated users can delete support requests.
     */
    public function test_a_non_authenticated_user_cannot_delete_a_support_request(): void
    {
        // Given: An existing support request
        $supportRequest = SupportRequest::factory()->create();

        // When: A non-authenticated user attempts to delete the support request
        $response = $this->deleteJson(route('support-request.destroy', $supportRequest));

        // Then: The request should fail with a 401 Unauthorized status
        $response->assertStatus(401);
        $response->assertJsonStructure(['status', 'message']);
        $this->assertDatabaseHas('support_requests', ['id' => $supportRequest->id]);
    }

    /**
     * Test that an authenticated user cannot delete another user's support request.
     * This ensures that users can only delete their own support requests.
     */
    public function test_an_authenticated_user_cannot_delete_another_users_support_request(): void
    {
        // Given: Two users and a support request belonging to the second user
        $user1 = User::role(UserRoles::USER)->first();
        $user2 = User::role(UserRoles::ADMIN)->first();
        $supportRequest = SupportRequest::factory()->create(['user_id' => $user2->id]);

        // When: User1 attempts to delete User2's support request
        $response = $this->apiAs($user1, 'DELETE', route('support-request.destroy', $supportRequest));

        // Then: The request should fail with a 404 Nor Found status
        $response->assertStatus(404);
        $response->assertJsonStructure(['status', 'message']);
        $this->assertDatabaseHas('support_requests', ['id' => $supportRequest->id]);
    }

    /**
     * Test that deleting a non-existent support request returns a 404 error.
     * This ensures that the API handles non-existent resources gracefully.
     */
    public function test_deleting_a_non_existent_support_request_returns_404(): void
    {
        // Given: An authenticated user and a non-existent support request ID
        $user = User::factory()->create();
        $nonExistentSupportRequestId = 999;

        // When: The user attempts to delete the non-existent support request
        $response = $this->apiAs($user, 'DELETE', route('support-request.destroy', $nonExistentSupportRequestId));

        // Then: The request should fail with a 404 Not Found status
        $response->assertStatus(404);
        $response->assertJsonStructure(['status', 'message']);
    }
}