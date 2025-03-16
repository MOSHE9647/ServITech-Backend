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
use Illuminate\Support\Facades\Session;
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
        $user = User::create($request->validated());

        return ApiResponse::success(
            __('messages.user_registered'), 
            $user->toArray(), 
            Response::HTTP_CREATED
        );
    }

    /**
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
        $userData = Auth::guard('sanctum')->user();
        if (!$userData) {
            return ApiResponse::error(__('messages.unauthorized'), [], Response::HTTP_UNAUTHORIZED);
        }

        return ApiResponse::success(__('messages.user_details'), $userData->toArray());
    }

    /**
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
        optional(Auth::guard('sanctum')->user())->tokens()->delete();

        return ApiResponse::success(__('messages.successfully_logged_out'));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/forgot-password",
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

        // Generate a password reset token
        $token = bin2hex(random_bytes(32));
        \DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        Mail::to($user->email)->send(new PasswordResetMail($user, $token));
        return ApiResponse::success(__('messages.password_reset_link_sent'));
    }

    public function resetPassword(Request $request)
    {
        // Validate the request
        $request->validate([
            'email' => 'required|email',
            'reset_token' => 'required',
            'password' => 'required|confirmed|min:8',
        ]);

        // Find the token in the password_reset_tokens table
        $tokenData = \DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        // Check if the token is invalid or expired
        if (!$tokenData || !Hash::check($request->reset_token, $tokenData->token)) {
            $swal = [
                'title' => __('messages.invalid_or_expired_token'),
                'type' => 'error',
            ];
            return view('auth.reset', compact('swal'));
        }

        // Find the user by email
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            $swal = [
                'title'=> __('messages.email_not_found'),
                'type'=> 'error',
            ];
            return view('auth.reset', compact('swal'));
        }

        // Reset the password
        $user->password = bcrypt($request->password);
        $user->save();

        // Delete the token
        \DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        $swal = [
            'title' => __('messages.password_reset_success'),
            'type' => 'success',
        ];
        return view('auth.reset', compact('swal'));
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