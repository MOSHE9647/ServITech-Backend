<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Http\Responses\ApiResponse;
use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="RegisterUserRequest",
 *     type="object",
 *     required={"name", "email", "password"},
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
 *     @OA\Property(property="password", type="string", format="password", example="password123")
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
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/register",
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
        $user = User::create($request->validated());

        return ApiResponse::success(
            __('messages.user_registered'), 
            $user->toArray(), 
            Response::HTTP_CREATED
        );
    }

    /**
     * @OA\Post(
     *     path="/api/login",
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
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return $this->authenticationError(!$user ? 'email' : 'password');
        }

        $token = $user->createToken('authToken', ['*'], now()->addMinutes(60))->plainTextToken;

        return ApiResponse::success(__('messages.user_logged_in'), ['token' => $token]);
    }

    /**
     * @OA\Get(
     *     path="/api/profile",
     *     summary="Get user profile",
     *     tags={"Auth"},
     *     @OA\Response(
     *         response=200,
     *         description="User profile retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponse")
     *     )
     * )
     */
    public function profile()
    {
        $userData = Auth::guard('sanctum')->user();
        if (!$userData) {
            return ApiResponse::error(__('messages.unauthorized'), [], Response::HTTP_UNAUTHORIZED);
        }

        return ApiResponse::success(__('messages.user_details'), $userData->toArray());
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     summary="Logout a user",
     *     tags={"Auth"},
     *     @OA\Response(
     *         response=200,
     *         description="User logged out successfully",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponse")
     *     )
     * )
     */
    public function logout()
    {
        optional(Auth::guard('sanctum')->user())->tokens()->delete();

        return ApiResponse::success(__('messages.successfully_logged_out'));
    }

    /**
     * @OA\Post(
     *     path="/api/forgot-password",
     *     summary="Send password reset link",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="email", type="string", format="email")
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
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return ApiResponse::error(
                __('messages.user_not_found'), 
                ['email' => [__('messages.email_not_found')]], 
                Response::HTTP_NOT_FOUND
            );
        }

        $token = $user->createToken('passwordResetToken', ['*'], now()->addMinutes(60))->plainTextToken;

        Mail::to($user->email)->send(new PasswordResetMail($token, $user));
        return ApiResponse::success(__('messages.password_reset_link_sent'));
    }

    /**
     * @OA\Post(
     *     path="/api/reset-password",
     *     summary="Reset user password",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="reset_token", type="string"),
     *             @OA\Property(property="password", type="string", format="password"),
     *             @OA\Property(property="password_confirmation", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset successfully",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponse")
     *     )
     * )
     */
    public function resetPassword(Request $request)
    {
        // Validate the request
        $request->validate([
            'token' => 'required',
            'reset_token' => 'required',
            'password' => 'required|confirmed|min:8',
        ]);

        // Find the user with the token
        $token = $request->reset_token;
        $user = User::where('password_reset_token', $token)->first();
        if (!$user || $user->tokenExpired()) {
            return ApiResponse::error(__('messages.invalid_or_expired_token'), [], Response::HTTP_BAD_REQUEST);

            // session()->flash('auth_error', __('messages.invalid_or_expired_token'));
            // return view('auth.reset');
        }

        // Reset the password
        $user->password = bcrypt($request->password);
        $user->password_reset_token = null;
        $user->save();

        session()->flash('success', __('messages.password_reset_success'));
        return view('auth.reset');
    }

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

        return ApiResponse::error(
            $messages[$field]['message'],
            $messages[$field]['errors'],
            Response::HTTP_UNAUTHORIZED
        );
    }
}