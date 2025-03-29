<?php

namespace Tests\Feature;

use Database\Seeders\UserSeeder;
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
        $this->seed(UserSeeder::class); // Seed the database with test users
    }

    /**
     * Test that a user can log out successfully.
     * This ensures that a logged-in user can log out and receive a 200 status with a success message.
     */
    public function test_an_user_can_logout_successfully()
    {
        // Given: Valid user credentials
        $credentials = [
            "email"     => "example@example.com",
            "password"  => "password",
        ];

        // Log in the user
        $this->postJson(route('auth.login'), $credentials);

        // When: The user attempts to log out
        $response = $this->postJson(route('auth.logout'));

        // Then: The response should return a 200 status with a success message
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'message', 'data']);
        $response->assertJsonFragment([
            'status' => 200,
            'message' => __('messages.user.logged_out'),
        ]);
    }

    /**
     * Test that a user who is already logged out cannot log out again.
     * This ensures that attempting to log out without an active session returns a 401 status.
     */
    public function test_an_user_is_already_logged_out()
    {
        // Given: Valid user credentials
        $credentials = [
            "email"     => "example@example.com",
            "password"  => "password",
        ];

        // Log in and log out the user
        $this->postJson(route('auth.login'), $credentials);
        $this->postJson(route('auth.logout'));

        // When: The user attempts to log out again
        $response = $this->postJson(route('auth.logout'));

        // Then: The response should return a 401 status with an appropriate error message
        $response->assertStatus(401);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 401,
            'message' => __('messages.user.already_logged_out'),
        ]);
    }
}