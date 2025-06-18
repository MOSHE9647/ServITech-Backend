<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use RefreshDatabase; // Reset the database after each test

    protected $token = '';
    protected $email = '';

    /**
     * Set up the test environment.
     * This method seeds the database before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class); // Seed the database with test users
    }

    /**
     * Helper method to send a reset password request and extract token.
     * This method simulates sending a reset password email and extracts the token for testing.
     */
    public function sendResetPasswordAndGetToken(): string
    {
        Notification::fake(); // Prevent actual notifications from being sent

        // Given: A valid email for a user
        $data = [
            'email' => 'example@example.com',
        ];

        // When: The user requests a password reset
        $response = $this->postJson(route('auth.send-reset-link'), $data);

        // Then: The response should be successful
        $response->assertStatus(200)
            ->assertJsonFragment(['message' => __('passwords.sent')]);

        $user = User::where('email', 'example@example.com')->first();
        $this->assertNotNull($user, 'Test user should exist in database');

        // Generate a valid password reset token for testing
        $token = Password::createToken($user);
        $this->token = $token;
        $this->email = $user->email;

        // Verify notification was sent
        Notification::assertSentTo([$user], ResetPasswordNotification::class);

        return $token;
    }

    /**
     * Test that a user can successfully request a password reset link.
     * This ensures that valid email addresses receive password reset links.
     */
    public function test_user_can_request_password_reset_link(): void
    {
        // Given: A valid email for an existing user
        $data = [
            'email' => 'example@example.com',
        ];

        // When: The user requests a password reset
        $response = $this->postJson(route('auth.send-reset-link'), $data);

        // Then: The response should be successful with proper message
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data'
            ])
            ->assertJsonFragment([
                'status' => 200,
                'message' => __('passwords.sent')
            ]);
    }

    /**
     * Test that an existing user can reset their password successfully.
     * This ensures that a valid token and email allow the user to reset their password.
     */
    public function test_an_existing_user_can_reset_their_password(): void
    {
        // Given: A valid reset password token
        $token = $this->sendResetPasswordAndGetToken();
        $newPassword = 'newSecurePassword123';

        // When: The user submits a new password with valid data
        $response = $this->putJson(route('auth.reset-password'), [
            'email' => $this->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
            'token' => $token,
        ]);

        // Then: The response should return a 200 status and show the reset view
        $response->assertStatus(200)
            ->assertViewIs('auth.reset-password')
            ->assertSeeText(__('passwords.reset'));

        // And: The user's password should be updated in the database
        $user = User::where('email', $this->email)->first();
        $this->assertNotNull($user, 'User should exist in database');
        $this->assertTrue(Hash::check($newPassword, $user->password), 'Password should be updated');
    }

    /**
     * Test password reset with invalid token.
     * This ensures that invalid tokens are rejected properly.
     */
    public function test_password_reset_with_invalid_token_fails(): void
    {
        // Given: An invalid token
        $invalidToken = 'invalid-token-12345';
        
        // When: The user attempts to reset password with invalid token
        $response = $this->putJson(route('auth.reset-password'), [
            'email' => 'example@example.com',
            'password' => 'newPassword123',
            'password_confirmation' => 'newPassword123',
            'token' => $invalidToken,
        ]);

        // Then: The response should show token error
        $response->assertStatus(200)
            ->assertViewIs('auth.reset-password')
            ->assertSeeText(__('passwords.token'));
    }

    /**
     * Test password reset with non-existing user.
     * This ensures that non-existing users cannot reset passwords.
     */
    public function test_password_reset_with_non_existing_user_fails(): void
    {
        // Given: A valid token but non-existing email
        $token = $this->sendResetPasswordAndGetToken();
        
        // When: The user attempts to reset password with non-existing email
        $response = $this->putJson(route('auth.reset-password'), [
            'email' => 'nonexisting@example.com',
            'password' => 'newPassword123',
            'password_confirmation' => 'newPassword123',
            'token' => $token,
        ]);

        // Then: The response should show user error
        $response->assertStatus(200)
            ->assertViewIs('auth.reset-password')
            ->assertSeeText(__('passwords.user'));
    }

    // Send Reset Link Tests

    /**
     * Test that the email field is required when requesting a password reset.
     * This ensures that missing email returns a 422 status with validation errors.
     */
    public function test_email_must_be_required_for_reset_link(): void
    {
        // Given: Missing email in the request
        $data = [];

        // When: The user attempts to request a password reset
        $response = $this->postJson(route('auth.send-reset-link'), $data);

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
    public function test_email_must_be_a_valid_email_for_reset_link(): void
    {
        // Given: An invalid email format
        $data = [
            'email' => 'invalid-email-format',
        ];

        // When: The user attempts to request a password reset
        $response = $this->postJson(route('auth.send-reset-link'), $data);

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
     * Test that the email must exist in the database.
     * This ensures that non-existing emails return a 422 status with validation errors.
     */
    public function test_email_must_be_an_existing_email_for_reset_link(): void
    {
        // Given: A non-existing email
        $data = [
            'email' => 'nonexistent@example.com',
        ];

        // When: The user attempts to request a password reset
        $response = $this->postJson(route('auth.send-reset-link'), $data);

        // Then: The response should return a 422 status with validation errors for the email field
        $response->assertStatus(422)
            ->assertJsonStructure([
                'status', 
                'message', 
                'errors' => ['email']
            ])
            ->assertJsonPath('errors.email', __('validation.exists', [
                'attribute' => __('validation.attributes.email')
            ]));
    }

    // Reset Password Validation Tests

    /**
     * Test that the email field is required when resetting password.
     * This ensures that missing email returns a 422 status with validation errors.
     */
    public function test_email_must_be_required_for_password_reset(): void
    {
        // Given: A valid token but missing email
        $token = $this->sendResetPasswordAndGetToken();

        // When: The user submits a request without an email
        $response = $this->postJson(route('auth.reset-password'), [
            'password' => 'newPassword123',
            'password_confirmation' => 'newPassword123',
            'token' => $token,
        ]);

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
     * Test that the password field is required when resetting password.
     * This ensures that missing passwords return a 422 status with validation errors.
     */
    public function test_password_must_be_required_for_password_reset(): void
    {
        // Given: A valid token but missing password
        $token = $this->sendResetPasswordAndGetToken();

        // When: The user submits a request without a password
        $response = $this->putJson(route('auth.reset-password'), [
            'email' => $this->email,
            'password_confirmation' => 'newPassword123',
            'token' => $token,
        ]);

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
     * Test that the password confirmation must match.
     * This ensures that mismatched passwords return validation errors.
     */
    public function test_password_confirmation_must_match(): void
    {
        // Given: A valid token but mismatched passwords
        $token = $this->sendResetPasswordAndGetToken();

        // When: The user submits passwords that don't match
        $response = $this->putJson(route('auth.reset-password'), [
            'email' => $this->email,
            'password' => 'newPassword123',
            'password_confirmation' => 'differentPassword456',
            'token' => $token,
        ]);

        // Then: The response should return a 422 status with validation errors for the password field
        $response->assertStatus(422)
            ->assertJsonStructure([
                'status', 
                'message', 
                'errors' => ['password']
            ])
            ->assertJsonPath('errors.password', __('validation.confirmed', [
                'attribute' => __('validation.attributes.password')
            ]));
    }

    /**
     * Test that the password must have at least 8 characters.
     * This ensures that short passwords return validation errors.
     */
    public function test_password_must_have_minimum_length(): void
    {
        // Given: A valid token but short password
        $token = $this->sendResetPasswordAndGetToken();

        // When: The user submits a password that's too short
        $response = $this->putJson(route('auth.reset-password'), [
            'email' => $this->email,
            'password' => 'short',
            'password_confirmation' => 'short',
            'token' => $token,
        ]);

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
     * Test that the token field is required when resetting password.
     * This ensures that missing token returns validation errors.
     */
    public function test_token_must_be_required_for_password_reset(): void
    {
        // Given: Valid email and password but missing token
        // When: The user submits a request without a token
        $response = $this->putJson(route('auth.reset-password'), [
            'email' => 'example@example.com',
            'password' => 'newPassword123',
            'password_confirmation' => 'newPassword123',
        ]);

        // Then: The response should return a 422 status with validation errors for the token field
        $response->assertStatus(422)
            ->assertJsonStructure([
                'status', 
                'message', 
                'errors' => ['token']
            ])
            ->assertJsonPath('errors.token', __('validation.required', [
                'attribute' => 'token'
            ]));
    }

    /**
     * Test that password reset link request fails for server errors.
     * This simulates server-side issues when sending reset links.
     */
    public function test_password_reset_link_fails_on_server_error(): void
    {
        // Mock the Password facade to simulate a server error
        Password::shouldReceive('sendResetLink')
            ->once()
            ->with(['email' => 'example@example.com'])
            ->andReturn(Password::RESET_THROTTLED); // Simulate a failure status

        // Given: A valid email for an existing user
        $data = ['email' => 'example@example.com'];

        // When: The user requests a password reset and server fails
        $response = $this->postJson(route('auth.send-reset-link'), $data);

        // Then: The response should return a 500 status with error message
        $response->assertStatus(500)
            ->assertJsonStructure([
                'status',
                'message'
            ])
            ->assertJsonFragment([
                'status' => 500
            ]);

        // Verify that the error message indicates a server failure
        $responseData = $response->json();
        $this->assertStringContainsString(__('passwords.not_sent'), $responseData['message']);
    }

    /**
     * Test that password reset notifications are sent correctly.
     * This ensures that the notification system works properly.
     */
    public function test_password_reset_notification_is_sent(): void
    {
        Notification::fake();

        // Given: A valid email for an existing user
        $user = User::where('email', 'example@example.com')->first();
        $data = ['email' => $user->email];

        // When: The user requests a password reset
        $response = $this->postJson(route('auth.send-reset-link'), $data);

        // Then: The response should be successful and notification should be sent
        $response->assertStatus(200);

        // Verify that the notification contains a token
        Notification::assertSentTo(
            [$user], 
            ResetPasswordNotification::class, 
            fn ($notification) => $notification->url && $notification->via($user) === ['mail']
        );

        // Also verify that exactly one notification was sent
        Notification::assertSentToTimes($user, ResetPasswordNotification::class, 1);
    }
}