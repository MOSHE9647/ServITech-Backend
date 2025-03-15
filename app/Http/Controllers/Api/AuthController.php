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

class AuthController extends Controller
{
    public function register(RegisterUserRequest $request)
    {
        $user = User::create($request->validated());

        return ApiResponse::success(
            __('messages.user_registered'), 
            $user->toArray(), 
            Response::HTTP_CREATED
        );
    }

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

    public function profile()
    {
        $userData = Auth::guard('sanctum')->user();
        if (!$userData) {
            return ApiResponse::error(__('messages.unauthorized'), [], Response::HTTP_UNAUTHORIZED);
        }

        return ApiResponse::success(__('messages.user_details'), $userData->toArray());
    }

    public function logout()
    {
        optional(Auth::guard('sanctum')->user())->tokens()->delete();

        return ApiResponse::success(__('messages.successfully_logged_out'));
    }

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