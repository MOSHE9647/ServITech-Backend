<?php

namespace App\Http\Controllers;

use App\Enums\UserRoles;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Http\Responses\MessageResponse;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use OpenApi\Annotations as OA;

/**
 * Class AuthController for handling user authentication.
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
 *    schema="RegisterUserRequest",
 *    type="object",
 *    required={"email", "password", "password_confirmation", "name", "last_name"},
 *    @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
 *    @OA\Property(property="password", type="string", format="password", example="password123"),
 *    @OA\Property(property="password_confirmation", type="string", format="password", example="password123"),
 *    @OA\Property(property="name", type="string", example="John"),
 *    @OA\Property(property="last_name", type="string", example="Doe")
 * )
 * 
 * @OA\Schema(
 *    schema="ResetPasswordRequest",
 *    type="object",
 *    required={"email", "token", "password", "password_confirmation"},
 *    @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
 *    @OA\Property(property="token", type="string", example="1234567890"),
 *    @OA\Property(property="password", type="string", format="password", example="password123"),
 *    @OA\Property(property="password_confirmation", type="string", format="password", example="password123")
 * )
 * 
 * @OA\Schema(
 *    schema="MessageResponse",
 *    type="object",
 *    @OA\Property(property="title", type="string", example="Your password has been reset."),
 *    @OA\Property(property="type", type="string", example="success")
 * )
 */
