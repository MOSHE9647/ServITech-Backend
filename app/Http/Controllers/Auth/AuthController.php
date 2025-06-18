<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRoles;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginUserRequest;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Http\Responses\MessageResponse;
use App\Models\User;
use App\Notifications\PasswordResetSuccessNotification;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AuthController for handling user authentication.
 * This controller provides methods for user login, registration,
 * password reset, and logout functionalities.
 * 
 * For Authentication, it uses JWT (JSON Web Tokens)
 * to manage user sessions and provide secure access to the API.
 */
class AuthController extends Controller implements HasMiddleware
{
    /**
     * Define the middleware for the controller.
     * This method specifies which middleware should be applied to the controller's actions.
     * The 'auth:api' middleware is applied to the 'logout' action,
     * ensuring that only authenticated users can log out.
     * @return Middleware[]
     */
    public static function middleware(): array
    {
        return [
            new Middleware(middleware: 'auth:api', only: ['logout']),
        ];
    }

    /**
     * Login a user.
     * 
     * This method handles user login by validating the request data,
     * attempting to authenticate the user with the provided credentials,
     * and returning a JWT token if the authentication is successful.
     * @unauthenticated Indicates that this endpoint does not requires authentication.
     * 
     * @param LoginUserRequest $request The request containing the user's email and password.
     * @return ApiResponse JSON response containing the user data and JWT token
     * @throws ValidationException If the request validation fails.
     */
    public function login(LoginUserRequest $request): JsonResponse
    {
        // Validate the request and get the credentials
        $request->validated();
        $credentials = request(['email', 'password']);

        /*         
         * Attempt to authenticate the user using the provided credentials.
         * If the credentials are valid, a JWT token is generated.
         * If the credentials are invalid, an error response is returned.
         */
        if (!$token = auth()->attempt($credentials)) {
            // If the token is not generated, it means the credentials are invalid
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                /**
                 * If the user is not found, return an error response 
                 * indicating that the user does not exist.
                 */
                return ApiResponse::error(
                    status: Response::HTTP_BAD_REQUEST,
                    message: __('passwords.user'),
                    errors: ['email' => __('passwords.user')]
                );
            }

            /**
             * If the user is found and the password is incorrect, 
             * return an error response indicating that the credentials are invalid.
             */
            return ApiResponse::error(
                status: Response::HTTP_UNAUTHORIZED,
                message: __('auth.password'),
                errors: ['password' => __('auth.password')]
            );
        }

        /**
         * If the token is generated, it means the credentials are valid.
         * The token is returned in the response among with the user and the expiration time.
         */
        $user = auth()->user();
        $expiresIn = auth()->factory()->getTTL() * 60;

        // Return a success response with the user data, token, and expiration time.
        return ApiResponse::success(
            message: __('messages.user.logged_in'),
            data: [
                'user' => UserResource::make($user),
                'token' => $token,
                'expires_in' => $expiresIn
            ]
        );
    }

    /**
     * Register a new user.
     * 
     * This method handles user registration by validating the request data,
     * and creating a new user in the database.
     * @unauthenticated
     *
     * @param RegisterUserRequest $request The request containing the user registration data.
     * @return JsonResponse JSON response containing the newly created user data
     * @throws ValidationException If the request validation fails.
     */
    public function register(RegisterUserRequest $request): JsonResponse
    {
        /**
         * Validate the request data and create a new user with an USER role.
         * The request is validated using the RegisterUserRequest class
         * which contains the validation rules for the registration request
         */
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
     * This method handles the request to send a password reset link
     * to the user's email address. It validates the request,
     * checks if the email exists in the database, and sends the reset link.
     * @unauthenticated
     * 
     * @param Request $request The request containing the user's email address.
     * @return ApiResponse JSON response indicating the status of the operation
     * @throws \Illuminate\Validation\ValidationException If the request validation fails.
     */
    public function sendResetLink(Request $request): JsonResponse
    {
        // Validate the request
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        /**
         * Send the password reset link. 
         * The sendResetLink() method is called on the Password facade
         */
        $status = Password::sendResetLink(
            $request->only('email')
        );

        /**
         * Check if the password reset link was sent successfully
         * The status is checked against the Password::RESET_LINK_SENT constant
         * If the status is Password::RESET_LINK_SENT, a success response is returned
         */
        $sent = $status === Password::RESET_LINK_SENT;
        return $sent
            ? ApiResponse::success(status: Response::HTTP_OK, message: __('passwords.sent'))
            : ApiResponse::error(status: Response::HTTP_INTERNAL_SERVER_ERROR, message: __('passwords.not_sent'));
    }

    /**
     * Reset the user's password.
     * 
     * This method handles the password reset process by validating the request data,
     * resetting the user's password using the provided token, and returning a view
     * indicating the success or failure of the operation.
     * @unauthenticated
     * 
     * @param ResetPasswordRequest $request The request containing the password reset data.
     * @return View A HTML view indicating the success or failure of the password reset operation.
     * @throws ValidationException If the request validation fails.
     */
    public function resetPassword(ResetPasswordRequest $request): View
    {
        // Variable to store the user whose password is being reset
        $resetUser = null;

        // Validate the request
        $status = Password::reset(
            $request->validated(),
            function ($user, $password) use (&$resetUser) {
                // Store the user reference for later use
                $resetUser = $user;

                // Update the user's password
                $user->forceFill([
                    'password' => bcrypt($password)
                ])->setRememberToken(Str::random(60));

                $user->save();
                event(new PasswordReset($user));
            }
        );

        /**
         * Check the status of the password reset
         * The status is checked against the Password::PASSWORD_RESET constant
         * If the status is Password::PASSWORD_RESET, a success message is returned
         */
        $message = match ($status) {
            Password::PASSWORD_RESET => __('passwords.reset'),
            Password::INVALID_USER => __('passwords.user'),
            Password::INVALID_TOKEN => __('passwords.token'),
            default => __('passwords.failed'),
        };

        /**
         * Determine the type of message to display
         * The type is determined based on the status of the password reset
         * If the status is Password::PASSWORD_RESET, the type is success
         */
        $type = match ($status) {
            Password::PASSWORD_RESET => MessageResponse::TYPE_SUCCESS,
            default => MessageResponse::TYPE_ERROR,
        };

        // Send success notification if password was reset successfully
        if ($status === Password::PASSWORD_RESET && $resetUser) {
            $resetUser->notify(new PasswordResetSuccessNotification());
        }

        /**
         * Return a success message if the password was reset successfully 
         * or an error message if the password could not be reset.
         */
        $message = MessageResponse::create($message, $type);
        return view('auth.reset-password', compact('message'));
    }

    /**
     * Logout the authenticated user.
     * 
     * This method handles the logout process by checking if the user is authenticated,
     * logging them out, and returning a success response.
     *
     * @return JsonResponse JSON response indicating the status of the logout operation
     * @throws AuthenticationException If the user is not authenticated.
     */
    public function logout(): JsonResponse
    {
        // Verifies if the user is already logged out
        if (!auth()->check()) {
            // If the user is not authenticated, return an error response
            return ApiResponse::error(
                message: __('messages.user.already_logged_out'),
                status: Response::HTTP_UNAUTHORIZED
            );
        }

        // Logs out the user
        auth()->logout(true);

        // Returns a success response
        return ApiResponse::success(
            message: __('messages.user.logged_out'),
            status: Response::HTTP_OK
        );
    }
}
