<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRoles;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRegisterTest extends TestCase
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
     * Test that a user can register successfully.
     * This ensures that valid data allows the user to register and receive a 201 status with a success message.
     */
    public function test_an_user_can_register(): void
    {
        // Given: Valid user data
        $data = [
            "name"                  => "Example User",
            "phone"                 => "1234567890",
            "email"                 => "email@email.com",
            "password"              => "password",
            "password_confirmation" => "password",
        ];

        // When: The user attempts to register
        $response = $this->postJson(route('auth.register'), $data);

        // Then: The response should return a 201 status with a success message
        $response->assertStatus(201)
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
                'status' => 201,
                'message' => __('messages.user.registered'),
            ])
            ->assertJsonPath('data.user.email', 'email@email.com')
            ->assertJsonPath('data.user.name', 'Example User')
            ->assertJsonPath('data.user.phone', '1234567890')
            ->assertJsonPath('data.user.role', UserRoles::USER->value);

        // And: Ensure the user is in the database
        $this->assertDatabaseHas('users', [
            'email' => 'email@email.com',
            'name'  => 'Example User',
            'phone' => '1234567890',
        ]);

        // And: Verify the user has the USER role assigned
        $user = User::where('email', 'email@email.com')->first();
        $this->assertTrue($user->hasRole(UserRoles::USER->value));
    }

    /**
     * Test that a registered user can log in.
     * This ensures that a user who has registered can log in successfully.
     */
    public function test_a_registered_user_can_login(): void
    {
        // Given: Valid user data for registration
        $userData = [
            "name"                  => "Example User",
            "phone"                 => "1234567890",
            "email"                 => "email@email.com",
            "password"              => "password",
            "password_confirmation" => "password",
        ];

        // When: The user registers and then attempts to log in
        $registerResponse = $this->postJson(route('auth.register'), $userData);
        $registerResponse->assertStatus(201);

        $loginResponse = $this->postJson(route('auth.login'), [
            "email"    => "email@email.com",
            "password" => "password",
        ]);

        // Then: The response should return a 200 status with user data, token and expiration
        $loginResponse->assertStatus(200)
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
            ])
            ->assertJsonFragment([
                'status' => 200,
                'message' => __('messages.user.logged_in')
            ])
            ->assertJsonPath('data.user.email', 'email@email.com')
            ->assertJsonPath('data.user.name', 'Example User')
            ->assertJsonPath('data.user.phone', '1234567890')
            ->assertJsonPath('data.user.role', UserRoles::USER->value);
    }

    /**
     * Test that the email field is required.
     * This ensures that missing the email field returns a 422 status with validation errors.
     */
    public function test_email_must_be_required(): void
    {
        // Given: Missing email field
        $data = [
            "name"                  => "Example User",
            "phone"                 => "1234567890",
            "password"              => "password",
            "password_confirmation" => "password",
        ];

        // When: The user attempts to register
        $response = $this->postJson(route('auth.register'), $data);

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
        $data = [
            "name"                  => "Example User",
            "phone"                 => "1234567890",
            "email"                 => "invalid-email-format",
            "password"              => "password",
            "password_confirmation" => "password",
        ];

        // When: The user attempts to register
        $response = $this->postJson(route('auth.register'), $data);

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
     * Test that the email must be unique.
     * This ensures that duplicate emails return a 422 status with validation errors.
     */
    public function test_email_must_be_unique(): void
    {
        // Given: An existing user with the same email
        User::factory()->create(['email' => 'existing@email.com']);

        $data = [
            "name"                  => "Example User",
            "phone"                 => "1234567890",
            "email"                 => "existing@email.com",
            "password"              => "password",
            "password_confirmation" => "password",
        ];

        // When: The user attempts to register with the same email
        $response = $this->postJson(route('auth.register'), $data);

        // Then: The response should return a 422 status with validation errors for the email field
        $response->assertStatus(422)
            ->assertJsonStructure([
                'status', 
                'message', 
                'errors' => ['email']
            ])
            ->assertJsonPath('errors.email', __('validation.unique', [
                'attribute' => __('validation.attributes.email')
            ]));
    }

    /**
     * Test that the password field is required.
     * This ensures that missing the password field returns a 422 status with validation errors.
     */
    public function test_password_must_be_required(): void
    {
        // Given: Missing password field
        $data = [
            "name"                  => "Example User",
            "phone"                 => "1234567890",
            "email"                 => "email@email.com",
            "password_confirmation" => "password",
        ];

        // When: The user attempts to register
        $response = $this->postJson(route('auth.register'), $data);

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
        $data = [
            "name"                  => "Example User",
            "phone"                 => "1234567890",
            "email"                 => "email@email.com",
            "password"              => "short",
            "password_confirmation" => "short",
        ];

        // When: The user attempts to register
        $response = $this->postJson(route('auth.register'), $data);

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
            "name"     => "Example User",
            "phone"    => "1234567890",
            "email"    => "email@email.com",
            "password" => "password",
        ];

        // When: The user attempts to register
        $response = $this->postJson(route('auth.register'), $data);

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
     * Test that the password must match the confirmation.
     * This ensures that mismatched passwords return a 422 status with validation errors.
     */
    public function test_password_must_match_confirmation(): void
    {
        // Given: Mismatched password and confirmation
        $data = [
            "name"                  => "Example User",
            "phone"                 => "1234567890",
            "email"                 => "email@email.com",
            "password"              => "password",
            "password_confirmation" => "different_password",
        ];

        // When: The user attempts to register
        $response = $this->postJson(route('auth.register'), $data);

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
     * Test that the name field is required.
     * This ensures that missing the name field returns a 422 status with validation errors.
     */
    public function test_name_must_be_required(): void
    {
        // Given: Missing name field
        $data = [
            "phone"                 => "1234567890",
            "email"                 => "email@email.com",
            "password"              => "password",
            "password_confirmation" => "password",
        ];

        // When: The user attempts to register
        $response = $this->postJson(route('auth.register'), $data);

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
     * Test that the name must be a string.
     * This ensures that non-string values for the name field return a 422 status with validation errors.
     */
    public function test_name_must_be_a_string(): void
    {
        // Given: Non-string name value
        $data = [
            "name"                  => 1234567890,
            "phone"                 => "1234567890",
            "email"                 => "email@email.com",
            "password"              => "password",
            "password_confirmation" => "password",
        ];

        // When: The user attempts to register
        $response = $this->postJson(route('auth.register'), $data);

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
     * Test that the name must have at least 2 characters.
     * This ensures that short names return a 422 status with validation errors.
     */
    public function test_name_must_have_at_least_2_characters(): void
    {
        // Given: Name with less than 2 characters
        $data = [
            "name"                  => "E",
            "phone"                 => "1234567890",
            "email"                 => "email@email.com",
            "password"              => "password",
            "password_confirmation" => "password",
        ];

        // When: The user attempts to register
        $response = $this->postJson(route('auth.register'), $data);

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
     * Test that the phone field is required.
     * This ensures that missing the phone field returns a 422 status with validation errors.
     */
    public function test_phone_must_be_required(): void
    {
        // Given: Missing phone field
        $data = [
            "name"                  => "Example User",
            "email"                 => "email@email.com",
            "password"              => "password",
            "password_confirmation" => "password",
        ];

        // When: The user attempts to register
        $response = $this->postJson(route('auth.register'), $data);

        // Then: The response should return a 422 status with validation errors for the phone field
        $response->assertStatus(422)
            ->assertJsonStructure([
                'status', 
                'message', 
                'errors' => ['phone']
            ])
            ->assertJsonPath('errors.phone', __('validation.required', [
                'attribute' => __('validation.attributes.phone')
            ]));
    }

    /**
     * Test that the phone must be a string.
     * This ensures that non-string values for the phone field return a 422 status with validation errors.
     */
    public function test_phone_must_be_a_string(): void
    {
        // Given: Non-string phone value
        $data = [
            "name"                  => "Example User",
            "phone"                 => 1234567890,
            "email"                 => "email@email.com",
            "password"              => "password",
            "password_confirmation" => "password",
        ];

        // When: The user attempts to register
        $response = $this->postJson(route('auth.register'), $data);

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
     * Test that the phone must have at least 10 characters.
     * This ensures that short phone numbers return a 422 status with validation errors.
     */
    public function test_phone_must_have_at_least_10_characters(): void
    {
        // Given: Phone with less than 10 characters
        $data = [
            "name"                  => "Example User",
            "phone"                 => "123456789", // 9 characters
            "email"                 => "email@email.com",
            "password"              => "password",
            "password_confirmation" => "password",
        ];

        // When: The user attempts to register
        $response = $this->postJson(route('auth.register'), $data);

        // Then: The response should return a 422 status with validation errors for the phone field
        $response->assertStatus(422)
            ->assertJsonStructure([
                'status', 
                'message', 
                'errors' => ['phone']
            ])
            ->assertJsonPath('errors.phone', __('validation.min.string', [
                'attribute' => __('validation.attributes.phone'),
                'min' => 10,
            ]));
    }

    /**
     * Test that registration creates user with correct role.
     * This ensures that newly registered users get the USER role assigned.
     */
    public function test_registration_assigns_user_role(): void
    {
        // Given: Valid user data
        $data = [
            "name"                  => "Example User",
            "phone"                 => "1234567890",
            "email"                 => "test@example.com",
            "password"              => "password",
            "password_confirmation" => "password",
        ];

        // When: The user registers
        $response = $this->postJson(route('auth.register'), $data);

        // Then: The response should be successful
        $response->assertStatus(201);

        // And: The user should have the USER role
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('user'));
    }

    /**
     * Test that UserResource returns correct structure and data.
     * This ensures that the UserResource includes all expected fields with correct values.
     */
    public function test_user_resource_returns_correct_structure(): void
    {
        // Given: Valid user data
        $data = [
            "name"                  => "Resource Test User",
            "phone"                 => "9876543210",
            "email"                 => "resource@test.com",
            "password"              => "password",
            "password_confirmation" => "password",
        ];

        // When: The user registers
        $response = $this->postJson(route('auth.register'), $data);

        // Then: The response should include UserResource with all expected fields
        $response->assertStatus(201)
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
            ]);

        // And: Verify the UserResource contains exactly the expected data
        $userData = $response->json('data.user');
        $this->assertIsInt($userData['id']);
        $this->assertEquals('user', $userData['role']);
        $this->assertEquals('Resource Test User', $userData['name']);
        $this->assertEquals('resource@test.com', $userData['email']);
        $this->assertEquals('9876543210', $userData['phone']);

        // And: Verify no additional fields are exposed (security check)
        $expectedFields = ['id', 'role', 'name', 'email', 'phone'];
        $actualFields = array_keys($userData);
        $this->assertEquals($expectedFields, $actualFields);
    }

    /**
     * Test that multiple users can register successfully.
     * This ensures that the registration process works for multiple users.
     */
    public function test_multiple_users_can_register(): void
    {
        // Given: Multiple sets of valid user data
        $users = [
            [
                "name"                  => "User One",
                "phone"                 => "1111111111",
                "email"                 => "user1@example.com",
                "password"              => "password",
                "password_confirmation" => "password",
            ],
            [
                "name"                  => "User Two",
                "phone"                 => "2222222222",
                "email"                 => "user2@example.com",
                "password"              => "password",
                "password_confirmation" => "password",
            ]
        ];

        // When: Both users register
        foreach ($users as $index => $userData) {
            $response = $this->postJson(route('auth.register'), $userData);
            
            // Then: Each registration should be successful
            $response->assertStatus(201)
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
                ->assertJsonPath('data.user.name', $userData['name'])
                ->assertJsonPath('data.user.email', $userData['email'])
                ->assertJsonPath('data.user.phone', $userData['phone'])
                ->assertJsonPath('data.user.role', 'user');
        }

        // And: Both users should exist in the database
        $this->assertDatabaseHas('users', ['email' => 'user1@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'user2@example.com']);

        // And: Both users should have the correct role
        $user1 = User::where('email', 'user1@example.com')->first();
        $user2 = User::where('email', 'user2@example.com')->first();
        $this->assertTrue($user1->hasRole('user'));
        $this->assertTrue($user2->hasRole('user'));
    }

    /**
     * Test that UserResource does not expose sensitive data.
     * This ensures that sensitive fields like password are not included in the response.
     */
    public function test_user_resource_does_not_expose_sensitive_data(): void
    {
        // Given: Valid user data
        $data = [
            "name"                  => "Security Test User",
            "phone"                 => "5555555555",
            "email"                 => "security@test.com",
            "password"              => "secretpassword123",
            "password_confirmation" => "secretpassword123",
        ];

        // When: The user registers
        $response = $this->postJson(route('auth.register'), $data);

        // Then: The response should not contain sensitive information
        $response->assertStatus(201);
        
        $userData = $response->json('data.user');
        
        // Verify sensitive fields are not exposed
        $this->assertArrayNotHasKey('password', $userData);
        $this->assertArrayNotHasKey('remember_token', $userData);
        $this->assertArrayNotHasKey('email_verified_at', $userData);
        $this->assertArrayNotHasKey('created_at', $userData);
        $this->assertArrayNotHasKey('updated_at', $userData);
        $this->assertArrayNotHasKey('deleted_at', $userData);
        
        // Verify only expected fields are present
        $expectedFields = ['id', 'role', 'name', 'email', 'phone'];
        $this->assertEquals($expectedFields, array_keys($userData));
    }
}