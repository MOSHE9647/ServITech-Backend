<?php

namespace App\Http\Controllers\Model;

use App\Http\Controllers\Controller;
use App\Http\Requests\RepairRequest\CreateRepairRequest;
use App\Http\Requests\RepairRequest\UpdateRepairRequest;
use App\Http\Resources\RepairRequestResource;
use App\Http\Responses\ApiResponse;
use App\Models\RepairRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use DB;

/**
 * Class RepairRequestController for managing repair requests.
 * This controller handles CRUD operations for repair requests,
 * including creating, retrieving, updating, and deleting repair requests.
 */
class RepairRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        // Fetch all repair requests from the database
        // and order them by ID in descending order
        $repairRequests = RepairRequest::orderBy("id", "desc")->get();

        // Return a successful response with the list of repair requests
        return ApiResponse::success(
            message: __('messages.repair_request.retrieved_list'),
            data: compact("repairRequests")
        );
    }

    /**
     * Store a newly created resource in storage.
     * @requestMediaType multipart/form-data
     * 
     * @param CreateRepairRequest $request The request object containing the data 
     * for creating a repair request.
     * @return ApiResponse A JSON response indicating the success of the operation.
     */
    public function store(CreateRepairRequest $request): JsonResponse
    {
        // Validate the request data
        $data = $request->validated();

        // Create a new repair request in the database
        $repairRequest = RepairRequest::create($data);

        // Check if images are provided in the request
        if ($request->hasFile('images')) {
            try {
                // Begin a database transaction to ensure atomicity
                DB::beginTransaction();

                // Store each image and create a record in the database
                $images = [];
                foreach ($request->file('images') as $image) {
                    $path = Storage::put('repair_requests', $image);
                    $images[] = ['path' => Storage::url($path)];
                }
                $repairRequest->images()->createMany($images);

                // Commit the transaction if all operations are successful
                DB::commit();
            } catch (\Throwable $th) {
                // Rollback the transaction if any operation fails
                DB::rollBack();

                // Return an error response with the exception message
                return ApiResponse::error(
                    status: Response::HTTP_INTERNAL_SERVER_ERROR,
                    message: __('messages.repair_request.creation_failed'),
                    errors: ['exception' => $th->getMessage()]
                );
            }
        }

        // Return a successful response with the created repair request
        return ApiResponse::success(
            status: Response::HTTP_CREATED,
            message: __('messages.repair_request.created'),
            data: [
                'repairRequest' => RepairRequestResource::make(
                    $repairRequest->load('images')
                )
            ]
        );
    }

    /**
     * Display the specified resource.
     * 
     * @param RepairRequest $repairRequest The repair request to be displayed.
     * @return ApiResponse A JSON response containing the details of the repair request.
     */
    public function show(RepairRequest $repairRequest): JsonResponse
    {
        // Check if the repair request exists
        if (!$repairRequest->exists()) {
            return ApiResponse::error(
                message: __('messages.not_found', ['attribute' => RepairRequest::class]),
                status: Response::HTTP_BAD_REQUEST
            );
        }

        // Load the images associated with the repair request
        $repairRequest->load('images');
        
        // Return a successful response with the repair request details
        return ApiResponse::success(
            data: ['repairRequest' => RepairRequestResource::make($repairRequest)],
            message: __('messages.repair_request.retrieved')
        );
    }

    /**
     * Update the specified resource in storage.
     * @requestMediaType multipart/form-data
     *
     * @param RepairRequest $repairRequest The repair request to be updated.
     * @param UpdateRepairRequest $request The request object containing the data
     * for updating the repair request.
     * @return ApiResponse A JSON response indicating the success of the operation.
     */
    public function update(UpdateRepairRequest $request, RepairRequest $repairRequest): JsonResponse
    {
        // Check if the repair request exists
        // If not, return an error response
        if (! $repairRequest->exists()) {
            return ApiResponse::error(
                message: __('messages.not_found', ['attribute' => RepairRequest::class]),
                status: Response::HTTP_BAD_REQUEST
            );
        }

        // Validate the request data
        $data = $request->validated();

        // Update the repair request in the database
        $repairRequest->update($data);

        // Check if images are provided in the request
        if ($request->hasFile('images')) {
            try {
                // Begin a database transaction to ensure atomicity
                DB::beginTransaction();

                // Delete existing images associated with the repair request
                $repairRequest->images()->each(function ($image) {
                    Storage::delete(str_replace('/storage/', '', $image->path));
                    $image->delete();
                });

                // Store each new image and create a record in the database
                $images = [];
                foreach ($request->file('images') as $image) {
                    $path = Storage::put('repair_requests', $image);
                    $images[] = ['path' => Storage::url($path)];
                }
                $repairRequest->images()->createMany($images);

                // Commit the transaction if all operations are successful
                DB::commit();
            } catch (\Throwable $th) {
                // Rollback the transaction if any operation fails
                DB::rollBack();

                // Return an error response with the exception message
                return ApiResponse::error(
                    status: Response::HTTP_INTERNAL_SERVER_ERROR,
                    message: __('messages.repair_request.update_failed'),
                    errors: ['exception' => $th->getMessage()]
                );
            }
        }

        // Return a successful response with the updated repair request
        return ApiResponse::success(
            message: __('messages.repair_request.updated'),
            data: ['repairRequest' => RepairRequestResource::make($repairRequest->load('images'))],
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param RepairRequest $repairRequest The repair request to be deleted.
     * @return ApiResponse A JSON response indicating the success of the operation.
     */
    public function destroy(RepairRequest $repairRequest): JsonResponse
    {
        // Check if the repair request exists
        // If not, return an error response
        if (!$repairRequest->exists()) {
            return ApiResponse::error(
                message: __('messages.not_found', ['attribute' => RepairRequest::class]),
                status: Response::HTTP_BAD_REQUEST
            );
        }
        
        // Begin a database transaction to ensure atomicity
        DB::beginTransaction();
        try {
            // Check if the repair request has associated images
            // If so, delete each image from storage and remove the record from the database
            if ($repairRequest->images()->exists()){
                $repairRequest->images()->each(function ($image) {
                    // Delete the image file from storage
                    Storage::delete(str_replace('/storage/', '', $image->path));
                    // Delete the image record from the database
                    $image->delete();
                });
            }

            // Attempt to delete the repair request
            // If deletion fails, return an error response
            if (!$repairRequest->delete()) {
                throw new \Exception(__('messages.repair_request.not_deleted'));
            }

            // Commit the transaction if all operations are successful
            DB::commit();
            
            // Delete the repair request from the database
            return ApiResponse::success(message: __('messages.repair_request.deleted'));
        } catch (\Throwable $th) {
            // Rollback the transaction if any operation fails
            DB::rollBack();

            // Return an error response with the exception message
            return ApiResponse::error(
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                message: __('messages.repair_request.not_deleted'),
                errors: ['exception' => $th->getMessage()]
            );
        }
    }
}