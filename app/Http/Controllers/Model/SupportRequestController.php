<?php

namespace App\Http\Controllers\Model;

use App\Http\Controllers\Controller;
use App\Models\SupportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Responses\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SupportRequestController for managing support requests.
 *
 * @OA\Schema(
 *     schema="CreateSupportRequest",
 *     type="object",
 *     required={"date", "location", "detail"},
 *     @OA\Property(property="date", type="string", format="date", example="2025-03-29"),
 *     @OA\Property(property="location", type="string", example="New York"),
 *     @OA\Property(property="detail", type="string", example="The system is not responding."),
 * )
 *
 * @OA\Schema(
 *     schema="UpdateSupportRequest",
 *     type="object",
 *     required={"date", "location", "detail"},
 *     @OA\Property(property="date", type="string", format="date", example="2025-03-30"),
 *     @OA\Property(property="location", type="string", example="Los Angeles"),
 *     @OA\Property(property="detail", type="string", example="The issue has been updated."),
 * )
 */
class SupportRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/{version}/support-requests",
     *     summary="Get all support requests",
     *     tags={"Support Requests"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="version",
     *         in="path",
     *         required=true,
     *         description="API version",
     *         @OA\Schema(type="string", example="v1")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of support requests retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Support requests retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="supportRequests", type="array",
     *                     @OA\Items(ref="#/components/schemas/CreateSupportRequest")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        // Ensure the user is authenticated
        if (!auth()->guard('api')->check()) {
            return ApiResponse::error(
                message: __('auth.unauthenticated'),
                status: Response::HTTP_UNAUTHORIZED,
            );
        }

        // Retrieve support requests for the authenticated user
        $authenticatedUserID = auth()->guard('api')->user()->id;
        $supportRequests = SupportRequest::where('user_id', $authenticatedUserID)
            ->orderBy('id', 'desc')
            ->get();

        return ApiResponse::success(
            data: compact('supportRequests'),
            message: __('messages.support_request.retrieved_all')
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *     path="/api/{version}/support-requests",
     *     summary="Create a new support request",
     *     tags={"Support Requests"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="version",
     *         in="path",
     *         required=true,
     *         description="API version",
     *         @OA\Schema(type="string", example="v1")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CreateSupportRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Support request created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=201),
     *             @OA\Property(property="message", type="string", example="Support request created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="supportRequest", ref="#/components/schemas/CreateSupportRequest")
     *             )
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        // Validate the request data
        $data = $request->validate([
            'date' => 'required|date',
            'location' => 'required|string',
            'detail' => 'required|string',
        ]);

        // Ensure the user is authenticated
        if (!auth()->guard('api')->check()) {
            return ApiResponse::error(
                message: __('auth.unauthenticated'),
                status: Response::HTTP_UNAUTHORIZED,
            );
        }

        // Create the support request for the authenticated user
        $user = auth()->guard('api')->user();
        $data = array_merge($data, ['user_id' => $user->id]);
        $supportRequest = SupportRequest::create($data);

        return ApiResponse::success(
            message: __('messages.support_request.created'),
            data: compact('supportRequest'),
            status: Response::HTTP_CREATED,
        );
    }

    /**
     * Display the specified resource.
     *
     * @OA\Get(
     *     path="/api/{version}/support-requests/{id}",
     *     summary="Get a specific support request",
     *     tags={"Support Requests"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="version",
     *         in="path",
     *         required=true,
     *         description="API version",
     *         @OA\Schema(type="string", example="v1")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the support request",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Support request retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Support request retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="supportRequest", ref="#/components/schemas/CreateSupportRequest")
     *             )
     *         )
     *     )
     * )
     */
    public function show(SupportRequest $supportRequest): JsonResponse
    {
        // Ensure the user is authenticated
        if (!auth()->guard('api')->check()) {
            return ApiResponse::error(
                message: __('auth.unauthenticated'),
                status: Response::HTTP_UNAUTHORIZED,
            );
        }

        // Ensure the authenticated user owns the support request
        if ($supportRequest->user_id !== auth()->guard('api')->user()->id) {
            return ApiResponse::error(
                message: __('messages.not_found', ['attribute' => SupportRequest::class]),
                status: Response::HTTP_NOT_FOUND,
            );
        }

        return ApiResponse::success(
            message: __('messages.support_request.retrieved'),
            data: compact('supportRequest'),
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Put(
     *     path="/api/{version}/support-requests/{id}",
     *     summary="Update a support request",
     *     tags={"Support Requests"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="version",
     *         in="path",
     *         required=true,
     *         description="API version",
     *         @OA\Schema(type="string", example="v1")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the support request",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UpdateSupportRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Support request updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Support request updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="supportRequest", ref="#/components/schemas/UpdateSupportRequest")
     *             )
     *         )
     *     )
     * )
     */
    public function update(Request $request, SupportRequest $supportRequest): JsonResponse
    {
        $data = $request->validate([
            'date' => 'required|date',
            'location' => 'required|string',
            'detail' => 'required|string',
        ]);

        $supportRequest->update($data);

        return ApiResponse::success(
            data: compact('supportRequest'),
            message: __('messages.support_request.updated'),
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @OA\Delete(
     *     path="/api/{version}/support-requests/{id}",
     *     summary="Delete a support request",
     *     tags={"Support Requests"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="version",
     *         in="path",
     *         required=true,
     *         description="API version",
     *         @OA\Schema(type="string", example="v1")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the support request",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Support request deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Support request deleted successfully")
     *         )
     *     )
     * )
     */
    public function destroy(SupportRequest $supportRequest): JsonResponse
    {
        if (!auth()->guard('api')->check()) {
            return ApiResponse::error(
                message: __('auth.unauthenticated'),
                status: Response::HTTP_UNAUTHORIZED,
            );
        }

        if ($supportRequest->user_id !== auth()->guard('api')->user()->id) {
            return ApiResponse::error(
                message: __('messages.not_found', ['attribute' => SupportRequest::class]),
                status: Response::HTTP_NOT_FOUND,
            );
        }

        $supportRequest->delete();

        return ApiResponse::success(
            message: __('messages.support_request.deleted'),
        );
    }
}