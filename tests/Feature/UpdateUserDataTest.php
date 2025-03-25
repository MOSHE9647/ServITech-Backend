<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UpdateUserDataTest extends TestCase
{
    use RefreshDatabase; // RefreshDatabase trait to reset the database after each test

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class); // Seed the database with test users
    }

    public function test_an_authenticated_user_can_modify_their_data()
    {
        // Show exceptions instead of catching them
        // $this->withoutExceptionHandling();

        // Given:
        $data = [
            "name" => "New Name",
            "last_name" => "New Last Name",
        ];

        // When:
        $user = User::find(1)->first(); // Find the user with ID 1
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists
        
        $response = $this->apiAs($user, 'PUT', "{$this->apiBase}/user/profile", $data);

        // Then:
        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'status', 'message']);
        $response->assertJsonFragment([
            'data' => [
                'user' => [
                    "id"        => 1,
                    "email"     => "example@example.com",
                    "name"      => "New Name",
                    "last_name" => "New Last Name",
                ],
            ],
            'status' => 200, 
            'message' => __('messages.user.info_updated')
        ]);

        // Ensure the old data is not present in the database
        $this->assertDatabaseMissing('users', [
            'email'     => 'example@example.com',
            "name"      => "Example",
            "last_name" => "Example Example",
        ]);
    }

    public function test_an_authenticated_user_cannot_modify_their_email()
    {
        // Show exceptions instead of catching them
        // $this->withoutExceptionHandling();

        // Given:
        $data = [
            "email"     => "newemail@example.com",
            "name"      => "New Name",
            "last_name" => "New Last Name",
        ];

        // When:
        $user = User::find(1)->first(); // Find the user with ID 1
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'PUT', "{$this->apiBase}/user/profile", $data);

        // Then:
        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'status', 'message']);
        $response->assertJsonFragment([
            'data' => [
                'user' => [
                    "id"        => 1,
                    "email"     => "example@example.com",
                    "name"      => "New Name",
                    "last_name" => "New Last Name",
                ],
            ],
            'status' => 200, 
            'message' => __('messages.user.info_updated')
        ]);

        // Ensure the email was not changed in the database
        $this->assertDatabaseHas('users', [
            'email'     => 'example@example.com',
            "name"      => "New Name",
            "last_name" => "New Last Name",
        ]);
    }

    public function test_an_authenticated_user_cannot_modify_their_password()
    {
        // Show exceptions instead of catching them
        // $this->withoutExceptionHandling();

        // Given:
        $data = [
            "password"  => "newpassword",
            "name"      => "New Name",
            "last_name" => "New Last Name",
        ];

        // When:
        $user = User::find(1)->first(); // Find the user with ID 1
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'PUT', "{$this->apiBase}/user/profile", $data);

        // Then:
        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'status', 'message']);

        $user->refresh(); // Refresh the user instance to get the updated data
        $this->assertFalse(Hash::check($data['password'], $user->password)); // Verify the password was not updated
    }

    public function test_name_must_be_required(): void
    {
        // Show exceptions instead of catching them
        // $this->withoutExceptionHandling();
        
        // Given:
        $data = [
            "name"      => "",
            "last_name" => "Example Example",
        ];

        // When:
        $user = User::find(1)->first(); // Find the user with ID 1
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'PUT', "{$this->apiBase}/user/profile", $data);

        // Then:
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

    public function test_name_must_be_a_string(): void
    {
        // Show exceptions instead of catching them
        // $this->withoutExceptionHandling();
        
        // Given:
        $data = [
            "name"      => 1234567890,
            "last_name" => "Example Example",
        ];

        // When:
        $user = User::find(1)->first(); // Find the user with ID 1
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'PUT', "{$this->apiBase}/user/profile", $data);

        // Then:
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

    public function test_name_must_have_at_least_2_characters(): void
    {
        // Show exceptions instead of catching them
        // $this->withoutExceptionHandling();
        
        // Given:
        $data = [
            "name"      => "E",
            "last_name" => "Example Example",
        ];

        // When:
        $user = User::find(1)->first(); // Find the user with ID 1
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'PUT', "{$this->apiBase}/user/profile", $data);

        // Then:
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

    public function test_last_name_must_be_required(): void
    {
        // Show exceptions instead of catching them
        // $this->withoutExceptionHandling();
        
        // Given:
        $data = [
            "name"      => "Example",
            "last_name" => "",
        ];

        // When:
        $user = User::find(1)->first(); // Find the user with ID 1
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'PUT', "{$this->apiBase}/user/profile", $data);

        // Then:
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

    public function test_last_name_must_be_a_string(): void
    {
        // Show exceptions instead of catching them
        // $this->withoutExceptionHandling();
        
        // Given:
        $data = [
            "name"      => 'Example',
            "last_name" => 1234567890,
        ];

        // When:
        $user = User::find(1)->first(); // Find the user with ID 1
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'PUT', "{$this->apiBase}/user/profile", $data);

        // Then:
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

    public function test_last_name_must_have_at_least_2_characters(): void
    {
        // Show exceptions instead of catching them
        // $this->withoutExceptionHandling();
        
        // Given:
        $data = [
            "name"      => "Example",
            "last_name" => "E",
        ];

        // When:
        $user = User::find(1)->first(); // Find the user with ID 1
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'PUT', "{$this->apiBase}/user/profile", $data);

        // Then:
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