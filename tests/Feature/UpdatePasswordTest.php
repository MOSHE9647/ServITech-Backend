<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UpdatePasswordTest extends TestCase
{
    use RefreshDatabase; // Reset the database after each test

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
     * Test that an authenticated user can update their password.
     * This ensures that a valid old password and new password allow the user to update their password.
     */
    public function test_an_authenticated_user_can_update_their_password()
    {
        // Given: Valid old password and new password
        $data = [
            "old_password" => "password",
            "password" => "newpassword",
            "password_confirmation" => "newpassword",
        ];

        // When: The user attempts to update their password
        $user = User::find(1)->first(); // Find the user with ID 1
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'PUT', route('password.update'), $data);

        // Then: The response should return a 200 status with a success message
        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'status', 'message']);

        $user->refresh(); // Refresh the user instance to get the updated password
        $this->assertTrue(Hash::check($data['password'], $user->password)); // Verify the password was updated
    }

    /**
     * Test that the old password must be validated.
     * This ensures that an incorrect old password returns a 422 status with validation errors.
     */
    public function test_old_password_must_be_validated()
    {
        // Given: Incorrect old password
        $data = [
            "old_password" => "wrongpassword",
            "password" => "newpassword",
            "password_confirmation" => "newpassword",
        ];

        // When: The user attempts to update their password
        $user = User::find(1)->first(); // Find the user with ID 1
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'PUT', route('password.update'), $data);

        // Then: The response should return a 422 status with validation errors for the old password
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'status', 'message', 'errors' => ['old_password']
        ]);
        $response->assertJsonFragment([
            'old_password' => [
                __('validation.old_password', [
                    'attribute' => __('validation.attributes.old_password')
                ]),
            ]
        ]);
    }

    /**
     * Test that the old password is required.
     * This ensures that missing the old password returns a 422 status with validation errors.
     */
    public function test_old_password_must_be_required(): void
    {
        // Given: Missing old password
        $data = [
            "old_password" => "",
            "password" => "newpassword",
            "password_confirmation" => "newpassword",
        ];

        // When: The user attempts to update their password
        $user = User::find(1)->first(); // Find the user with ID 1
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'PUT', route('password.update'), $data);

        // Then: The response should return a 422 status with validation errors for the old password
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'status', 'message', 'errors' => ['old_password']
        ]);
    }

    /**
     * Test that the new password is required.
     * This ensures that missing the new password returns a 422 status with validation errors.
     */
    public function test_password_must_be_required(): void
    {
        // Given: Missing new password
        $data = [
            "old_password" => "password",
            "password" => "",
            "password_confirmation" => "newpassword",
        ];

        // When: The user attempts to update their password
        $user = User::find(1)->first(); // Find the user with ID 1
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'PUT', route('password.update'), $data);

        // Then: The response should return a 422 status with validation errors for the password field
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'status', 'message', 'errors' => ['password']
        ]);
    }

    /**
     * Test that the new password must have at least 8 characters.
     * This ensures that short passwords return a 422 status with validation errors.
     */
    public function test_password_must_have_at_least_8_characters(): void
    {
        // Given: Password with less than 8 characters
        $data = [
            "old_password" => "password",
            "password" => "newpass",
            "password_confirmation" => "newpass",
        ];

        // When: The user attempts to update their password
        $user = User::find(1)->first(); // Find the user with ID 1
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'PUT', route('password.update'), $data);

        // Then: The response should return a 422 status with validation errors for the password field
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'status', 'message', 'errors' => ['password']
        ]);
    }

    /**
     * Test that the password confirmation is required.
     * This ensures that missing the password confirmation returns a 422 status with validation errors.
     */
    public function test_password_confirmation_is_required(): void
    {
        // Given: Missing password confirmation
        $data = [
            "old_password" => "password",
            "password" => "newpassword",
            "password_confirmation" => "",
        ];

        // When: The user attempts to update their password
        $user = User::find(1)->first(); // Find the user with ID 1
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'PUT', route('password.update'), $data);

        // Then: The response should return a 422 status with validation errors for the password field
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'status', 'message', 'errors' => ['password']
        ]);
    }

    /**
     * Test that the new password must match the confirmation.
     * This ensures that mismatched passwords return a 422 status with validation errors.
     */
    public function test_password_must_match_confirmation(): void
    {
        // Given: Mismatched password and confirmation
        $data = [
            "old_password" => "password",
            "password" => "newpassword",
            "password_confirmation" => "newpassword123",
        ];

        // When: The user attempts to update their password
        $user = User::find(1)->first(); // Find the user with ID 1
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'PUT', route('password.update'), $data);

        // Then: The response should return a 422 status with validation errors for the password field
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'status', 'message', 'errors' => ['password']
        ]);
    }
}