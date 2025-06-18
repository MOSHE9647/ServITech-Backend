<?php

namespace App\Http\Controllers;

use App\Http\Requests\RepairRequest\CreateRepairRequest;
use App\Http\Requests\RepairRequest\UpdateRepairRequest;
use App\Http\Resources\RepairRequestResource;
use App\Http\Responses\ApiResponse;
use App\Models\RepairRequest;
use App\Traits\HandleImageUploads;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use DB;

/**
 * Class RepairRequestController for managing repair requests.
 * This controller handles CRUD operations for repair requests,
 * including creating, retrieving, updating, and deleting repair requests.
 */
class RepairRequestController extends Controller
{
    // Use the HandleImageUploads trait to manage image uploads
    use HandleImageUploads;
    
    /**
     * Display a listing of the resource.
     * 
     * This method retrieves all repair requests from the database,
     * orders them by ID in descending order, and returns them in a JSON response.
     * 
     * @return ApiResponse A JSON response containing the list of repair requests.
     * @throws \Exception If there is an error retrieving the repair requests.
     */
    public function index(): JsonResponse
    {
        /**
         * Fetch all repair requests from the database,
         * ordered by ID in descending order.
         */
        $repairRequests = RepairRequest::orderBy("id", "desc")->get();

        // Return a successful response with the list of repair requests
        return ApiResponse::success(
            message: __(
                'messages.common.retrieved_all', 
                ['items' => __('messages.entities.repair_request.plural')]
            ),
            data: [
                'repairRequests' => RepairRequestResource::collection(
                    $repairRequests->load('images')
                )
            ]
        );
    }

    /**
     * Store a new repair request.
     * 
     * This method handles the creation of a new repair request
     * by validating the request data and storing it in the database.
     * @requestMediaType multipart/form-data
     * 
     * @param CreateRepairRequest $request The request object containing the data 
     * for creating a repair request.
     * @return ApiResponse A JSON response indicating the success of the operation.
     * @throws \Throwable If there is an error during the creation process,
     * such as database transaction failure or file storage issues.
     */
    public function store(CreateRepairRequest $request): JsonResponse
    {
        // Validate the request data
        $data = $request->validated();

        try {
            // Begin a database transaction to ensure atomicity
            DB::beginTransaction();

            // Create a new repair request in the database
            $repairRequest = RepairRequest::create($data);

            // Check if images are provided in the request
            if ($request->hasFile('images')) {
                // Store each image and create a record in the database
                $images = $this->storeImages(
                    images: $request->file('images'), 
                    relatedId: $repairRequest->receipt_number,
                    prefix: 'repair_request_image',
                    directory: 'repair_requests'
                );
                $repairRequest->images()->createMany($images);
            }

            // Commit the transaction if all operations are successful
            DB::commit();

            // Return a successful response with the created repair request
            return ApiResponse::success(
                status: Response::HTTP_CREATED,
                message: __(
                    'messages.common.created', 
                    ['item' => __('messages.entities.repair_request.singular')]
                ),
                data: [
                    'repairRequest' => RepairRequestResource::make(
                        $repairRequest->load('images')
                    )
                ]
            );
        } catch (\Throwable $th) {
            // Rollback the transaction if any operation fails
            DB::rollBack();

            // Return an error response with the exception message
            return ApiResponse::error(
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                message: __(
                    'messages.common.creation_failed', 
                    ['item' => __('messages.entities.repair_request.singular')]
                ),
                errors: ['exception' => $th->getMessage()]
            );
        }
    }

    /**
     * Display a specific repair request.
     * 
     * This method retrieves a specific repair request by its Receipt Number
     * and returns its details in a JSON response.
     * 
     * @param RepairRequest $repairRequest The receipt number of the repair request to be displayed.
     * @return ApiResponse A JSON response containing the details of the repair request.
     * @throws \Exception If the repair request does not exist or if there is an error retrieving it.
     */
    public function show(RepairRequest $repairRequest): JsonResponse
    {
        // Load the images associated with the repair request
        $repairRequest->load('images');
        
        // Return a successful response with the repair request details
        return ApiResponse::success(
            data: ['repairRequest' => RepairRequestResource::make($repairRequest)],
            message: __(
                'messages.common.retrieved', 
                ['item' => __('messages.entities.repair_request.singular')]
            )
        );
    }

    /**
     * Update an existing repair request.
     * 
     * This method handles the update of an existing repair request by its Receipt Number
     * by validating the request data and updating the record in the database.
     *
     * @param RepairRequest $repairRequest The receipt number of the repair request to be updated.
     * @param UpdateRepairRequest $request The request object containing the data
     * for updating the repair request.
     * @return ApiResponse A JSON response indicating the success of the operation.
     * @throws \Throwable If there is an error during the update process,
     * such as database transaction failure or validation issues.
     */
    public function update(UpdateRepairRequest $request, RepairRequest $repairRequest): JsonResponse
    {
        // Validate the request data
        $data = $request->validated();

        // Update the repair request in the database
        $repairRequest->update($data);

        // Return a successful response with the updated repair request
        return ApiResponse::success(
            message: __(
                'messages.common.updated', 
                ['item' => __('messages.entities.repair_request.singular')]
            ),
            data: [
                'repairRequest' => RepairRequestResource::make(
                    $repairRequest->load('images')
                )
            ],
        );
    }

    /**
     * Remove a specific repair request.
     * 
     * This method deletes a specific repair request by its Receipt Number from the database,
     * including its associated images, if they exist, using a soft delete approach.
     *
     * @param RepairRequest $repairRequest The receipt number of the repair request to be deleted.
     * @return ApiResponse A JSON response indicating the success of the operation.
     * @throws \Throwable If there is an error during the deletion process,
     * such as database transaction failure or file storage issues.
     */
    public function destroy(RepairRequest $repairRequest): JsonResponse
    {
        try {
            // Begin a database transaction to ensure atomicity
            DB::beginTransaction();
        
            // Check if the repair request has associated images
            // If so, delete each image from storage and remove the record from the database
            if ($repairRequest->images()->exists()){
                $this->deleteImages($repairRequest->images);
            }

            // Attempt to delete the repair request
            // If deletion fails, return an error response
            if (!$repairRequest->delete()) {
                throw new \Exception(__(
                    'messages.common.deletion_failed', 
                    ['item' => __('messages.entities.repair_request.singular')]
                ));
            }

            // Commit the transaction if all operations are successful
            DB::commit();
            
            // Return a successful response indicating the repair request was deleted
            return ApiResponse::success(message: __(
                'messages.common.deleted', 
                ['item' => __('messages.entities.repair_request.singular')]
            ));
        } catch (\Throwable $th) {
            // Rollback the transaction if any operation fails
            DB::rollBack();

            // Return an error response with the exception message
            return ApiResponse::error(
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                message: __(
                    'messages.common.deletion_failed',
                    ['item' => __('messages.entities.repair_request.singular')]
                ),
                errors: ['exception' => $th->getMessage()]
            );
        }
    }
}
