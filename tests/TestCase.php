<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

abstract class TestCase extends BaseTestCase
{
    protected $apiBase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiBase = env("API_BASE", "/api/v1");
    }

    protected function apiAs(User $user, string $method, string $uri, array $data = [])
    {
        $headers = [
            'Authorization' => 'Bearer ' . JWTAuth::fromUser($user),
        ];

        return $this->json($method, $uri, $data, $headers);
    }
}
