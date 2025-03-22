<?php

namespace Tests\Feature;

use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
    }

    public function test_an_user_can_logout_successfully()
    {
        // Given:
        $credentials = [
            "email"     => "example@example.com",
            "password"  => "password",
        ];

        $this->postJson("{$this->apiBase}/auth/login", $credentials);

        // When:
        $response = $this->postJson("{$this->apiBase}/auth/logout");

        // Then:
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'message', 'data']);
        $response->assertJsonFragment([
            'status'=> 200,
            'message'=> __('messages.user_logged_out'),
        ]);
    }

    public function test_an_user_is_already_logged_out()
    {
        // Given:
        $credentials = [
            "email"     => "example@example.com",
            "password"  => "password",
        ];

        $this->postJson("{$this->apiBase}/auth/login", $credentials);
        $this->postJson("{$this->apiBase}/auth/logout");

        // When:
        $response = $this->postJson("{$this->apiBase}/auth/logout");

        // Then:
        $response->assertStatus(401);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status'=> 401,
            'message'=> __('messages.user_already_logged_out'),
        ]);
    }
}
