<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRegisterTest extends TestCase
{
    use RefreshDatabase; // RefreshDatabase trait to reset the database after each test

    public function test_an_user_can_register()
    {
        // Show exceptions instead of catching them
        // $this->withoutExceptionHandling();

        // Given:
        $data = [
            "name"                  => "Example",
            "last_name"             => "Example Example",
            "phone"                 => "1234567890",
            "email"                 => "email@email.com",
            "password"              => "password",
            "password_confirmation" => "password",
        ];

        // When:
        $response = $this->postJson("{$this->apiBase}/auth/register", $data);

        // Then:
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

    public function test_a_registered_user_can_login(): void
    {
        // Show exceptions instead of catching them
        // $this->withoutExceptionHandling();
        
        // Given:
        $data = [
            "name"                  => "Example",
            "last_name"             => "Example Example",
            "phone"                 => "1234567890",
            "email"                 => "email@email.com",
            "password"              => "password",
            "password_confirmation" => "password",
        ];

        // When:
        $this->postJson("{$this->apiBase}/auth/register", $data);
        $response = $this->postJson("{$this->apiBase}/auth/login", [
            "email"=> "email@email.com",
            "password"=> "password",
        ]);

        // Then:
        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => ['token']]);
    }

    public function test_email_must_be_required(): void
    {
        // Show exceptions instead of catching them
        // $this->withoutExceptionHandling();
        
        // Given:
        $data = [
            "name"                  => "Example",
            "last_name"             => "Example Example",
            "phone"                 => "1234567890",
            "email"                 => "",
            "password"              => "password",
            "password_confirmation" => "password",
        ];

        // When:
        $response = $this->postJson("{$this->apiBase}/auth/register", $data);

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
        // Show exceptions instead of catching them
        // $this->withoutExceptionHandling();
        
        // Given:
        $data = [
            "name"                  => "Example",
            "last_name"             => "Example Example",
            "phone"                 => "1234567890",
            "email"                 => "asasdadefinsdov",
            "password"              => "password",
            "password_confirmation" => "password",
        ];

        // When:
        $response = $this->postJson("{$this->apiBase}/auth/register", $data);

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

    public function test_email_must_be_unique(): void
    {
        // Show exceptions instead of catching them
        // $this->withoutExceptionHandling();
        
        // Given:
        User::factory()->create(['email'=> 'email@email.com']);
        
        $data = [
            "name"                  => "Example",
            "last_name"             => "Example Example",
            "phone"                 => "1234567890",
            "email"                 => "email@email.com",
            "password"              => "password",
            "password_confirmation" => "password",
        ];

        // When:
        $response = $this->postJson("{$this->apiBase}/auth/register", $data);

        // Then:
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

    public function test_password_must_be_required(): void
    {
        // Show exceptions instead of catching them
        // $this->withoutExceptionHandling();
        
        // Given:
        $data = [
            "name"                  => "Example",
            "last_name"             => "Example Example",
            "phone"                 => "1234567890",
            "email"                 => "email@email.com",
            "password"              => "",
            "password_confirmation" => "password",
        ];

        // When:
        $response = $this->postJson("{$this->apiBase}/auth/register", $data);

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
            "name"                  => "Example",
            "last_name"             => "Example Example",
            "phone"                 => "1234567890",
            "email"                 => "email@email.com",
            "password"              => "pass",
            "password_confirmation" => "pass",
        ];

        // When:
        $response = $this->postJson("{$this->apiBase}/auth/register", $data);

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
            "name"                  => "Example",
            "last_name"             => "Example Example",
            "phone"                 => "1234567890",
            "email"                 => "email@email.com",
            "password"              => "password",
            "password_confirmation" => "",
        ];

        // When:
        $response = $this->postJson("{$this->apiBase}/auth/register", $data);

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
            "name"                  => "Example",
            "last_name"             => "Example Example",
            "phone"                 => "1234567890",
            "email"                 => "email@email.com",
            "password"              => "password",
            "password_confirmation" => "different_password",
        ];

        // When:
        $response = $this->postJson("{$this->apiBase}/auth/register", $data);

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

    public function test_name_must_be_required(): void
    {
        // Show exceptions instead of catching them
        // $this->withoutExceptionHandling();
        
        // Given:
        $data = [
            "name"                  => "",
            "last_name"             => "Example Example",
            "phone"                 => "1234567890",
            "email"                 => "email@email.com",
            "password"              => "password",
            "password_confirmation" => "password",
        ];

        // When:
        $response = $this->postJson("{$this->apiBase}/auth/register", $data);

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
            "name"                  => 1234567890,
            "last_name"             => "Example Example",
            "phone"                 => "1234567890",
            "email"                 => "email@email.com",
            "password"              => "password",
            "password_confirmation" => "password",
        ];

        // When:
        $response = $this->postJson("{$this->apiBase}/auth/register", $data);

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
            "name"                  => "E",
            "last_name"             => "Example Example",
            "phone"                 => "1234567890",
            "email"                 => "email@email.com",
            "password"              => "password",
            "password_confirmation" => "password",
        ];

        // When:
        $response = $this->postJson("{$this->apiBase}/auth/register", $data);

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
            "name"                  => "Example",
            "last_name"             => "",
            "phone"                 => "1234567890",
            "email"                 => "email@email.com",
            "password"              => "password",
            "password_confirmation" => "password",
        ];

        // When:
        $response = $this->postJson("{$this->apiBase}/auth/register", $data);

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
            "name"                  => "Example",
            "last_name"             => 1234567890,
            "phone"                 => "1234567890",
            "email"                 => "email@email.com",
            "password"              => "password",
            "password_confirmation" => "password",
        ];

        // When:
        $response = $this->postJson("{$this->apiBase}/auth/register", $data);

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
            "name"                  => "Example",
            "last_name"             => "E",
            "phone"                 => "1234567890",
            "email"                 => "email@email.com",
            "password"              => "password",
            "password_confirmation" => "password",
        ];

        // When:
        $response = $this->postJson("{$this->apiBase}/auth/register", $data);

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