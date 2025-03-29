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
        $supportRequest = SupportRequest::orderBy('id', 'desc')->get();
        return ApiResponse::success(
            data: compact('supportRequest'),
            message: __('messages.support_request.retrieved_all')
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => 'required|date',
            'location' => 'required|string',
            'detail' => 'required|string',
        ]);

        // When creating a new support request, the user_id is the authenticated user
        $data['user_id'] = auth()->guard('api')->user()->id;
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