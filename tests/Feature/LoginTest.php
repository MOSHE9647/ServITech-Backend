<?php

namespace Tests\Feature;

use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase; // RefreshDatabase trait to reset the database after each test

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class); // Seed the database with test users
    }

    public function test_an_existing_user_can_login(): void
    {
        // Show exceptions instead of catching them
        // $this->withoutExceptionHandling();

        // Given:
        $credentials = [
            'email' => 'example@example.com',
            'password' => 'password',
        ];

        // When:
        $response = $this->postJson("{$this->apiBase}/auth/login", $credentials);

        // Then:
        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => ['token']]);
    }

    public function test_a_non_existing_user_cannot_login(): void
    {
        // Given:
        $credentials = [
            'email' => 'example@nonexisting.com',
            'password' => 'assddrfegvfdg',
        ];

        // When:
        $response = $this->postJson("{$this->apiBase}/auth/login", $credentials);

        // Then:
        $response->assertStatus(401);
        $response->assertJsonFragment(['status' => 401, 'message' => __('passwords.user')]);
    }

    public function test_an_existing_user_use_a_wrong_password(): void{
        // Given:
        $credentials = [
            'email' => 'example@example.com',
            'password' => 'assddrfegvfdg',
        ];

        // When:
        $response = $this->postJson("{$this->apiBase}/auth/login", $credentials);

        // Then:
        $response->assertStatus(401);
        $response->assertJsonFragment(['status' => 401, 'message' => __('messages.user.invalid_credentials')]);
    }

    public function test_email_must_be_required(): void
    {
        // Given:
        $credentials = [
            'password' => 'password',
        ];

        // When:
        $response = $this->postJson("{$this->apiBase}/auth/login", $credentials);

        // Then:
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

    public function test_email_must_be_a_valid_email(): void
    {
        // Given:
        $credentials = [
            'email'=> 'email',
            'password' => 'password',
        ];

        // When:
        $response = $this->postJson("{$this->apiBase}/auth/login", $credentials);

        // Then:
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

    public function test_email_must_be_a_string(): void
    {
        // Given:
        $credentials = [
            'email'=> 1234567890,
            'password' => 'password',
        ];

        // When:
        $response = $this->postJson("{$this->apiBase}/auth/login", $credentials);

        // Then:
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'status', 'message', 'errors' => ['email']
        ]);
        $response->assertJsonFragment([
            'email' => [
                __('validation.string', [
                    'attribute' => __('validation.attributes.email')
                ]),
                __('validation.email', [
                    'attribute' => __('validation.attributes.email')
                ])
            ]
        ]);
    }

    public function test_password_must_be_required(): void
    {
        // Given:
        $credentials = [
            'email' => 'example@nonexisting.com',
        ];

        // When:
        $response = $this->postJson("{$this->apiBase}/auth/login", $credentials);

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
        // Given:
        $credentials = [
            'email' => 'example@nonexisting.com',
            'password'=> 'pass',
        ];

        // When:
        $response = $this->postJson("{$this->apiBase}/auth/login", $credentials);

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
}