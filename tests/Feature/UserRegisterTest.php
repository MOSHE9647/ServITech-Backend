<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRegisterTest extends TestCase
{
    use RefreshDatabase; // Reset the database after each test

    /**
     * Test that a user can register successfully.
     * This ensures that valid data allows the user to register and receive a 201 status with a success message.
     */
    public function test_an_user_can_register()
    {
        // Given: Valid user data
        $data = [
            "name"                  => "Example",
            "last_name"             => "Example Example",
            "phone"                 => "1234567890",
            "email"                 => "email@email.com",
            "password"              => "password",
            "password_confirmation" => "password",
        ];

        // When: The user attempts to register
        $response = $this->postJson(route('auth.register'), $data);

        // Then: The response should return a 201 status with a success message
        $response->assertStatus(201);
        $response->assertJsonStructure(['data', 'status', 'message']);
        $response->assertJsonFragment([
            'data' => [
                'user' => [
                    "id"            => 1,
                    "email"         => "email@email.com",
                    "phone"         => "1234567890",
                    "name"          => "Example",
                    "last_name"     => "Example Example",
                    "created_at"    => now()->format('Y-m-d\TH:i:s.000000\Z'),
                    "updated_at"    => now()->format('Y-m-d\TH:i:s.000000\Z'),
                ],
            ],
            'status' => 201,
            'message' => __('messages.user.registered'),
        ]);

        // Ensure the user is in the database
        $this->assertDatabaseCount("users", 1);
        $this->assertDatabaseHas('users', [
            'email'     => 'email@email.com',
            "name"      => "Example",
            "last_name" => "Example Example",
        ]);
    }

    /**
     * Test that a registered user can log in.
     * This ensures that a user who has registered can log in successfully.
     */
    public function test_a_registered_user_can_login(): void
    {
        // Given: Valid user data
        $data = [
            "name"                  => "Example",
            "last_name"             => "Example Example",
            "phone"                 => "1234567890",
            "email"                 => "email@email.com",
            "password"              => "password",
            "password_confirmation" => "password",
        ];

        // When: The user registers and then attempts to log in
        $this->postJson(route('auth.register'), $data);
        $response = $this->postJson(route('auth.login'), [
            "email"    => "email@email.com",
            "password" => "password",
        ]);

        // Then: The response should return a 200 status with a token
        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => ['token']]);
    }

    /**
     * Test that the email field is required.
     * This ensures that missing the email field returns a 422 status with validation errors.
     */
    public function test_email_must_be_required(): void
    {
        // Given: Missing email field
        $data = [
            "name"                  => "Example",
            "last_name"             => "Example Example",
            "phone"                 => "1234567890",
            "email"                 => "",
            "password"              => "password",
            "password_confirmation" => "password",
        ];

        // When: The user attempts to register
        $response = $this->postJson(route('auth.register'), $data);

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
        $data = [
            "name"                  => "Example",
            "last_name"             => "Example Example",
            "phone"                 => "1234567890",
            "email"                 => "invalid-email",
            "password"              => "password",
            "password_confirmation" => "password",
        ];

        // When: The user attempts to register
        $response = $this->postJson(route('auth.register'), $data);

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
     * Test that the email must be unique.
     * This ensures that duplicate emails return a 422 status with validation errors.
     */
    public function test_email_must_be_unique(): void
    {
        // Given: An existing user with the same email
        User::factory()->create(['email' => 'email@email.com']);

        $data = [
            "name"                  => "Example",
            "last_name"             => "Example Example",
            "phone"                 => "1234567890",
            "email"                 => "email@email.com",
            "password"              => "password",
            "password_confirmation" => "password",
        ];

        // When: The user attempts to register with the same email
        $response = $this->postJson(route('auth.register'), $data);

        // Then: The response should return a 422 status with validation errors for the email field
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'status', 'message', 'errors' => ['email']
        ]);
        $response->assertJsonFragment([
            'email' => [
                __('validation.unique', [
                    'attribute' => __('validation.attributes.email')
                ])
            ]
        ]);
    }

    /**
     * Test that the password field is required.
     * This ensures that missing the password field returns a 422 status with validation errors.
     */
    public function test_password_must_be_required(): void
    {
        // Given: Missing password field
        $data = [
            "name"                  => "Example",
            "last_name"             => "Example Example",
            "phone"                 => "1234567890",
            "email"                 => "email@email.com",
            "password"              => "",
            "password_confirmation" => "password",
        ];

        // When: The user attempts to register
        $response = $this->postJson(route('auth.register'), $data);

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
        $data = [
            "name"                  => "Example",
            "last_name"             => "Example Example",
            "phone"                 => "1234567890",
            "email"                 => "email@email.com",
            "password"              => "pass",
            "password_confirmation" => "pass",
        ];

        // When: The user attempts to register
        $response = $this->postJson(route('auth.register'), $data);

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

    /**
     * Test that the password confirmation is required.
     * This ensures that missing the password confirmation returns a 422 status with validation errors.
     */
    public function test_password_confirmation_is_required(): void
    {
        // Given: Missing password confirmation
        $data = [
            "name"                  => "Example",
            "last_name"             => "Example Example",
            "phone"                 => "1234567890",
            "email"                 => "email@email.com",
            "password"              => "password",
            "password_confirmation" => "",
        ];

        // When: The user attempts to register
        $response = $this->postJson(route('auth.register'), $data);

        // Then: The response should return a 422 status with validation errors for the password field
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'status', 'message', 'errors' => ['password']
        ]);
        $response->assertJsonFragment([
            'password' => [
                __('validation.confirmed', [
                    'attribute' => __('validation.attributes.password')
                ]),
            ]
        ]);
    }

    /**
     * Test that the password must match the confirmation.
     * This ensures that mismatched passwords return a 422 status with validation errors.
     */
    public function test_password_must_match_confirmation(): void
    {
        // Given: Mismatched password and confirmation
        $data = [
            "name"                  => "Example",
            "last_name"             => "Example Example",
            "phone"                 => "1234567890",
            "email"                 => "email@email.com",
            "password"              => "password",
            "password_confirmation" => "different_password",
        ];

        // When: The user attempts to register
        $response = $this->postJson(route('auth.register'), $data);

        // Then: The response should return a 422 status with validation errors for the password field
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'status', 'message', 'errors' => ['password']
        ]);
        $response->assertJsonFragment([
            'password' => [
                __('validation.confirmed', [
                    'attribute' => __('validation.attributes.password')
                ]),
            ]
        ]);
    }

    /**
     * Test that the name field is required.
     * This ensures that missing the name field returns a 422 status with validation errors.
     */
    public function test_name_must_be_required(): void
    {
        // Given: Missing name field
        $data = [
            "name"                  => "",
            "last_name"             => "Example Example",
            "phone"                 => "1234567890",
            "email"                 => "email@email.com",
            "password"              => "password",
            "password_confirmation" => "password",
        ];

        // When: The user attempts to register
        $response = $this->postJson(route('auth.register'), $data);

        // Then: The response should return a 422 status with validation errors for the name field
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'status', 'message', 'errors' => ['name']
        ]);
        $response->assertJsonFragment([
            'name' => [
                __('validation.required', [
                    'attribute' => __('validation.attributes.name')
                ]),
            ]
        ]);
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
            "last_name"             => "Example Example",
            "phone"                 => "1234567890",
            "email"                 => "email@email.com",
            "password"              => "password",
            "password_confirmation" => "password",
        ];

        // When: The user attempts to register
        $response = $this->postJson(route('auth.register'), $data);

        // Then: The response should return a 422 status with validation errors for the name field
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'status', 'message', 'errors' => ['name']
        ]);
        $response->assertJsonFragment([
            'name' => [
                __('validation.string', [
                    'attribute' => __('validation.attributes.name')
                ]),
            ]
        ]);
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
            "last_name"             => "Example Example",
            "phone"                 => "1234567890",
            "email"                 => "email@email.com",
            "password"              => "password",
            "password_confirmation" => "password",
        ];

        // When: The user attempts to register
        $response = $this->postJson(route('auth.register'), $data);

        // Then: The response should return a 422 status with validation errors for the name field
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'status', 'message', 'errors' => ['name']
        ]);
        $response->assertJsonFragment([
            'name' => [
                __('validation.min.string', [
                    'attribute' => __('validation.attributes.name'),
                    'min' => 2,
                ]),
            ]
        ]);
    }

    /**
     * Test that the last name field is required.
     * This ensures that missing the last name field returns a 422 status with validation errors.
     */
    public function test_last_name_must_be_required(): void
    {
        // Given: Missing last name field
        $data = [
            "name"                  => "Example",
            "last_name"             => "",
            "phone"                 => "1234567890",
            "email"                 => "email@email.com",
            "password"              => "password",
            "password_confirmation" => "password",
        ];

        // When: The user attempts to register
        $response = $this->postJson(route('auth.register'), $data);

        // Then: The response should return a 422 status with validation errors for the last name field
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'status', 'message', 'errors' => ['last_name']
        ]);
        $response->assertJsonFragment([
            'last_name' => [
                __('validation.required', [
                    'attribute' => __('validation.attributes.last_name')
                ]),
            ]
        ]);
    }

    /**
     * Test that the last name must be a string.
     * This ensures that non-string values for the last name field return a 422 status with validation errors.
     */
    public function test_last_name_must_be_a_string(): void
    {
        // Given: Non-string last name value
        $data = [
            "name"                  => "Example",
            "last_name"             => 1234567890,
            "phone"                 => "1234567890",
            "email"                 => "email@email.com",
            "password"              => "password",
            "password_confirmation" => "password",
        ];

        // When: The user attempts to register
        $response = $this->postJson(route('auth.register'), $data);

        // Then: The response should return a 422 status with validation errors for the last name field
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'status', 'message', 'errors' => ['last_name']
        ]);
        $response->assertJsonFragment([
            'last_name' => [
                __('validation.string', [
                    'attribute' => __('validation.attributes.last_name')
                ]),
            ]
        ]);
    }

    /**
     * Test that the last name must have at least 2 characters.
     * This ensures that short last names return a 422 status with validation errors.
     */
    public function test_last_name_must_have_at_least_2_characters(): void
    {
        // Given: Last name with less than 2 characters
        $data = [
            "name"                  => "Example",
            "last_name"             => "E",
            "phone"                 => "1234567890",
            "email"                 => "email@email.com",
            "password"              => "password",
            "password_confirmation" => "password",
        ];

        // When: The user attempts to register
        $response = $this->postJson(route('auth.register'), $data);

        // Then: The response should return a 422 status with validation errors for the last name field
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'status', 'message', 'errors' => ['last_name']
        ]);
        $response->assertJsonFragment([
            'last_name' => [
                __('validation.min.string', [
                    'attribute' => __('validation.attributes.last_name'),
                    'min' => 2,
                ]),
            ]
        ]);
    }
}