<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
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
     * Helper method to send a reset password request.
     * This method simulates sending a reset password email and extracts the token and email from the notification.
     */
    public function sendResetPassword()
    {
        Notification::fake(); // Prevent notifications from being sent

        // Given: A valid email for a user
        $data = [
            'email' => 'example@example.com',
        ];

        // When: The user requests a password reset
        $response = $this->postJson(route('auth.send-reset-link'), $data);

        // Then: The response should return a 200 status with a success message
        $response->assertStatus(200);
        $response->assertJsonFragment(['message' => __('passwords.sent')]);

        $user = User::first();
        $this->assertNotNull($user, 'User does not exist.');

        // Assert that the notification was sent and extract the token and email
        Notification::assertSentTo([$user], function (ResetPasswordNotification $notification) {
            $url = $notification->url;

            $parts = parse_url($url); // Parse the URL to extract the query parameters
            parse_str($parts['query'], $query); // Parse the query string into an associative array

            $this->token = $query['token']; // Extract the token
            $this->email = urldecode($query['email']); // Extract the email

            // Assert that the token and email are present in the URL
            return strpos($url, 'reset-password?token=') !== false && strpos($url, 'email=') !== false;
        });
    }

    /**
     * Test that an existing user can reset their password successfully.
     * This ensures that a valid token and email allow the user to reset their password.
     */
    public function test_an_existing_user_can_reset_their_password(): void
    {
        // Given: A valid reset password request
        $this->sendResetPassword();

        // When: The user submits a new password
        $response = $this->putJson(route('auth.reset-password', ['token' => $this->token]), [
            'email' => $this->email,
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);

        // Then: The response should return a 200 status with a success message
        $response->assertStatus(200);
        $response->assertSeeText(__('passwords.reset'));

        $user = User::first();
        $this->assertNotNull($user, 'User does not exist.');
        $this->assertTrue(Hash::check('newpassword', $user->password));
    }

    /**
     * Test that the email field is required when requesting a password reset.
     * This ensures that missing email returns a 422 status with validation errors.
     */
    public function test_email_must_be_required(): void
    {
        // Given: Missing email in the request
        $data = [
            'email' => '',
        ];

        // When: The user attempts to request a password reset
        $response = $this->postJson(route('auth.send-reset-link'), $data);

        // Then: The response should return a 422 status with validation errors for the email field
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['email']]);
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
        // Given: An invalid email format
        $data = [
            'email' => 'notanemail',
        ];

        // When: The user attempts to request a password reset
        $response = $this->postJson(route('auth.send-reset-link'), $data);

        // Then: The response should return a 422 status with validation errors for the email field
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['email']]);
        $response->assertJsonFragment([
            'email' => [
                __('validation.email', [
                    'attribute' => __('validation.attributes.email')
                ])
            ]
        ]);
    }

    /**
     * Test that the email must exist in the database.
     * This ensures that non-existing emails return a 422 status with validation errors.
     */
    public function test_email_must_be_an_existing_email(): void
    {
        // Given: A non-existing email
        $data = [
            'email' => 'notexistingemail@example.com',
        ];

        // When: The user attempts to request a password reset
        $response = $this->postJson(route('auth.send-reset-link'), $data);

        // Then: The response should return a 422 status with validation errors for the email field
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['email']]);
        $response->assertJsonFragment([
            'email' => [
                __('validation.exists', [
                    'attribute' => __('validation.attributes.email')
                ])
            ]
        ]);
    }

    /**
     * Test that the password must be required when resetting the password.
     * This ensures that missing passwords return a 422 status with validation errors.
     */
    public function test_password_must_be_required(): void
    {
        // Given: A valid reset password request
        $this->sendResetPassword();

        // When: The user submits a request without a password
        $response = $this->putJson(route('auth.reset-password', ['token' => $this->token]), [
            'email' => $this->email,
            'password' => '',
            'password_confirmation' => 'newpassword',
        ]);

        // Then: The response should return a 422 status with validation errors for the password field
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['password']]);
    }
}