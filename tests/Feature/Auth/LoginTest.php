<?php

namespace Tests\Feature\Auth;

use Database\Seeders\DatabaseSeeder;
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
        $this->seed(DatabaseSeeder::class); // Seed the database with test users
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

        // Then: The response should be successful and include a token, user, and expiration time
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email'
                    ],
                    'token',
                    'expires_in'
                ]
            ])
            ->assertJsonFragment([
                'status' => 200,
                'message' => __('messages.user.logged_in')
            ]);
    }

    /**
     * Test that a non-existing user cannot log in.
     * This ensures that invalid credentials return a 400 status.
     */
    public function test_a_non_existing_user_cannot_login(): void
    {
        // Given: Invalid user credentials (non-existing email)
        $credentials = [
            'email' => 'example@nonexisting.com',
            'password' => 'assddrfegvfdg',
        ];

        // When: The user attempts to log in
        $response = $this->postJson(route('auth.login'), $credentials);
        // var_dump($response->json());

        // Then: The response should return a 400 status with user not found message
        $response->assertStatus(400)
            ->assertJsonStructure([
                'status',
                'message',
                'errors' => ['email']
            ])
            ->assertJsonFragment([
                'status' => 400,
                'message' => __('passwords.user')
            ])
            ->assertJsonPath('errors.email', __('passwords.user'));
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

        // Then: The response should return a 401 status with incorrect password message
        $response->assertStatus(401)
            ->assertJsonStructure([
                'status',
                'message',
                'errors' => ['password']
            ])
            ->assertJsonFragment([
                'status' => 401,
                'message' => __('auth.password')
            ])
            ->assertJsonPath('errors.password', __('auth.password'));
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
        $response->assertStatus(422)
            ->assertJsonStructure([
                'status',
                'message',
                'errors' => ['email']
            ])
            ->assertJsonPath('errors.email', __('validation.required', [
                'attribute' => __('validation.attributes.email')
            ]));
    }

    /**
     * Test that the email must be a valid email address.
     * This ensures that invalid email formats return a 422 status with validation errors.
     */
    public function test_email_must_be_a_valid_email(): void
    {
        // Given: Invalid email format
        $credentials = [
            'email' => 'invalid-email-format',
            'password' => 'password',
        ];

        // When: The user attempts to log in
        $response = $this->postJson(route('auth.login'), $credentials);

        // Then: The response should return a 422 status with validation errors for the email field
        $response->assertStatus(422)
            ->assertJsonStructure([
                'status',
                'message',
                'errors' => ['email']
            ])
            ->assertJsonPath('errors.email', __('validation.email', [
                'attribute' => __('validation.attributes.email')
            ]));
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
        $response->assertStatus(422)
            ->assertJsonStructure([
                'status',
                'message',
                'errors' => ['email']
            ]);

        // Verify it contains the string validation error
        $emailError = $response->json('errors.email');
        $this->assertEquals(__('validation.string', [
            'attribute' => __('validation.attributes.email')
        ]), $emailError);
    }

    /**
     * Test that the password field is required.
     * This ensures that missing password returns a 422 status with validation errors.
     */
    public function test_password_must_be_required(): void
    {
        // Given: Missing password in the credentials
        $credentials = [
            'email' => 'example@example.com',
        ];

        // When: The user attempts to log in
        $response = $this->postJson(route('auth.login'), $credentials);

        // Then: The response should return a 422 status with validation errors for the password field
        $response->assertStatus(422)
            ->assertJsonStructure([
                'status',
                'message',
                'errors' => ['password']
            ])
            ->assertJsonPath('errors.password', __('validation.required', [
                'attribute' => __('validation.attributes.password')
            ]));
    }

    /**
     * Test that the password must have at least 8 characters.
     * This ensures that short passwords return a 422 status with validation errors.
     */
    public function test_password_must_have_at_least_8_characters(): void
    {
        // Given: Password with less than 8 characters
        $credentials = [
            'email' => 'example@example.com',
            'password' => 'pass',
        ];

        // When: The user attempts to log in
        $response = $this->postJson(route('auth.login'), $credentials);

        // Then: The response should return a 422 status with validation errors for the password field
        $response->assertStatus(422)
            ->assertJsonStructure([
                'status',
                'message',
                'errors' => ['password']
            ])
            ->assertJsonPath('errors.password', __('validation.min.string', [
                'attribute' => __('validation.attributes.password'),
                'min' => 8,
            ]));
    }

    /**
     * Test that login response includes all required data fields.
     * This ensures the API returns consistent data structure for successful login.
     */
    public function test_successful_login_returns_complete_data_structure(): void
    {
        // Given: Valid user credentials
        $credentials = [
            'email' => 'example@example.com',
            'password' => 'password',
        ];

        // When: The user attempts to log in
        $response = $this->postJson(route('auth.login'), $credentials);

        // Then: The response should include all required fields with proper types
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
                    ],
                    'token',
                    'expires_in'
                ]
            ]);

        // Verify data types
        $responseData = $response->json('data');
        $this->assertIsString($responseData['token']);
        $this->assertIsInt($responseData['expires_in']);
        $this->assertGreaterThan(0, $responseData['expires_in']);
    }

    /**
     * Test that login is case-sensitive for email.
     * This ensures that email case matters for authentication.
     */
    public function test_login_is_case_sensitive_for_email(): void
    {
        // Given: Email with different case
        $credentials = [
            'email' => 'EXAMPLE@EXAMPLE.COM', // Different case
            'password' => 'password',
        ];

        // When: The user attempts to log in
        $response = $this->postJson(route('auth.login'), $credentials);

        // Then: The response should return a 400 status (user not found)
        $response->assertStatus(400)
            ->assertJsonFragment([
                'status' => 400,
                'message' => __('passwords.user')
            ]);
    }

    /**
     * Test that login validates empty string inputs properly.
     * This ensures empty strings are treated as missing fields.
     */
    public function test_empty_string_inputs_are_validated(): void
    {
        // Given: Empty string credentials
        $credentials = [
            'email' => '',
            'password' => '',
        ];

        // When: The user attempts to log in
        $response = $this->postJson(route('auth.login'), $credentials);

        // Then: The response should return validation errors for both fields
        $response->assertStatus(422)
            ->assertJsonStructure([
                'status',
                'message',
                'errors' => ['email', 'password']
            ]);
    }
}