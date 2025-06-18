<?php

namespace Tests\Feature\User;

use App\Enums\UserRoles;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UpdateUserDataTest extends TestCase
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
     * Test that an authenticated user can modify their data.
     * This ensures that valid data allows the user to update their profile information.
     */
    public function test_an_authenticated_user_can_modify_their_data(): void
    {
        // Given: Valid user data
        $data = [
            "name" => "Updated Name",
            "phone" => "9876543210",
        ];

        // When: The user attempts to update their profile
        $user = User::where('email', 'example@example.com')->first();
        $this->assertNotNull($user, 'Test user should exist');

        $response = $this->apiAs($user, 'PUT', route('user.profile.update'), $data);

        // Then: The response should return a 200 status with a success message
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
                'message' => __('messages.user.info_updated')
            ])
            ->assertJsonPath('data.user.name', 'Updated Name')
            ->assertJsonPath('data.user.email', $user->email)
            ->assertJsonPath('data.user.phone', '9876543210')
            ->assertJsonPath('data.user.role', UserRoles::USER->value);

        // And: Ensure the data is updated in the database
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => $user->email,
            'name' => 'Updated Name',
            'phone' => '9876543210',
        ]);
    }

    /**
     * Test that an authenticated user cannot modify their email.
     * This ensures that the email field is ignored during profile updates.
     */
    public function test_an_authenticated_user_cannot_modify_their_email(): void
    {
        // Given: Data with a new email (should be ignored)
        $data = [
            "email" => "newemail@example.com", // This should be ignored
            "name" => "Updated Name",
            "phone" => "5555555555",
        ];

        // When: The user attempts to update their profile
        $user = User::where('email', 'example@example.com')->first();
        $this->assertNotNull($user, 'Test user should exist');
        $originalEmail = $user->email;

        $response = $this->apiAs($user, 'PUT', route('user.profile.update'), $data);

        // Then: The response should return a 200 status with a success message
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
                'message' => __('messages.user.info_updated')
            ])
            ->assertJsonPath('data.user.email', $originalEmail) // Email should remain unchanged
            ->assertJsonPath('data.user.name', 'Updated Name');

        // And: Ensure the email was not changed in the database
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => $originalEmail, // Original email should be preserved
            'name' => 'Updated Name',
        ]);

        // And: Ensure the new email is not in the database
        $this->assertDatabaseMissing('users', [
            'email' => 'newemail@example.com'
        ]);
    }

    /**
     * Test that an authenticated user cannot modify their password.
     * This ensures that the password field is ignored during profile updates.
     */
    public function test_an_authenticated_user_cannot_modify_their_password(): void
    {
        // Given: Data with a new password (should be ignored)
        $data = [
            "password" => "newpassword123", // This should be ignored
            "name" => "Updated Name",
        ];

        // When: The user attempts to update their profile
        $user = User::where('email', 'example@example.com')->first();
        $this->assertNotNull($user, 'Test user should exist');
        $originalPassword = $user->password;

        $response = $this->apiAs($user, 'PUT', route('user.profile.update'), $data);

        // Then: The response should return a 200 status with a success message
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
                'message' => __('messages.user.info_updated')
            ]);

        // And: Verify the password was not updated in the database
        $user->refresh();
        $this->assertEquals($originalPassword, $user->password);
        $this->assertFalse(Hash::check('newpassword123', $user->password));
    }

    /**
     * Test that the name field is required.
     * This ensures that missing the name field returns a 422 status with validation errors.
     */
    public function test_name_must_be_required(): void
    {
        // Given: Missing name field
        $data = [
            "phone" => "1234567890",
        ];

        // When: The user attempts to update their profile
        $user = User::where('email', 'example@example.com')->first();
        $this->assertNotNull($user, 'Test user should exist');

        $response = $this->apiAs($user, 'PUT', route('user.profile.update'), $data);

        // Then: The response should return a 422 status with validation errors for the name field
        $response->assertStatus(422)
            ->assertJsonStructure([
                'status',
                'message',
                'errors' => ['name']
            ])
            ->assertJsonPath('errors.name', __('validation.required', [
                'attribute' => __('validation.attributes.name')
            ]));
    }

    /**
     * Test that the name must have at least 2 characters.
     * This ensures that short names return a 422 status with validation errors.
     */
    public function test_name_must_have_at_least_2_characters(): void
    {
        // Given: Name with less than 2 characters
        $data = [
            "name" => "X",
            "phone" => "1234567890",
        ];

        // When: The user attempts to update their profile
        $user = User::where('email', 'example@example.com')->first();
        $this->assertNotNull($user, 'Test user should exist');

        $response = $this->apiAs($user, 'PUT', route('user.profile.update'), $data);

        // Then: The response should return a 422 status with validation errors for the name field
        $response->assertStatus(422)
            ->assertJsonStructure([
                'status',
                'message',
                'errors' => ['name']
            ])
            ->assertJsonPath('errors.name', __('validation.min.string', [
                'attribute' => __('validation.attributes.name'),
                'min' => 2,
            ]));
    }

    /**
     * Test that the name must be a string.
     * This ensures that non-string values for the name field return validation errors.
     */
    public function test_name_must_be_a_string(): void
    {
        // Given: Non-string name value
        $data = [
            "name" => 123456,
            "phone" => "1234567890",
        ];

        // When: The user attempts to update their profile
        $user = User::where('email', 'example@example.com')->first();
        $this->assertNotNull($user, 'Test user should exist');

        $response = $this->apiAs($user, 'PUT', route('user.profile.update'), $data);

        // Then: The response should return a 422 status with validation errors for the name field
        $response->assertStatus(422)
            ->assertJsonStructure([
                'status',
                'message',
                'errors' => ['name']
            ])
            ->assertJsonPath('errors.name', __('validation.string', [
                'attribute' => __('validation.attributes.name')
            ]));
    }

    /**
     * Test that phone field is optional.
     * This ensures that profile can be updated without providing phone.
     */
    public function test_phone_is_optional(): void
    {
        // Given: Data without phone field
        $data = [
            "name" => "Updated Name",
        ];

        // When: The user attempts to update their profile
        $user = User::where('email', 'example@example.com')->first();
        $this->assertNotNull($user, 'Test user should exist');

        $response = $this->apiAs($user, 'PUT', route('user.profile.update'), $data);

        // Then: The response should return a 200 status (success)
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
                'message' => __('messages.user.info_updated')
            ]);
    }

    /**
     * Test that phone field has maximum length validation.
     * This ensures that excessively long phone numbers are rejected.
     */
    public function test_phone_must_not_exceed_maximum_length(): void
    {
        // Given: Phone with more than 20 characters
        $data = [
            "name" => "Valid Name",
            "phone" => "123456789012345678901", // 21 characters
        ];

        // When: The user attempts to update their profile
        $user = User::where('email', 'example@example.com')->first();
        $this->assertNotNull($user, 'Test user should exist');

        $response = $this->apiAs($user, 'PUT', route('user.profile.update'), $data);

        // Then: The response should return a 422 status with validation errors for the phone field
        $response->assertStatus(422)
            ->assertJsonStructure([
                'status',
                'message',
                'errors' => ['phone']
            ])
            ->assertJsonPath('errors.phone', __('validation.max.string', [
                'attribute' => __('validation.attributes.phone'),
                'max' => 20,
            ]));
    }

    /**
     * Test that phone must be a string.
     * This ensures that non-string values for the phone field return validation errors.
     */
    public function test_phone_must_be_a_string(): void
    {
        // Given: Non-string phone value
        $data = [
            "name" => "Valid Name",
            "phone" => 1234567890,
        ];

        // When: The user attempts to update their profile
        $user = User::where('email', 'example@example.com')->first();
        $this->assertNotNull($user, 'Test user should exist');

        $response = $this->apiAs($user, 'PUT', route('user.profile.update'), $data);

        // Then: The response should return a 422 status with validation errors for the phone field
        $response->assertStatus(422)
            ->assertJsonStructure([
                'status',
                'message',
                'errors' => ['phone']
            ])
            ->assertJsonPath('errors.phone', __('validation.string', [
                'attribute' => __('validation.attributes.phone')
            ]));
    }

    /**
     * Test that unauthenticated users cannot update profile.
     * This ensures that authentication is required for profile updates.
     */
    public function test_unauthenticated_user_cannot_update_profile(): void
    {
        // Given: Valid profile data but no authentication
        $data = [
            "name" => "Updated Name",
            "phone" => "1234567890",
        ];

        // When: An unauthenticated user attempts to update profile
        $response = $this->putJson(route('user.profile.update'), $data);

        // Then: The response should return a 401 status (Unauthorized)
        $response->assertStatus(401);
    }

    /**
     * Test that profile update endpoint only accepts PUT method.
     * This ensures that only PUT requests are allowed for profile updates.
     */
    public function test_profile_update_only_accepts_put_method(): void
    {
        // Given: A valid user and data
        $user = User::where('email', 'example@example.com')->first();
        $data = [
            "name" => "Updated Name",
            "phone" => "1234567890",
        ];

        // When: Attempting to use POST method
        $response = $this->apiAs($user, 'POST', route('user.profile.update'), $data);

        // Then: The response should return a 405 status (Method Not Allowed)
        $response->assertStatus(405);
    }

    /**
     * Test that profile update returns UserResource structure.
     * This ensures that the updated profile data follows the UserResource format.
     */
    public function test_profile_update_returns_user_resource_structure(): void
    {
        // Given: Valid update data
        $data = [
            "name" => "Resource Test User",
            "phone" => "9999999999",
        ];

        // When: The user updates their profile
        $user = User::where('email', 'example@example.com')->first();
        $response = $this->apiAs($user, 'PUT', route('user.profile.update'), $data);

        // Then: The response should include UserResource with all expected fields
        $response->assertStatus(200);

        $userData = $response->json('data.user');
        $expectedFields = ['id', 'role', 'name', 'email', 'phone'];
        $actualFields = array_keys($userData);

        $this->assertEquals($expectedFields, $actualFields);
    }

    /**
     * Test that profile update response doesn't contain sensitive data.
     * This ensures that sensitive fields are not included in the update response.
     */
    public function test_profile_update_does_not_expose_sensitive_data(): void
    {
        // Given: Valid update data
        $data = [
            "name" => "Security Test User",
            "phone" => "8888888888",
        ];

        // When: The user updates their profile
        $user = User::where('email', 'example@example.com')->first();
        $response = $this->apiAs($user, 'PUT', route('user.profile.update'), $data);

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

    /**
     * Test that multiple profile updates work correctly.
     * This ensures that users can update their profile multiple times.
     */
    public function test_multiple_profile_updates_work_correctly(): void
    {
        // Given: A user
        $user = User::where('email', 'example@example.com')->first();

        // When: The user updates their profile the first time
        $firstUpdate = [
            "name" => "First Update Name",
            "phone" => "1111111111",
        ];

        $response1 = $this->apiAs($user, 'PUT', route('user.profile.update'), $firstUpdate);
        $response1->assertStatus(200);

        // And: The user updates their profile again
        $secondUpdate = [
            "name" => "Second Update Name",
            "phone" => "2222222222",
        ];

        $response2 = $this->apiAs($user, 'PUT', route('user.profile.update'), $secondUpdate);

        // Then: Both updates should be successful
        $response2->assertStatus(200)
            ->assertJsonPath('data.user.name', 'Second Update Name')
            ->assertJsonPath('data.user.phone', '2222222222');

        // And: The final data should be the second update
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Second Update Name',
            'phone' => '2222222222',
        ]);
    }
}