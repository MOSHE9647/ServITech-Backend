<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    /**
     * Register API - POST Method <br>
     * Validate the user input and register the user.
     * 
     * @param \App\Http\Requests\RegisterUserRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(RegisterUserRequest $request) {
        // Create a new user in the database
        $user = User::create($request->validated());

        // Return the user details
        return ApiResponse::success(
            'User registered successfully', 
            $user->toArray(), 
            Response::HTTP_CREATED
        );
    }

    /**
     * Login API - POST Method <br>
     * Validate the user input and login the user.
     * 
     * @param \App\Http\Requests\LoginUserRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginUserRequest $request)
    {
        // Validate the user input
        $data = $request->validated();

        // Verify the user credentials
        $user = User::where('email', $data['email'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return $this->authenticationError(!$user ? 'email' : 'password');
        }

        // Generate the user token
        $token = $user->createToken(
            'authToken', 
            ['*'], 
            now()->addMinutes(60)
        )->plainTextToken;

        // Return the token
        return ApiResponse::success('User logged in successfully', ['token' => $token]);
    }

    /**
     * Profile API - GET Method <br>
     * Get the user profile details.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile() {
        // Get the user data from the token payload
        $userData = Auth::guard('sanctum')->user();
        if (!$userData) {
            return ApiResponse::error('Unauthorized.', [], Response::HTTP_UNAUTHORIZED);
        }

        // Return the user data
        return ApiResponse::success(
            'User profile details',
            $userData->toArray()
        );
    }

    /**
     * Logout API - GET Method <br>
     * Logout the user and delete the tokens.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout() {
        // Delete the user tokens
        optional(Auth::guard('sanctum')->user())->tokens()->delete();

        // Return the success response
        return ApiResponse::success('Successfully logged out.');
    }

    /**
     * Handle the authentication error response.
     * 
     * @param string $field
     * @return \Illuminate\Http\JsonResponse
     */
    private function authenticationError(string $field)
    {
        // Array of error messages
        $messages = [
            'email' => [
                'message' => 'User not found.',
                'errors'  => ['email' => ['Email not found. Please register first.']]
            ],
            'password' => [
                'message' => 'Password didn\'t match.',
                'errors'  => ['password' => ['Password didn\'t match. Please try again.']]
            ]
        ];

        // Return the error response
        return ApiResponse::error(
            $messages[$field]['message'],
            $messages[$field]['errors'],
            Response::HTTP_UNAUTHORIZED
        );
    }
}