class AuthController extends Controller
{
    /**
     * Login a user.
     * 
     * @OA\Post(
     *     path="/api/{version}/auth/login",
     *     summary="Login a user",
     *     tags={"Auth"},
     *     @OA\Parameter(
     *         name="version",
     *         in="path",
     *         required=true,
     *         description="API version",
     *         @OA\Schema(type="string", example="v1")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/LoginUserRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User logged in successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="User logged in successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."),
     *                 @OA\Property(property="expires_in", type="integer", example=3600)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="We can\'t find a user with that email address. / Invalid credentials",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=401),
     *             @OA\Property(property="message", type="string", example="We can\'t find a user with that email address. / Invalid credentials")
     *         )
     *     )
     * )
     * 
     * @param \App\Http\Requests\LoginUserRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginUserRequest $request): JsonResponse 
    {
        // Validate the request and get the credentials
        // The request is validated using the LoginUserRequest class
        // which contains the validation rules for the login request
        // The credentials are extracted from the request
        // using the request() helper function
        $request->validated();
        $credentials = request(['email', 'password']);

        // Attempt to authenticate the user using the credentials
        // The auth() helper function is used to get the authentication guard
        // The attempt() method is called on the guard to check if the credentials are valid
        // If the credentials are valid, a token is generated
        // If the credentials are invalid, an error response is returned
        // The error response contains a status code and a message
        // indicating that the credentials are invalid
        // The message is translated using the __() helper function
        // The messages are defined in the lang/en and lang/es folder
        if (! $token = auth()->guard('api')->attempt($credentials)) {
            // If the token is not generated, it means the credentials are invalid
            $user = User::where('email', $request->email)->first();
            
            // If the user is not found, return an error response
            // indicating that the user does not exist
            if (!$user) {
                return ApiResponse::error(
                    status: Response::HTTP_UNAUTHORIZED,
                    message: __('passwords.user'),
                    errors: ['email' => __('messages.user.not_found')]
                );
            }
    
            // If the user is found, return an error response
            // indicating that the credentials are invalid
            return ApiResponse::error(
                status: Response::HTTP_UNAUTHORIZED,
                message: __('messages.user.invalid_credentials'),
                errors: ['password' => __('messages.user.invalid_credentials')]
            );
        }

        // If the token is generated, it means the credentials are valid
        // The token is returned in the response among with the user and the expiration time
        $user = auth()->guard('api')->user();
        $expiresIn = auth('api')->factory()->getTTL() * 60;

        return ApiResponse::success(message: __('messages.user.logged_in'),
        data: [
            'user' => UserResource::make($user),
            'token' => $token,
            'expires_in' => $expiresIn
        ]);
    }

    /**
     * Register a new user.
     * 
     * @OA\Post(
     *     path="/api/{version}/auth/register",
     *     summary="Register a new user",
     *     tags={"Auth"},
     *     @OA\Parameter(
     *         name="version",
     *         in="path",
     *         required=true,
     *         description="API version",
     *         @OA\Schema(type="string", example="v1")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/RegisterUserRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=201),
     *             @OA\Property(property="message", type="string", example="User registered successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *                     @OA\Property(property="name", type="string", example="John"),
     *                     @OA\Property(property="last_name", type="string", example="Doe")
     *                 )
     *             )
     *         )
     *     )
     * )
     * 
     * @param \App\Http\Requests\RegisterUserRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(RegisterUserRequest $request): JsonResponse
    {
        // Validate the request and create a new user
        // The request is validated using the RegisterUserRequest class
        // which contains the validation rules for the registration request
        $user = User::create($request->validated());
        $user->assignRole(UserRoles::USER);

        // If the user is created successfully, a success response is returned
        return ApiResponse::success(
            message: __('messages.user.registered'),
            status: Response::HTTP_CREATED,
            data: ['user' => UserResource::make($user)]
        );
    }

    /**
     * Send a password reset link to the user.
     * 
     * @OA\Post(
     *     path="/api/{version}/auth/reset-password",
     *     summary="Send password reset link",
     *     tags={"Auth"},
     *     @OA\Parameter(
     *         name="version",
     *         in="path",
     *         required=true,
     *         description="API version",
     *         @OA\Schema(type="string", example="v1")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset link sent successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Password reset link sent successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Your password could not be reset.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=500),
     *             @OA\Property(property="message", type="string", example="Your password could not be reset.")
     *         )
     *     )
     * )
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendResetLink(Request $request): JsonResponse
    {
        // Validate the request
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        // Send the password reset link
        // The sendResetLink() method is called on the Password facade
        $status = Password::sendResetLink(
            $request->only('email')
        );

        // Check if the password reset link was sent successfully
        // The status is checked against the Password::RESET_LINK_SENT constant
        // If the status is Password::RESET_LINK_SENT, a success response is returned
        $sent = $status === Password::RESET_LINK_SENT;
        return $sent 
            ? ApiResponse::success(status: Response::HTTP_OK, message: __('passwords.sent')) 
            : ApiResponse::error(status: Response::HTTP_INTERNAL_SERVER_ERROR, message: __('passwords.failed'));
    }

    /**
     * Reset the user's password.
     * 
     * @OA\Put(
     *     path="/api/{version}/auth/reset-password",
     *     summary="Reset the user's password",
     *     tags={"Auth"},
     *     @OA\Parameter(
     *         name="version",
     *         in="path",
     *         required=true,
     *         description="API version",
     *         @OA\Schema(type="string", example="v1")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ResetPasswordRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Your password has been reset.",
     *         @OA\MediaType(
     *             mediaType="text/html",
     *             @OA\Schema(
     *                 type="string",
     *                 example="<html><body>Success: Your password has been reset.</body></html>"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="We can't find a user with that email address. / This password reset token is invalid.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=500),
     *             @OA\Property(property="message", type="string", example="We can't find a user with that email address. / This password reset token is invalid.")
     *         )
     *     )
     * )
     * 
     * @param \App\Http\Requests\ResetPasswordRequest $request
     * @return \Illuminate\View\View
     */
    public function resetPassword(ResetPasswordRequest $request): View
    {
        // Validate the request
        $status = Password::reset(
            $request->validated(),
            function ($user, $password) {
                // Update the user's password
                $user->forceFill([
                    'password' => bcrypt($password)
                ])->setRememberToken(Str::random(60));

                $user->save();
                event(new PasswordReset($user));
            }
        );

        // Check the status of the password reset
        // The status is checked against the Password::PASSWORD_RESET constant
        // If the status is Password::PASSWORD_RESET, a success message is returned
        $message = match ($status) {
            Password::PASSWORD_RESET => __('passwords.reset'),
            Password::INVALID_USER => __('passwords.user'),
            Password::INVALID_TOKEN => __('passwords.token'),
            default => __('passwords.failed'),
        };

        // Determine the type of message to display
        // The type is determined based on the status of the password reset
        // If the status is Password::PASSWORD_RESET, the type is success
        $type = match ($status) {
            Password::PASSWORD_RESET => MessageResponse::TYPE_SUCCESS,
            default => __(MessageResponse::TYPE_ERROR),
        };

        // Return a success message if the password was reset successfully
        // or an error message if the password could not be reset.
        $message = MessageResponse::create($message, $type);
        return view('auth.reset-password', compact('message'));
    }

    /**
     * @OA\Post(
     *     path="/api/{version}/auth/logout",
     *     summary="Logs out the authenticated user",
     *     tags={"Auth"},
     *     @OA\Parameter(
     *         name="version",
     *         in="path",
     *         required=true,
     *         description="API version",
     *         @OA\Schema(type="string", example="v1")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User successfully logged out",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User logged out successfully"),
     *             @OA\Property(property="status", type="integer", example=200)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="User already logged out",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User already logged out"),
     *             @OA\Property(property="status", type="integer", example=401)
     *         )
     *     )
     * )
     *
     * Logs out the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(): JsonResponse
    {
        // Verifies if the user is already logged out
        if (! auth()->guard('api')->check()) {
            return ApiResponse::error(
                message: __('messages.user.already_logged_out'),
                status: Response::HTTP_UNAUTHORIZED
            );
        }

        // Logs out the user
        auth()->guard('api')->logout();

        // Returns a success response
        return ApiResponse::success(
            message: __('messages.user.logged_out'),
            status: Response::HTTP_OK
        );
    }
}
