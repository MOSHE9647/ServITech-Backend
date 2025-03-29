<?php

namespace App\Http\Controllers\Model;

use App\Http\Controllers\Controller;
use App\Models\SupportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Responses\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

class SupportRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        // Check if the user is authenticated
        // If the user is not authenticated, return an error response
        if (! auth()->guard('api')->check()) {
            return ApiResponse::error(
                message: __('auth.unauthenticated'),
                status: Response::HTTP_UNAUTHORIZED,
            );
        }

        // Get the authenticated user id
        $authenticatedUserID = auth()->guard('api')->user()->id;
        $supportRequests = SupportRequest::where('user_id', $authenticatedUserID)
            ->orderBy('id', 'desc')
            ->get();

        // Return the support requests
        return ApiResponse::success(
            data: compact('supportRequests'),
            message: __('messages.support_request.retrieved_all')
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        // Validate the request data
        $data = $request->validate([
            'date' => 'required|date',
            'location' => 'required|string',
            'detail' => 'required|string',
        ]);

        // Check if the user is authenticated
        // If the user is not authenticated, return an error response
        if (! auth()->guard('api')->check()) {
            return ApiResponse::error(
                message: __('auth.unauthenticated'),
                status: Response::HTTP_UNAUTHORIZED,
            );
        }

        // Get the authenticated user id
        // When creating a new support request, the user_id is the authenticated user
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
     */
    public function show(SupportRequest $supportRequest): JsonResponse
    {
        if (! auth()->guard('api')->check()) {
            return ApiResponse::error(
                message: __('auth.unauthenticated'),
                status: Response::HTTP_UNAUTHORIZED,
            );
        }

        // Check if the authenticated user is the owner of the support request
        if ($supportRequest->user_id !== auth()->guard('api')->user()->id) {
            return ApiResponse::error(
                message: __('messages.not_found', ['attribute' => SupportRequest::class]),
                status: Response::HTTP_NOT_FOUND,
            );
        }

        // Return the support request
        return ApiResponse::success(
            message: __('messages.support_request.retrieved'),
            data: compact('supportRequest'),
        );
    }

    /**
     * Update the specified resource in storage.
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
     */
    public function destroy(SupportRequest $supportRequest): JsonResponse
    {
        return ApiResponse::success(
            data: compact('supportRequest'),
            message: __('messages.support_request.deleted'),
        );
    }
}