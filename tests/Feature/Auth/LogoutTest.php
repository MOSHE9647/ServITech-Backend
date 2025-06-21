<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase; // Reset the database after each test

    /**
     * Set up the test environment.
     * This method seeds the database before each test.
     */
    protected function setUp(): void
    {
        parent::setUp(); // Call the parent setUp method
        $this->seed(DatabaseSeeder::class); // Seed the database with test users
    }

    /**
     * Test that an authenticated user can log out successfully.
     * This ensures that a logged-in user can log out and receive a 200 status with a success message.
     */
    public function test_an_authenticated_user_can_logout_successfully(): void
    {
        // Given: An authenticated user
        $user = User::where('email', 'example@example.com')->first();
        $this->assertNotNull($user, 'Test user should exist in database');

        // When: The authenticated user attempts to log out
        $response = $this->apiAs($user, 'POST', route('auth.logout'));

        // Then: The response should return a 200 status with a success message
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data'
            ])
            ->assertJsonFragment([
                'status' => 200,
                'message' => __('messages.user.logged_out'),
            ]);
    }

    /**
     * Test that an unauthenticated user cannot log out.
     * This ensures that attempting to log out without authentication returns a 401 status.
     */
    public function test_an_unauthenticated_user_cannot_logout(): void
    {
        // Given: No authenticated user (no authorization header)

        // When: An unauthenticated request attempts to log out
        $response = $this->postJson(route('auth.logout'));

        // Then: The response should return a 401 status with an authentication error
        $response->assertStatus(401)
            ->assertJsonStructure([
                'status',
                'message'
            ])
            ->assertJsonFragment([
                'status' => 401,
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * Test that logout requires proper authentication middleware.
     * This ensures that the logout endpoint is protected by authentication middleware.
     */
    public function test_logout_requires_authentication_middleware(): void
    {
        // Given: A request without JWT token

        // When: Attempting to access logout endpoint without proper authentication
        $response = $this->postJson(route('auth.logout'), [], [
            'Authorization' => 'Bearer invalid-token'
        ]);

        // Then: The response should return a 401 status indicating authentication failure
        $response->assertStatus(401);
    }

    /**
     * Test logout with an expired token.
     * This ensures that expired tokens are handled properly.
     */
    public function test_logout_with_expired_token_fails(): void
    {
        // Given: A user with an expired token (we simulate this by using an invalid token)
        $user = User::where('email', 'example@example.com')->first();

        // When: Attempting to logout with an invalid/expired token
        $response = $this->postJson(route('auth.logout'), [], [
            'Authorization' => 'Bearer expired.jwt.token'
        ]);

        // Then: The response should return a 401 status
        $response->assertStatus(401);
    }

    /**
     * Test that the logout response has the correct structure.
     * This ensures the API returns consistent response structure.
     */
    public function test_successful_logout_returns_correct_response_structure(): void
    {
        // Given: An authenticated user
        $user = User::where('email', 'example@example.com')->first();

        // When: The user logs out successfully
        $response = $this->apiAs($user, 'POST', route('auth.logout'));

        // Then: The response should have the correct structure with all required fields
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data'
            ])
            ->assertJson([
                'status' => 200,
                'message' => __('messages.user.logged_out'),
                'data' => null
            ]);
    }

    /**
     * Test that logout invalidates the JWT token properly.
     * This ensures that after logout, subsequent requests fail due to token invalidation.
     */
    public function test_logout_invalidates_jwt_token(): void
    {
        // Given: An authenticated user who logs in first to get a valid token
        $credentials = [
            'email' => 'example@example.com',
            'password' => 'password',
        ];

        $loginResponse = $this->postJson(route('auth.login'), $credentials);
        $loginResponse->assertStatus(200);

        $token = $loginResponse->json('data.token');
        $this->assertNotNull($token);

        // When: The user logs out using the valid token
        $logoutResponse = $this->postJson(route('auth.logout'), [], [
            'Authorization' => 'Bearer ' . $token
        ]);

        // Then: The logout should be successful
        $logoutResponse->assertStatus(200);

        // And: Attempting to use the same token for another logout should fail
        $secondLogoutResponse = $this->postJson(route('auth.logout'), [], [
            'Authorization' => 'Bearer ' . $token
        ]);
        $secondLogoutResponse->assertStatus(401);
    }
}