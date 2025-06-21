<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SupportRequest;
use App\Models\User;
use App\Notifications\NewSupportRequestNotification;
use App\Enums\UserRoles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Responses\ApiResponse;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Notification;

/**
 * Class SupportRequestController for managing support requests.
 * This controller handles CRUD operations for support requests,
 * including creating, retrieving, updating, and deleting support requests.
 */
class SupportRequestController extends Controller
{
    /**
     * Display a listing of support requests.
     * 
     * This method retrieves all support requests for the authenticated user from the database,
     * orders them by ID in descending order, and returns them in a JSON response.
     * 
     * @return JsonResponse A JSON response containing the list of support requests.
     * @throws \Exception If there is an error retrieving the support requests.
     */
    public function index(): JsonResponse
    {
        /**
         * Ensure the user is authenticated before proceeding.
         * If not authenticated, return an error response.
         */
        if (!auth()->check()) {
            return ApiResponse::error(
                message: __('auth.failed'),
                status: Response::HTTP_UNAUTHORIZED,
            );
        }

        /**
         * Fetch all support requests from the database for the authenticated user,
         * ordered by ID in descending order.
         */
        $authenticatedUserID = auth()->user()->id;
        $supportRequests = SupportRequest::where('user_id', $authenticatedUserID)
            ->orderBy('id', 'desc')
            ->get();

        // Return a successful response with the list of support requests
        return ApiResponse::success(
            data: compact('supportRequests'),
            message: __(
                'messages.common.retrieved_all', 
                ['items' => __('messages.entities.support_request.plural')]
            )
        );
    }

    /**
     * Store a new support request.
     * 
     * This method handles the creation of a new support request
     * by validating the request data and storing it in the database for the authenticated user.
     * 
     * @param Request $request The request object containing the data 
     * for creating a support request.
     * @return JsonResponse A JSON response indicating the success of the operation.
     * @throws \Exception If there is an error during the creation process.
     */
    public function store(Request $request): JsonResponse
    {
        /**
         * Validate the request data.
         * The date field is required and must be a valid date.
         * The location field is required and must be a string.
         * The detail field is required and must be a string.
         */
        $data = $request->validate([
            'date' => 'required|date',
            'location' => 'required|string',
            'detail' => 'required|string',
        ]);

        /**
         * Ensure the user is authenticated before proceeding.
         * If not authenticated, return an error response.
         */
        if (!auth()->check()) {
            return ApiResponse::error(
                message: __('auth.failed'),
                status: Response::HTTP_UNAUTHORIZED,
            );
        }

        /**
         * Create the support request for the authenticated user.
         * Associate the request with the current authenticated user ID.
         */
        $user = auth()->user();
        $data = array_merge($data, ['user_id' => $user->id]);
        $supportRequest = SupportRequest::create($data);

        /**
         * Send notification to all administrators about the new support request.
         * Get all users with the admin role and send them the notification.
         */
        $administrators = User::role(UserRoles::ADMIN->value)->get();
        
        if ($administrators->isNotEmpty()) {
            Notification::send(
                $administrators, 
                new NewSupportRequestNotification($supportRequest, $user)
            );
        }

        // Return a successful response with the created support request
        return ApiResponse::success(
            message: __(
                'messages.common.created', 
                ['item' => __('messages.entities.support_request.singular')]
            ),
            data: compact('supportRequest'),
            status: Response::HTTP_CREATED,
        );
    }

    /**
     * Display a specific support request.
     * 
     * This method retrieves a specific support request by its model binding
     * and returns its details in a JSON response, ensuring the authenticated user owns it.
     * 
     * @param SupportRequest $supportRequest The support request to be displayed.
     * @return JsonResponse A JSON response containing the details of the support request.
     * @throws \Exception If the support request does not exist or if there is an error retrieving it.
     */
    public function show(SupportRequest $supportRequest): JsonResponse
    {
        /**
         * Ensure the user is authenticated before proceeding.
         * If not authenticated, return an error response.
         */
        if (!auth()->check()) {
            return ApiResponse::error(
                message: __('auth.failed'),
                status: Response::HTTP_UNAUTHORIZED,
            );
        }

        /**
         * Ensure the authenticated user owns the support request.
         * Users can only view their own support requests.
         */
        if ($supportRequest->user_id !== auth()->user()->id) {
            return ApiResponse::error(
                message: __(
                    'messages.common.not_found', 
                    ['item' => __('messages.entities.support_request.singular')]
                ),
                status: Response::HTTP_NOT_FOUND,
            );
        }

        // Return a successful response with the support request details
        return ApiResponse::success(
            message: __(
                'messages.common.retrieved', 
                ['item' => __('messages.entities.support_request.singular')]
            ),
            data: compact('supportRequest'),
        );
    }

    /**
     * Update an existing support request.
     * 
     * This method handles the update of an existing support request by its model binding
     * by validating the request data and updating the record in the database.
     *
     * @param Request $request The request object containing the data
     * for updating the support request.
     * @param SupportRequest $supportRequest The support request to be updated.
     * @return JsonResponse A JSON response indicating the success of the operation.
     * @throws \Exception If there is an error during the update process.
     */
    public function update(Request $request, SupportRequest $supportRequest): JsonResponse
    {
        // Check if the support request exists
        if (!$supportRequest->exists()) {
            // If the support request does not exist, return an error response
            return ApiResponse::error(
                message: __(
                    'messages.common.not_found', 
                    ['item' => __('messages.entities.support_request.singular')]
                ),
                status: Response::HTTP_NOT_FOUND
            );
        }

        /**
         * Validate the request data.
         * The date field is required and must be a valid date.
         * The location field is required and must be a string.
         * The detail field is required and must be a string.
         */
        $data = $request->validate([
            'date' => 'required|date',
            'location' => 'required|string',
            'detail' => 'required|string',
        ]);

        // Update the support request in the database
        $supportRequest->update($data);

        // Return a successful response with the updated support request
        return ApiResponse::success(
            data: compact('supportRequest'),
            message: __(
                'messages.common.updated', 
                ['item' => __('messages.entities.support_request.singular')]
            ),
        );
    }

    /**
     * Remove a specific support request.
     * 
     * This method deletes a specific support request by its model binding from the database,
     * ensuring the authenticated user owns the request.
     *
     * @param SupportRequest $supportRequest The support request to be deleted.
     * @return JsonResponse A JSON response indicating the success of the operation.
     * @throws \Exception If there is an error during the deletion process.
     */
    public function destroy(SupportRequest $supportRequest): JsonResponse
    {
        /**
         * Ensure the user is authenticated before proceeding.
         * If not authenticated, return an error response.
         */
        if (!auth()->check()) {
            return ApiResponse::error(
                message: __('auth.failed'),
                status: Response::HTTP_UNAUTHORIZED,
            );
        }

        /**
         * Ensure the authenticated user owns the support request.
         * Users can only delete their own support requests.
         */
        if ($supportRequest->user_id !== auth()->user()->id) {
            return ApiResponse::error(
                message: __(
                    'messages.common.not_found', 
                    ['item' => __('messages.entities.support_request.singular')]
                ),
                status: Response::HTTP_NOT_FOUND,
            );
        }

        // Delete the support request from the database
        $supportRequest->delete();

        // Return a successful response indicating the support request was deleted
        return ApiResponse::success(
            message: __(
                'messages.common.deleted', 
                ['item' => __('messages.entities.support_request.singular')]
            ),
        );
    }
}