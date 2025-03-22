<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UpdatePasswordTest extends TestCase
{
    use RefreshDatabase; // RefreshDatabase trait to reset the database after each test

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class); // Seed the database with test users
    }

    public function test_an_authenticated_user_can_update_their_password()
    {
        // Show exceptions instead of catching them
        // $this->withoutExceptionHandling();

        // Given:
        $data = [
            "old_password" => "password",
            "password" => "newpassword",
            "password_confirmation" => "newpassword",
        ];

        // When:
        $user = User::find(1)->first(); // Find the user with ID 1
        $this->assertNotNull($user, __('messages.user_not_found')); // Ensure the user exists
        
        $response = $this->apiAs($user, 'PUT', "{$this->apiBase}/user/password", $data);

        // Then:
        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'status', 'message']);
        
        $user->refresh(); // Refresh the user instance to get the updated password
        $this->assertTrue(Hash::check($data['password'], $user->password)); // Verify the password was updated
    }

    public function test_old_password_must_be_validated()
    {
        // Show exceptions instead of catching them
        // $this->withoutExceptionHandling();

        // Given:
        $data = [
            "old_password" => "wrongpassword",
            "password" => "newpassword",
            "password_confirmation" => "newpassword",
        ];

        // When:
        $user = User::find(1)->first(); // Find the user with ID 1
        $this->assertNotNull($user, __('messages.user_not_found')); // Ensure the user exists
        
        $response = $this->apiAs($user, 'PUT', "{$this->apiBase}/user/password", $data);

        // Then:
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

    public function test_old_password_must_be_required(): void
    {
        // Show exceptions instead of catching them
        // $this->withoutExceptionHandling();
        
        // Given:
        $data = [
            "old_password" => "",
            "password" => "newpassword",
            "password_confirmation" => "newpassword",
        ];

        // When:
        $user = User::find(1)->first(); // Find the user with ID 1
        $this->assertNotNull($user, __('messages.user_not_found')); // Ensure the user exists
        
        $response = $this->apiAs($user, 'PUT', "{$this->apiBase}/user/password", $data);

        // Then:
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'status', 'message', 'errors' => ['old_password']
        ]);
        $response->assertJsonFragment([
            'old_password' => [
                __('validation.required', [
                    'attribute' => __('validation.attributes.old_password')
                ]),
            ]
        ]);
    }

    public function test_password_must_be_required(): void
    {
        // Show exceptions instead of catching them
        // $this->withoutExceptionHandling();
        
        // Given:
        $data = [
            "old_password" => "password",
            "password" => "",
            "password_confirmation" => "newpassword",
        ];

        // When:
        $user = User::find(1)->first(); // Find the user with ID 1
        $this->assertNotNull($user, __('messages.user_not_found')); // Ensure the user exists
        
        $response = $this->apiAs($user, 'PUT', "{$this->apiBase}/user/password", $data);

        // Then:
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

    public function test_password_must_have_at_least_8_characters(): void
    {
        // Show exceptions instead of catching them
        // $this->withoutExceptionHandling();
        
        // Given:
        $data = [
            "old_password" => "password",
            "password" => "newpass",
            "password_confirmation" => "newpass",
        ];

        // When:
        $user = User::find(1)->first(); // Find the user with ID 1
        $this->assertNotNull($user, __('messages.user_not_found')); // Ensure the user exists
        
        $response = $this->apiAs($user, 'PUT', "{$this->apiBase}/user/password", $data);

        // Then:
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

    public function test_password_confirmation_is_required(): void
    {
        // Show exceptions instead of catching them
        // $this->withoutExceptionHandling();
        
        // Given:
        $data = [
            "old_password" => "password",
            "password" => "newpassword",
            "password_confirmation" => "",
        ];

        // When:
        $user = User::find(1)->first(); // Find the user with ID 1
        $this->assertNotNull($user, __('messages.user_not_found')); // Ensure the user exists
        
        $response = $this->apiAs($user, 'PUT', "{$this->apiBase}/user/password", $data);

        // Then:
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

    public function test_password_must_match_confirmation(): void
    {
        // Show exceptions instead of catching them
        // $this->withoutExceptionHandling();
        
        // Given:
        $data = [
            "old_password" => "password",
            "password" => "newpassword",
            "password_confirmation" => "newpassword123",
        ];

        // When:
        $user = User::find(1)->first(); // Find the user with ID 1
        $this->assertNotNull($user, __('messages.user_not_found')); // Ensure the user exists
        
        $response = $this->apiAs($user, 'PUT', "{$this->apiBase}/user/password", $data);

        // Then:
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
}