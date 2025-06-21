<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdatePasswordRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Class UserController for handling user management.
 * This controller provides methods for managing user profiles,
 * including retrieving profile information, updating basic information,
 * and updating user passwords.
 */
class UserController extends Controller
{
    /**
     * Get the authenticated user profile.
     * 
     * This method retrieves the profile information of the currently authenticated user
     * and returns it in a JSON response using the UserResource.
     *
     * @return JsonResponse A JSON response containing the authenticated user's profile information.
     * @throws \Exception If there is an error retrieving the user information.
     */
    public function profile(): JsonResponse
    {
        /**
         * Get the authenticated user from the API guard
         * and transform it using the UserResource
         */
        $user = UserResource::make(auth()->user());
        
        // Return a successful response with the user profile information
        return ApiResponse::success(
            data: compact('user'), 
            message: __('messages.user.info_retrieved')
        );
    }

    /**
     * Update the user basic information.
     * 
     * This method handles the update of the authenticated user's basic information
     * by validating the request data and updating the user record in the database.
     *
     * @param UpdateUserRequest $request The request object containing the data
     * for updating the user's basic information.
     * @return JsonResponse A JSON response indicating the success of the operation.
     * @throws \Exception If there is an error during the update process.
     */
    public function updateBasicInformation(UpdateUserRequest $request): JsonResponse
    {
        /**
         * Validate the request data and update the authenticated user's information.
         * The request is validated using the UpdateUserRequest class
         * which contains the validation rules for the update request.
         */
        auth()->user()->update($request->validated());

        /**
         * Refresh the user instance to get the updated information
         * and transform it using the UserResource for consistent output.
         */
        $user = UserResource::make(auth()->user()->fresh() ?? []);
        
        // Return a successful response with the updated user information
        return ApiResponse::success(
            data: compact('user'), 
            message: __('messages.user.info_updated')
        );
    }

    /**
     * Update the user password.
     * 
     * This method handles the update of the authenticated user's password
     * by validating the request data and updating the password in the database.
     *
     * @param UpdatePasswordRequest $request The request object containing the data
     * for updating the user's password.
     * @return JsonResponse A JSON response indicating the success of the operation.
     * @throws \Exception If there is an error during the password update process.
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        /**
         * Validate the request data and update the authenticated user's password.
         * The request is validated using the UpdatePasswordRequest class
         * which contains the validation rules for the password update request.
         * The password is hashed using bcrypt before storing it in the database.
         */
        auth()->user()->update([
            'password'=> bcrypt($request->get('password')),
        ]);

        // Return a successful response indicating the password was updated
        return ApiResponse::success(message: __('messages.password.updated'));
    }
}
