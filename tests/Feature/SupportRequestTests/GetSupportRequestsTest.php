<?php

namespace Tests\Feature\SupportRequestTests;

use App\Enums\UserRoles;
use App\Models\SupportRequest;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetSupportRequestsTest extends TestCase
{
    use RefreshDatabase; // Use RefreshDatabase to reset the database after each test

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class); // Seed the database with necessary data for the tests
    }

    /**
     * Test that an authenticated user can retrieve all support requests.
     * This ensures that only authenticated users can access the list of support requests.
     */
    public function test_an_authenticated_user_can_retrieve_all_support_requests(): void
    {
        // Given: An authenticated user and existing support requests in the database
        $user = User::role(UserRoles::USER)->first();
        SupportRequest::where("user_id", $user->id)->get();

        // When: The user attempts to retrieve all support requests
        $response = $this->apiAs($user, 'GET', route('support-request.index'));

        // Then: The request should succeed, and the response should contain the support requests
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'supportRequest' => [
                    '*' => [
                        'id',
                        'date',
                        'location',
                        'detail',
                        'user_id',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ],
        ]);
    }

    /**
     * Test that a non-authenticated user cannot retrieve support requests.
     * This ensures that only authenticated users can access the list of support requests.
     */
    public function test_a_non_authenticated_user_cannot_retrieve_support_requests(): void
    {
        // Given: Existing support requests in the database
        SupportRequest::inRandomOrder()->first();

        // When: A non-authenticated user attempts to retrieve all support requests
        $response = $this->getJson(route('support-request.index'));

        // Then: The request should fail with a 401 Unauthorized status
        $response->assertStatus(401);
        $response->assertJsonStructure(['status', 'message']);
    }

    /**
     * Test that the response contains only the support requests of the authenticated user.
     * This ensures that users can only access their own support requests.
     */
    public function test_authenticated_user_can_only_retrieve_their_own_support_requests(): void
    {
        // Given: Two users with their own support requests
        $user1 = User::role(UserRoles::USER)->first();
        $user2 = User::role(UserRoles::ADMIN)->first();

        // When: User1 attempts to retrieve support requests
        $response = $this->apiAs($user1, 'GET', route('support-request.index'));
        // dd($response->json());

        // Then: The response should contain only User1's support requests
        $response->assertStatus(200);
        $responseData = $response->json('data.supportRequests');

        // Assert that the response contains support requests for user1
        $this->assertTrue(collect($responseData)->contains('user_id', $user1->id));

        // Assert that the response does not contain support requests for user2
        $this->assertFalse(collect($responseData)->contains('user_id', $user2->id));
    }

    /**
     * Test that an authenticated user can retrieve a single support request.
     * This ensures that only authenticated users can access a specific support request.
     */
    public function test_an_authenticated_user_can_retrieve_a_single_support_request(): void
    {
        // Given: An authenticated user and an existing support request
        $user = User::role(UserRoles::USER)->first();
        $supportRequest = SupportRequest::where('user_id', $user->id)->first();
        $this->assertNotNull($supportRequest); // Ensure the support request exists

        // When: The user attempts to retrieve the support request
        $response = $this->apiAs($user, 'GET', route('support-request.show', $supportRequest));

        // Then: The request should succeed, and the response should contain the support request
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'supportRequest' => [
                    'id',
                    'date',
                    'location',
                    'detail',
                    'user_id',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);
        $response->assertJsonFragment([
            'id' => $supportRequest->id,
            'date' => $supportRequest->date,
            'location' => $supportRequest->location,
            'detail' => $supportRequest->detail,
            'user_id' => $supportRequest->user_id,
        ]);
    }

    /**
     * Test that a non-authenticated user cannot retrieve a single support request.
     * This ensures that only authenticated users can access a specific support request.
     */
    public function test_a_non_authenticated_user_cannot_retrieve_a_single_support_request(): void
    {
        // Given: An existing support request
        $supportRequest = SupportRequest::where('user_id', User::role(UserRoles::USER)->first()->id)->first();
        $this->assertNotNull($supportRequest); // Ensure the support request exists

        // When: A non-authenticated user attempts to retrieve the support request
        $response = $this->getJson(route('support-request.show', $supportRequest));

        // Then: The request should fail with a 401 Unauthorized status
        $response->assertStatus(401);
        $response->assertJsonStructure(['status', 'message']);
    }

    /**
     * Test that an authenticated user cannot retrieve another user's support request.
     * This ensures that users can only access their own support requests.
     */
    public function test_an_authenticated_user_cannot_retrieve_another_users_support_request(): void
    {
        // Given: Two users and a support request belonging to the second user
        $user1 = User::role(UserRoles::USER)->first();
        $user2 = User::role(UserRoles::ADMIN)->first();
        $supportRequest = SupportRequest::where('user_id', $user2->id)->first();

        // When: User1 attempts to retrieve User2's support request
        $response = $this->apiAs($user1, 'GET', route('support-request.show', $supportRequest));

        // Then: The request should fail with a 404 Not Found status
        $response->assertStatus(404);
        $response->assertJsonStructure(['status', 'message']);
    }

    /**
     * Test that retrieving a non-existent support request returns a 404 error.
     * This ensures that the API handles non-existent resources gracefully.
     */
    public function test_retrieving_a_non_existent_support_request_returns_404(): void
    {
        // Given: An authenticated user and a non-existent support request ID
        $user = User::role(UserRoles::USER)->first();
        $nonExistentSupportRequestId = 999;

        // When: The user attempts to retrieve the non-existent support request
        $response = $this->apiAs($user, 'GET', route('support-request.show', $nonExistentSupportRequestId));

        // Then: The request should fail with a 404 Not Found status
        $response->assertStatus(404);
        $response->assertJsonStructure(['status', 'message']);
    }
}