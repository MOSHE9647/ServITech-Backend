<?php

namespace Tests\Feature\User;

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
    public function test_an_authenticated_user_can_update_their_password(): void
    {
        // Given: Valid old password and new password
        $data = [
            "old_password" => "password",
            "password" => "newSecurePassword123",
            "password_confirmation" => "newSecurePassword123",
        ];

        // When: The user attempts to update their password
        $user = User::where('email', 'example@example.com')->first();
        $this->assertNotNull($user, 'Test user should exist');

        $response = $this->apiAs($user, 'PUT', route('user.password.update'), $data);

        // Then: The response should return a 200 status with a success message
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data'
            ])
            ->assertJsonFragment([
                'status' => 200,
                'message' => __('messages.password.updated')
            ]);

        // And: The password should be updated in the database
        $user->refresh();
        $this->assertTrue(Hash::check($data['password'], $user->password));
    }

    /**
     * Test that unauthenticated users cannot update password.
     * This ensures that authentication is required for password updates.
     */
    public function test_unauthenticated_user_cannot_update_password(): void
    {
        // Given: Valid password data but no authentication
        $data = [
            "old_password" => "password",
            "password" => "newSecurePassword123",
            "password_confirmation" => "newSecurePassword123",
        ];

        // When: An unauthenticated user attempts to update password
        $response = $this->putJson(route('user.password.update'), $data);

        // Then: The response should return a 401 status (Unauthorized)
        $response->assertStatus(401);
    }

    /**
     * Test that multiple password updates work correctly.
     * This ensures that users can update their password multiple times.
     */
    public function test_multiple_password_updates_work_correctly(): void
    {
        // Given: A user
        $user = User::where('email', 'example@example.com')->first();

        // When: The user updates their password the first time
        $firstUpdate = [
            "old_password" => "password",
            "password" => "firstNewPassword123",
            "password_confirmation" => "firstNewPassword123",
        ];

        $response1 = $this->apiAs($user, 'PUT', route('user.password.update'), $firstUpdate);
        $response1->assertStatus(200);

        // And: The user updates their password again
        $secondUpdate = [
            "old_password" => "firstNewPassword123",
            "password" => "secondNewPassword456",
            "password_confirmation" => "secondNewPassword456",
        ];

        $response2 = $this->apiAs($user, 'PUT', route('user.password.update'), $secondUpdate);

        // Then: Both updates should be successful
        $response2->assertStatus(200);

        // And: The final password should be the second one
        $user->refresh();
        $this->assertTrue(Hash::check($secondUpdate['password'], $user->password));
        $this->assertFalse(Hash::check($firstUpdate['password'], $user->password));
        $this->assertFalse(Hash::check('password', $user->password));
    }

    /**
     * Test that the old password must be validated.
     * This ensures that an incorrect old password returns a 422 status with validation errors.
     */
    public function test_old_password_must_be_validated(): void
    {
        // Given: Incorrect old password
        $data = [
            "old_password" => "wrongpassword",
            "password" => "newSecurePassword123",
            "password_confirmation" => "newSecurePassword123",
        ];

        // When: The user attempts to update their password
        $user = User::where('email', 'example@example.com')->first();
        $this->assertNotNull($user, 'Test user should exist');

        $response = $this->apiAs($user, 'PUT', route('user.password.update'), $data);

        // Then: The response should return a 422 status with validation errors for the old password
        $response->assertStatus(422)
            ->assertJsonStructure([
                'status', 
                'message', 
                'errors' => ['old_password']
            ])
            ->assertJsonPath(
                'errors.old_password', 
                __('validation.old_password', [
                    'attribute' => __('validation.attributes.old_password')
                ])
            );
    }

    /**
     * Test that the old password is required.
     * This ensures that missing the old password returns a 422 status with validation errors.
     */
    public function test_old_password_must_be_required(): void
    {
        // Given: Missing old password
        $data = [
            "password" => "newSecurePassword123",
            "password_confirmation" => "newSecurePassword123",
        ];

        // When: The user attempts to update their password
        $user = User::where('email', 'example@example.com')->first();
        $this->assertNotNull($user, 'Test user should exist');

        $response = $this->apiAs($user, 'PUT', route('user.password.update'), $data);

        // Then: The response should return a 422 status with validation errors for the old password
        $response->assertStatus(422)
            ->assertJsonStructure([
                'status', 
                'message', 
                'errors' => ['old_password']
            ])
            ->assertJsonPath(
                'errors.old_password', 
                __('validation.required', [
                    'attribute' => __('validation.attributes.old_password')
                ])
            );
    }

    /**
     * Test that old password must have at least 8 characters.
     * This ensures that short old passwords return validation errors.
     */
    public function test_old_password_must_have_at_least_8_characters(): void
    {
        // Given: Old password with less than 8 characters
        $data = [
            "old_password" => "short",
            "password" => "newSecurePassword123",
            "password_confirmation" => "newSecurePassword123",
        ];

        // When: The user attempts to update their password
        $user = User::where('email', 'example@example.com')->first();
        $this->assertNotNull($user, 'Test user should exist');

        $response = $this->apiAs($user, 'PUT', route('user.password.update'), $data);

        // Then: The response should return a 422 status with validation errors for the old password field
        $response->assertStatus(422)
            ->assertJsonStructure([
                'status', 
                'message', 
                'errors' => ['old_password']
            ])
            ->assertJsonPath('errors.old_password', __('validation.min.string', [
                'attribute' => __('validation.attributes.old_password'),
                'min' => 8,
            ]));
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
            "password_confirmation" => "newSecurePassword123",
        ];

        // When: The user attempts to update their password
        $user = User::where('email', 'example@example.com')->first();
        $this->assertNotNull($user, 'Test user should exist');

        $response = $this->apiAs($user, 'PUT', route('user.password.update'), $data);

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
     * Test that the new password must have at least 8 characters.
     * This ensures that short passwords return a 422 status with validation errors.
     */
    public function test_password_must_have_at_least_8_characters(): void
    {
        // Given: Password with less than 8 characters
        $data = [
            "old_password" => "password",
            "password" => "short",
            "password_confirmation" => "short",
        ];

        // When: The user attempts to update their password
        $user = User::where('email', 'example@example.com')->first();
        $this->assertNotNull($user, 'Test user should exist');

        $response = $this->apiAs($user, 'PUT', route('user.password.update'), $data);

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
     * Test that the password confirmation is required.
     * This ensures that missing the password confirmation returns a 422 status with validation errors.
     */
    public function test_password_confirmation_is_required(): void
    {
        // Given: Missing password confirmation
        $data = [
            "old_password" => "password",
            "password" => "newSecurePassword123",
        ];

        // When: The user attempts to update their password
        $user = User::where('email', 'example@example.com')->first();
        $this->assertNotNull($user, 'Test user should exist');

        $response = $this->apiAs($user, 'PUT', route('user.password.update'), $data);

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
     * Test that the new password must match the confirmation.
     * This ensures that mismatched passwords return a 422 status with validation errors.
     */
    public function test_password_must_match_confirmation(): void
    {
        // Given: Mismatched password and confirmation
        $data = [
            "old_password" => "password",
            "password" => "newSecurePassword123",
            "password_confirmation" => "differentPassword456",
        ];

        // When: The user attempts to update their password
        $user = User::where('email', 'example@example.com')->first();
        $this->assertNotNull($user, 'Test user should exist');

        $response = $this->apiAs($user, 'PUT', route('user.password.update'), $data);

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
     * Test that password update endpoint only accepts PUT method.
     * This ensures that only PUT requests are allowed for password updates.
     */
    public function test_password_update_only_accepts_put_method(): void
    {
        // Given: A valid user
        $user = User::where('email', 'example@example.com')->first();
        $data = [
            "old_password" => "password",
            "password" => "newSecurePassword123",
            "password_confirmation" => "newSecurePassword123",
        ];

        // When: Attempting to use POST method
        $response = $this->apiAs($user, 'POST', route('user.password.update'), $data);

        // Then: The response should return a 405 status (Method Not Allowed)
        $response->assertStatus(405);
    }

    /**
     * Test that password is properly hashed in database.
     * This ensures that passwords are not stored in plain text.
     */
    public function test_password_is_properly_hashed_in_database(): void
    {
        // Given: Valid password update data
        $data = [
            "old_password" => "password",
            "password" => "myNewPassword123",
            "password_confirmation" => "myNewPassword123",
        ];

        // When: The user updates their password
        $user = User::where('email', 'example@example.com')->first();
        $response = $this->apiAs($user, 'PUT', route('user.password.update'), $data);

        // Then: The response should be successful
        $response->assertStatus(200);

        // And: The password should be hashed (not plain text) in the database
        $user->refresh();
        $this->assertNotEquals($data['password'], $user->password);
        $this->assertTrue(Hash::check($data['password'], $user->password));
    }

    /**
     * Test that password update response doesn't contain sensitive data.
     * This ensures that the response doesn't expose sensitive information.
     */
    public function test_password_update_response_does_not_contain_sensitive_data(): void
    {
        // Given: Valid password update data
        $data = [
            "old_password" => "password",
            "password" => "newSecurePassword123",
            "password_confirmation" => "newSecurePassword123",
        ];

        // When: The user updates their password
        $user = User::where('email', 'example@example.com')->first();
        $response = $this->apiAs($user, 'PUT', route('user.password.update'), $data);

        // Then: The response should be successful
        $response->assertStatus(200);

        // And: The response should not contain sensitive information
        $responseData = $response->json();
        $this->assertArrayNotHasKey('password', $responseData);
        $this->assertArrayNotHasKey('old_password', $responseData);
        
        // And: The response should contain the expected success message
        $this->assertEquals(__('messages.password.updated'), $responseData['message']);
    }
}