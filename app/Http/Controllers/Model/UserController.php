<?php

namespace App\Http\Controllers\Model;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Class UserController for handling user management.
 * 
 * @OA\Schema(
 *     schema="UpdateUserRequest",
 *     type="object",
 *     required={"name", "last_name"},
 *     @OA\Property(property="name", type="string", example="John"),
 *     @OA\Property(property="last_name", type="string", example="Doe")
 * )
 * 
 * @OA\Schema(
 *     schema="UpdatePasswordRequest",
 *     type="object",
 *     required={"old_password", "password", "password_confirmation"},
 *     @OA\Property(property="old_password", type="string", format="password", example="oldpassword123"),
 *     @OA\Property(property="password", type="string", format="password", example="newpassword123"),
 *     @OA\Property(property="password_confirmation", type="string", format="password", example="newpassword123")
 * )
 * 
 * @OA\Schema(
 *     schema="UserResource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="John"),
 *     @OA\Property(property="last_name", type="string", example="Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
 *     @OA\Property(property="phone", type="string", example="+1234567890"),
 *     @OA\Property(property="role", type="string", example="user"),
 * )
 */
class UserController extends Controller
{
    /**
     * Get the authenticated User.
     * 
     * @OA\Get(
     *     path="/api/{version}/user/profile",
     *     summary="Get the authenticated user profile",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="version",
     *         in="path",
     *         required=true,
     *         description="API version",
     *         @OA\Schema(type="string", example="v1")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User profile retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="User information retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/UserResource")
     *             )
     *         )
     *     )
     * )
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile(): JsonResponse
    {
        // Get the authenticated user
        // and return the user resource
        $user = UserResource::make(auth()->guard('api')->user());
        return ApiResponse::success(data: compact('user'), message: __('messages.user.info_retrieved'));
    }

    /**
     * Update the user basic information in storage.
     * 
     * @OA\Put(
     *     path="/api/{version}/user/profile",
     *     summary="Update the user basic information",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="version",
     *         in="path",
     *         required=true,
     *         description="API version",
     *         @OA\Schema(type="string", example="v1")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UpdateUserRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User information updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="User information updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/UserResource")
     *             )
     *         )
     *     )
     * )
     * 
     * @param \App\Http\Requests\UpdateUserRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateBasicInformation(UpdateUserRequest $request): JsonResponse
    {
        // Validate the request
        // and update the user information
        // using the authenticated user
        auth()->guard('api')->user()->update($request->validated());

        // Refresh the user instance
        // to get the updated information
        // and return the user resource
        $user = UserResource::make(auth()->guard('api')->user()->fresh() ?? []);
        return ApiResponse::success(compact('user'), message: __('messages.user.info_updated'));
    }

    /**
     * Update the user password in storage.
     * 
     * @OA\Put(
     *     path="/api/{version}/user/password",
     *     summary="Update the user password",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="version",
     *         in="path",
     *         required=true,
     *         description="API version",
     *         @OA\Schema(type="string", example="v1")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UpdatePasswordRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User information updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="User password updated successfully")
     *         )
     *     )
     * )
     * 
     * @param \App\Http\Requests\UpdatePasswordRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        // Validate the request
        // and update the user password
        // using the authenticated user
        auth()->guard('api')->user()->update([
            'password'=> bcrypt($request->get('password')),
        ]);

        // Return a success response
        return ApiResponse::success(message: __('messages.user.password_updated'));
    }
}
