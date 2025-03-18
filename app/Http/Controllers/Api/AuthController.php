<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Http\Responses\ApiResponse;
use App\Http\Responses\MessageResponse;
use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use OpenApi\Annotations as OA;

/**
 * Class AuthController for handling user authentication.
 * 
 * @OA\Schema(
 *     schema="RegisterUserRequest",
 *     type="object",
 *     required={"name", "email", "password", "password_confirmation"},
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
 *     @OA\Property(property="password", type="string", format="password", example="password123"),
 *     @OA\Property(property="password_confirmation", type="string", format="password", example="password123")
 * )
 * 
 * @OA\Schema(
 *     schema="LoginUserRequest",
 *     type="object",
 *     required={"email", "password"},
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
 *     @OA\Property(property="password", type="string", format="password", example="password123")
 * )
 * 
 * @OA\Schema(
 *     schema="ApiResponse",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="Success"),
 *     @OA\Property(property="data", type="object"),
 *     @OA\Property(property="errors", type="object")
 * )
 * 
 * @OA\Schema(
 *    schema="MessageResponse",
 *    type="object",
 *    @OA\Property(property="title", type="string", example="Success"),
 *    @OA\Property(property="type", type="string", example="success")
 * )
 */
class AuthController extends Controller
{
    /**
     * Register a new user.
     * 
     * @OA\Post(
     *     path="/api/v1/auth/register",
     *     summary="Register a new user",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/RegisterUserRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponse")
     *     )
     * )
     */
    public function register(RegisterUserRequest $request)
    {
        // Create a new user with the validated request data
        $user = User::create($request->validated());

        // Return a success response with the created user data
        return ApiResponse::success(
            __('messages.user_registered'), 
            $user->toArray(), 
            Response::HTTP_CREATED
        );
    }

    /**
     * Login a user.
     * 
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     summary="Login a user",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/LoginUserRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User logged in successfully",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponse")
     *     )
     * )
     */
    public function login(LoginUserRequest $request)
    {
        // Validate the request data
        $data = $request->validated();

        // Find the user by email
        $user = User::where('email', $data['email'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            // Return an authentication error if the user is not found or the password is incorrect
            return $this->authenticationError(!$user ? 'email' : 'password');
        }

        // Create a new token for the user
        $token = $user->createToken('authToken', ['*'], now()->addMinutes(60))->plainTextToken;

        // Return a success response with the token
        return ApiResponse::success(__('messages.user_logged_in'), ['token' => $token]);
    }

    /**
     * Get user profile.
     * 
     * @OA\Get(
     *     path="/api/v1/user/profile",
     *     summary="Get user profile",
     *     tags={"Auth"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User profile retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponse")
     *     )
     * )
     */
    public function profile()
    {
        // Get the authenticated user
        $userData = Auth::guard('sanctum')->user();
        if (!$userData) {
            // Return an error response if the user is not authenticated
            return ApiResponse::error(__('messages.unauthorized'), [], Response::HTTP_UNAUTHORIZED);
        }

        // Return a success response with the user data
        return ApiResponse::success(__('messages.user_details'), $userData->toArray());
    }

    /**
     * Logout a user.
     * 
     * @OA\Post(
     *     path="/api/v1/user/logout",
     *     summary="Logout a user",
     *     tags={"Auth"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User logged out successfully",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponse")
     *     )
     * )
     */
    public function logout()
    {
        // Delete all tokens of the authenticated user
        optional(Auth::guard('sanctum')->user())->tokens()->delete();

        // Return a success response
        return ApiResponse::success(__('messages.successfully_logged_out'));
    }

    /**
     * Send password reset link.
     * 
     * @OA\Post(
     *     path="/api/v1/auth/forgot-password",
     *     summary="Send password reset link",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset link sent",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponse")
     *     )
     * )
     */
    public function forgotPassword(Request $request)
    {
        // Validate the request data
        $request->validate(['email' => 'required|email']);

        // Find the user by email
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            // Return an error response if the user is not found
            return ApiResponse::error(
                __('messages.user_not_found'), 
                ['email' => [__('messages.email_not_found')]], 
                Response::HTTP_NOT_FOUND
            );
        }

        // Generate a password reset token
        $token = bin2hex(random_bytes(50));
        \DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => now(),
            'expires_at' => now()->addMinutes(30),
        ]);

        // Send the password reset email
        Mail::to($user->email)->send(new PasswordResetMail($user, $token));
        return ApiResponse::success(__('messages.password_reset_link_sent'));
    }

    /**
     * Reset the user's password.
     * 
     * @OA\Post(
     *     path="/api/v1/auth/reset-password",
     *     summary="Reset the user's password",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *             @OA\Property(property="reset_token", type="string", example="reset_token_example"),
     *             @OA\Property(property="password", type="string", format="password", example="newpassword123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="newpassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset successfully",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponse")
     *     )
     * )
     */
    public function resetPassword(Request $request): View
    {
        // Validate the request data
        $request->validate([
            'email' => 'required|email',
            'reset_token' => 'required',
            'password' => 'required|confirmed|min:8',
        ]);

        // Retrieve the token data from the password_reset_tokens table
        $tokenData = \DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        // Check if the token is invalid or expired
        if (!$tokenData || !Hash::check($request->reset_token, $tokenData->token) || $tokenData->deleted_at) {
            $message = MessageResponse::create(__('messages.invalid_or_expired_token'), MessageResponse::TYPE_ERROR);
            return view('auth.reset', compact('message'));
        }

        // Find the user by email
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            $message = MessageResponse::create(__('messages.email_not_found'), MessageResponse::TYPE_ERROR);
            return view('auth.reset', compact('message'));
        }

        // Update the user's password
        $user->password = bcrypt($request->password);
        $user->save();

        // Soft delete the token
        \DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->update(['deleted_at' => now()]);

        // Return a success message
        $message = MessageResponse::create(__('messages.password_reset_success'), MessageResponse::TYPE_SUCCESS);
        return view('auth.reset', compact('message'));
    }

    /**
     * Handle authentication errors.
     * 
     * @param string $field The field that caused the authentication error.
     * @return \Illuminate\Http\JsonResponse
     */
    private function authenticationError(string $field)
    {
        $messages = [
            'email' => [
                'message' => __('messages.user_not_found'),
                'errors'  => ['email' => [__('messages.email_not_found')]]
            ],
            'password' => [
                'message' => __("messages.password_mismatch"),
                'errors'  => ['password' => [__("messages.password_mismatch")]]
            ]
        ];

        // Return an error response with the appropriate message and errors
        return ApiResponse::error(
            $messages[$field]['message'],
            $messages[$field]['errors'],
            Response::HTTP_UNAUTHORIZED
        );
    }
}