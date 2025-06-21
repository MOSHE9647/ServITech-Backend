<?php

namespace Tests\Feature\User;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Set up the test environment.
     * This method seeds the database before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
    }

    /**
     * Test that an authenticated user can retrieve their profile.
     * This ensures that the profile endpoint returns the correct user data.
     */
    public function test_authenticated_user_can_retrieve_profile(): void
    {
        // Given: An authenticated user
        $user = User::where('email', 'example@example.com')->first();
        $this->assertNotNull($user, 'Test user should exist');

        // When: The user requests their profile
        $response = $this->apiAs($user, 'GET', route('user.profile'));

        // Then: The response should return a 200 status with user data
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'role',
                        'name',
                        'email',
                        'phone'
                    ]
                ]
            ])
            ->assertJsonFragment([
                'status' => 200,
                'message' => __('messages.user.info_retrieved')
            ])
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.email', $user->email)
            ->assertJsonPath('data.user.name', $user->name)
            ->assertJsonPath('data.user.phone', $user->phone)
            ->assertJsonPath('data.user.role', 'user');
    }

    /**
     * Test that unauthenticated users cannot access profile.
     * This ensures that authentication is required for profile access.
     */
    public function test_unauthenticated_user_cannot_access_profile(): void
    {
        // When: An unauthenticated user attempts to access profile
        $response = $this->getJson(route('user.profile'));

        // Then: The response should return a 401 status (Unauthorized)
        $response->assertStatus(401);
    }

    /**
     * Test that profile endpoint only accepts GET method.
     * This ensures that only GET requests are allowed for profile retrieval.
     */
    public function test_profile_endpoint_only_accepts_get_method(): void
    {
        // Given: An authenticated user
        $user = User::where('email', 'example@example.com')->first();

        // When: Attempting to use POST method on profile endpoint
        $response = $this->apiAs($user, 'POST', route('user.profile'));

        // Then: The response should return a 405 status (Method Not Allowed)
        $response->assertStatus(405);
    }

    /**
     * Test that profile returns UserResource structure.
     * This ensures that the profile data follows the UserResource format.
     */
    public function test_profile_returns_user_resource_structure(): void
    {
        // Given: An authenticated user
        $user = User::where('email', 'example@example.com')->first();

        // When: The user requests their profile
        $response = $this->apiAs($user, 'GET', route('user.profile'));

        // Then: The response should contain exactly the UserResource fields
        $response->assertStatus(200);

        $userData = $response->json('data.user');
        $expectedFields = ['id', 'role', 'name', 'email', 'phone'];
        $actualFields = array_keys($userData);

        $this->assertEquals($expectedFields, $actualFields);
    }

    /**
     * Test that profile does not expose sensitive data.
     * This ensures that sensitive fields are not included in the profile response.
     */
    public function test_profile_does_not_expose_sensitive_data(): void
    {
        // Given: An authenticated user
        $user = User::where('email', 'example@example.com')->first();

        // When: The user requests their profile
        $response = $this->apiAs($user, 'GET', route('user.profile'));

        // Then: The response should not contain sensitive information
        $response->assertStatus(200);

        $userData = $response->json('data.user');

        // Verify sensitive fields are not exposed
        $this->assertArrayNotHasKey('password', $userData);
        $this->assertArrayNotHasKey('remember_token', $userData);
        $this->assertArrayNotHasKey('email_verified_at', $userData);
        $this->assertArrayNotHasKey('created_at', $userData);
        $this->assertArrayNotHasKey('updated_at', $userData);
        $this->assertArrayNotHasKey('deleted_at', $userData);
    }
}
