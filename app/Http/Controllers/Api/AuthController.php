<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    /**
     * Register API - POST Method
     * Validate the user input and register the user.
     * 
     * @param \App\Http\Requests\RegisterUserRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(RegisterUserRequest $request) {
        $user = User::create($request->validated());

        return ApiResponse::success(
            __('User registered successfully'), 
            $user->toArray(), 
            Response::HTTP_CREATED
        );
    }

    /**
     * Login API - POST Method
     * Validate the user input and login the user.
     * 
     * @param \App\Http\Requests\LoginUserRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginUserRequest $request)
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return $this->authenticationError(!$user ? 'email' : 'password');
        }

        $token = $user->createToken('authToken', ['*'], now()->addMinutes(60))->plainTextToken;

        return ApiResponse::success(__('User logged in successfully'), ['token' => $token]);
    }

    /**
     * Profile API - GET Method
     * Get the user profile details.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile() {
        $userData = Auth::guard('sanctum')->user();
        if (!$userData) {
            return ApiResponse::error(__('Unauthorized'), [], Response::HTTP_UNAUTHORIZED);
        }

        return ApiResponse::success(__('User details'), $userData->toArray());
    }

    /**
     * Logout API - GET Method
     * Logout the user and delete the tokens.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout() {
        optional(Auth::guard('sanctum')->user())->tokens()->delete();

        return ApiResponse::success(__('Successfully logged out'));
    }

    /**
     * Forgot Password API - POST Method
     * Send a password reset link to the user.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return ApiResponse::error(
                __('User not found'), 
                ['email' => [__('Email not found. Please register first')]], 
                Response::HTTP_NOT_FOUND
            );
        }

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? ApiResponse::success(__('Password reset link sent successfully'))
            : ApiResponse::error(__('Unable to send password reset link'), 
                ['email' => [__($status)]], 
                Response::HTTP_BAD_REQUEST
            );
    }

    /**
     * Handle the authentication error response.
     * 
     * @param string $field
     * @return \Illuminate\Http\JsonResponse
     */
    private function authenticationError(string $field)
    {
        $messages = [
            'email' => [
                'message' => __('User not found'),
                'errors'  => ['email' => [__('Email not found. Please register first')]]
            ],
            'password' => [
                'message' => __("Password didn't match"),
                'errors'  => ['password' => [__("Password didn't match. Please try again")]]
            ]
        ];

        return ApiResponse::error(
            $messages[$field]['message'],
            $messages[$field]['errors'],
            Response::HTTP_UNAUTHORIZED
        );
    }
}