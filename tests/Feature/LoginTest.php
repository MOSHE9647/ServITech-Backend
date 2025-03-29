<?php

namespace Tests\Feature;

use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
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
     * Test that an existing user can log in successfully.
     * This ensures that valid credentials return a 200 status and a token.
     */
    public function test_an_existing_user_can_login(): void
    {
        // Given: Valid user credentials
        $credentials = [
            'email' => 'example@example.com',
            'password' => 'password',
        ];

        // When: The user attempts to log in
        $response = $this->postJson(route('auth.login'), $credentials);

        // Then: The response should be successful and include a token
        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => ['token']]);
    }

    /**
     * Test that a non-existing user cannot log in.
     * This ensures that invalid credentials return a 401 status.
     */
    public function test_a_non_existing_user_cannot_login(): void
    {
        // Given: Invalid user credentials
        $credentials = [
            'email' => 'example@nonexisting.com',
            'password' => 'assddrfegvfdg',
        ];

        // When: The user attempts to log in
        $response = $this->postJson(route('auth.login'), $credentials);

        // Then: The response should return a 401 status with an appropriate error message
        $response->assertStatus(401);
        $response->assertJsonFragment(['status' => 401, 'message' => __('passwords.user')]);
    }

    /**
     * Test that an existing user cannot log in with an incorrect password.
     * This ensures that invalid credentials return a 401 status.
     */
    public function test_an_existing_user_use_a_wrong_password(): void
    {
        // Given: Valid email but incorrect password
        $credentials = [
            'email' => 'example@example.com',
            'password' => 'assddrfegvfdg',
        ];

        // When: The user attempts to log in
        $response = $this->postJson(route('auth.login'), $credentials);

        // Then: The response should return a 401 status with an invalid credentials message
        $response->assertStatus(401);
        $response->assertJsonFragment(['status' => 401, 'message' => __('messages.user.invalid_credentials')]);
    }

    /**
     * Test that the email field is required.
     * This ensures that missing email returns a 422 status with validation errors.
     */
    public function test_email_must_be_required(): void
    {
        // Given: Missing email in the credentials
        $credentials = [
            'password' => 'password',
        ];

        // When: The user attempts to log in
        $response = $this->postJson(route('auth.login'), $credentials);

        // Then: The response should return a 422 status with validation errors for the email field
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'status', 'message', 'errors' => ['email']
        ]);
        $response->assertJsonFragment([
            'email' => [
                __('validation.required', [
                    'attribute' => __('validation.attributes.email')
                ])
            ]
        ]);
    }

    /**
     * Test that the email must be a valid email address.
     * This ensures that invalid email formats return a 422 status with validation errors.
     */
    public function test_email_must_be_a_valid_email(): void
    {
        // Given: Invalid email format
        $credentials = [
            'email' => 'email',
            'password' => 'password',
        ];

        // When: The user attempts to log in
        $response = $this->postJson(route('auth.login'), $credentials);

        // Then: The response should return a 422 status with validation errors for the email field
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'status', 'message', 'errors' => ['email']
        ]);
        $response->assertJsonFragment([
            'email' => [
                __('validation.email', [
                    'attribute' => __('validation.attributes.email')
                ])
            ]
        ]);
    }

    /**
     * Test that the email must be a string.
     * This ensures that non-string email values return a 422 status with validation errors.
     */
    public function test_email_must_be_a_string(): void
    {
        // Given: Non-string email value
        $credentials = [
            'email' => 1234567890,
            'password' => 'password',
        ];

        // When: The user attempts to log in
        $response = $this->postJson(route('auth.login'), $credentials);

        // Then: The response should return a 422 status with validation errors for the email field
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'status', 'message', 'errors' => ['email']
        ]);
        $response->assertJsonFragment([
            'email' => [
                __('validation.string', [
                    'attribute' => __('validation.attributes.email')
                ]),
                __('validation.email', [
                    'attribute' => __('validation.attributes.email')
                ])
            ]
        ]);
    }

    /**
     * Test that the password field is required.
     * This ensures that missing password returns a 422 status with validation errors.
     */
    public function test_password_must_be_required(): void
    {
        // Given: Missing password in the credentials
        $credentials = [
            'email' => 'example@nonexisting.com',
        ];

        // When: The user attempts to log in
        $response = $this->postJson(route('auth.login'), $credentials);

        // Then: The response should return a 422 status with validation errors for the password field
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'status', 'message', 'errors' => ['password']
        ]);
        $response->assertJsonFragment([
            'password' => [
                __('validation.required', [
                    'attribute' => __('validation.attributes.password')
                ]),
            ]
        ]);
    }

    /**
     * Test that the password must have at least 8 characters.
     * This ensures that short passwords return a 422 status with validation errors.
     */
    public function test_password_must_have_at_least_8_characters(): void
    {
        // Given: Password with less than 8 characters
        $credentials = [
            'email' => 'example@nonexisting.com',
            'password' => 'pass',
        ];

        // When: The user attempts to log in
        $response = $this->postJson(route('auth.login'), $credentials);

        // Then: The response should return a 422 status with validation errors for the password field
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'status', 'message', 'errors' => ['password']
        ]);
        $response->assertJsonFragment([
            'password' => [
                __('validation.min.string', [
                    'attribute' => __('validation.attributes.password'),
                    'min' => 8,
                ]),
            ]
        ]);
    }
}