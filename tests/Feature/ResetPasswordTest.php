<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use RefreshDatabase; // RefreshDatabase trait to reset the database after each test

    protected $token = '';
    protected $email = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class); // Seed the database with test users
    }

    public function sendResetPassword() {
        // Show exceptions instead of catching them
        // $this->withoutExceptionHandling();

        Notification::fake(); // Prevent notifications from being sent

        // Given:
        $data = [
            'email' => 'example@example.com',
        ];

        // When:
        $response = $this->postJson("{$this->apiBase}/auth/reset-password", $data);

        // Then:
        $response->assertStatus(200);
        $response->assertJsonFragment(['message' => __('passwords.sent')]);

        $user = User::find(1)->first();
        $this->assertNotNull($user, 'User with ID 1 does not exist.');

        Notification::assertSentTo([$user], 
            function(ResetPasswordNotification $notification) {
                $url = $notification->url;

                $parts = parse_url($url);
                parse_str($parts['query'], $query);
                
                $this->token = $query['token'];
                $this->email = urldecode($query['email']);

                return 
                    strpos($url, 'reset-password?token=') !== false && 
                    strpos($url, 'email=') !== false
                ;
            }
        );
    }

    public function test_an_existing_user_can_reset_their_password(): void
    {
        // Show exceptions instead of catching them
        // $this->withoutExceptionHandling();

        // Given:
        $this->sendResetPassword();

        // When:
        $response = $this->putJson("{$this->apiBase}/auth/reset-password?token={$this->token}", [
            'email' => $this->email,
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);
        // dd($response);

        // Then:
        $response->assertStatus(200);
        $response->assertHeader('content-type','text/html; charset=UTF-8');
        $response->assertSeeText(__('passwords.reset'));
        
        $user = User::find(1)->first();
        $this->assertNotNull($user, 'User with ID 1 does not exist.');

        $this->assertTrue(Hash::check('newpassword', $user->password));
    }

    public function test_email_must_be_required(): void
    {
        // Given:
        $data = [
            'email' => '',
        ];

        // When:
        $response = $this->postJson("{$this->apiBase}/auth/reset-password", $data);

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
        $data = [
            'email' => 'notanemail',
        ];

        // When:
        $response = $this->postJson("{$this->apiBase}/auth/reset-password", $data);

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

    public function test_email_must_be_an_existing_email(): void
    {
        // Given:
        $data = [
            'email' => 'notexistingemail@example.com',
        ];

        // When:
        $response = $this->postJson("{$this->apiBase}/auth/reset-password", $data);
        // dd($response->json());

        // Then:
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'status', 'message', 'errors' => ['email']
        ]);
        $response->assertJsonFragment([
            'email' => [
                __('validation.exists', [
                    'attribute' => __('validation.attributes.email')
                ])
            ]
        ]);
    }

    public function test_email_must_be_associated_with_the_token(): void
    {
        // Show exceptions instead of catching them
        // $this->withoutExceptionHandling();

        // Given:
        $this->sendResetPassword();

        // When:
        $response = $this->putJson("{$this->apiBase}/auth/reset-password?token={$this->token}", [
            'email' => 'fake@email.com',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);

        // Then:
        $response->assertStatus(200);
        $response->assertHeader('content-type','text/html; charset=UTF-8');
        $response->assertSeeText(__('passwords.user'));
    }

    public function test_password_must_be_required(): void
    {
        // Show exceptions instead of catching them
        // $this->withoutExceptionHandling();

        // When:
        $response = $this->putJson("{$this->apiBase}/auth/reset-password?token={$this->token}", [
            'email' => $this->email,
            'password' => '',
            'password_confirmation' => 'newpassword',
        ]);

        // Then:
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['password']]);
    }

    public function test_password_must_have_at_least_8_characters(): void
    {
        // Show exceptions instead of catching them
        // $this->withoutExceptionHandling();

        // When:
        $response = $this->putJson("{$this->apiBase}/auth/reset-password?token={$this->token}", [
            'email' => $this->email,
            'password' => 'pass',
            'password_confirmation' => 'pass',
        ]);

        // Then:
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['password']]);
    }

    public function test_password_confirmation_is_required(): void
    {
        // Show exceptions instead of catching them
        // $this->withoutExceptionHandling();

        // When:
        $response = $this->putJson("{$this->apiBase}/auth/reset-password?token={$this->token}", [
            'email' => $this->email,
            'password' => 'newpassword',
            'password_confirmation' => '',
        ]);

        // Then:
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['password']]);
        $response->assertJsonFragment([
            'password' => [
                __('validation.confirmed', [
                    'attribute' => __('validation.attributes.password')
                ]),
            ]
        ]);
    }

    public function test_token_must_be_a_valid_token(): void
    {
        // Show exceptions instead of catching them
        // $this->withoutExceptionHandling();

        // Given:
        $this->sendResetPassword();

        // When:
        $response = $this->putJson("{$this->apiBase}/auth/reset-password?token={$this->token}modified", [
            'email' => $this->email,
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);

        // Then:
        // Then:
        $response->assertStatus(200);
        $response->assertHeader('content-type','text/html; charset=UTF-8');
        $response->assertSeeText(__('passwords.token'));
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
        $user = User::find(1)->first();
        $this->assertNotNull($user, 'User with ID 1 does not exist.');
        
        $response = $this->apiAs($user, 'PUT', "{$this->apiBase}/user/password", $data);
        // dd($response->json());

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