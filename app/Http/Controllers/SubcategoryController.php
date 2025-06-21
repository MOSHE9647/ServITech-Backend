<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubCategoryRequest\CreateSubcategoryRequest;
use App\Http\Requests\SubCategoryRequest\UpdateSubcategoryRequest;
use App\Http\Resources\SubcategoryResource;
use App\Http\Responses\ApiResponse;
use App\Models\Subcategory;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SubcategoryController for managing subcategories.
 * This controller handles CRUD operations for subcategories,
 * including creating, retrieving, updating, and deleting subcategories.
 */
class SubcategoryController extends Controller
{
    /**
     * Display a listing of subcategories.
     * 
     * This method retrieves all subcategories from the database,
     * orders them by ID in descending order, and returns them in a JSON response.
     * @unauthenticated
     * 
     * @return JsonResponse A JSON response containing the list of subcategories.
     * @throws \Exception If there is an error retrieving the subcategories.
     */
    public function index(): JsonResponse
    {
        /**
         * Fetch all subcategories from the database with their related category,
         * ordered by ID in descending order.
         */
        $subcategories = Subcategory::with('category')->orderBy('id', 'desc')->get();

        // Return a successful response with the list of subcategories
        return ApiResponse::success(
            data: ['subcategories' => SubcategoryResource::collection($subcategories)],
            message: __(
                'messages.common.retrieved_all',
                ['items' => __('messages.entities.subcategory.plural')]
            )
        );
    }

    /**
     * Store a new subcategory.
     * 
     * This method handles the creation of a new subcategory
     * by validating the request data and storing it in the database.
     * @unauthenticated
     * 
     * @param CreateSubcategoryRequest $request The request object containing the data 
     * for creating a subcategory.
     * @return JsonResponse A JSON response indicating the success of the operation.
     * @throws \Throwable If there is an error during the creation process,
     * such as database transaction failure.
     */
    public function store(CreateSubcategoryRequest $request): JsonResponse
    {
        // Validate the request data
        $data = $request->validated();

        try {
            // Begin a database transaction to ensure atomicity
            DB::beginTransaction();

            // Create a new subcategory in the database
            $subcategory = Subcategory::create($data);

            // Commit the transaction if all operations are successful
            DB::commit();

            // Return a successful response with the created subcategory
            return ApiResponse::success(
                status: Response::HTTP_CREATED,
                data: ['subcategory' => new SubcategoryResource($subcategory)],
                message: __(
                    'messages.common.created',
                    ['item' => __('messages.entities.subcategory.singular')]
                )
            );
        } catch (\Throwable $th) {
            // Rollback the transaction if any operation fails
            DB::rollBack();

            // Return an error response with the exception message
            return ApiResponse::error(
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                message: __(
                    'messages.common.creation_failed',
                    ['item' => __('messages.entities.subcategory.singular')]
                ),
                errors: ['exception' => $th->getMessage()]
            );
        }
    }

    /**
     * Display a specific subcategory.
     * 
     * This method retrieves a specific subcategory by its model binding
     * and returns its details in a JSON response.
     * @unauthenticated
     * 
     * @param Subcategory $subcategory The subcategory to be displayed.
     * @return JsonResponse A JSON response containing the details of the subcategory.
     * @throws \Exception If the subcategory does not exist or if there is an error retrieving it.
     */
    public function show(Subcategory $subcategory): JsonResponse
    {
        // Check if the subcategory exists
        if (!$subcategory->exists()) {
            // If the subcategory does not exist, return an error response
            return ApiResponse::error(
                message: __(
                    'messages.common.not_found',
                    ['item' => __('messages.entities.subcategory.singular')]
                ),
                status: Response::HTTP_NOT_FOUND
            );
        }

        // Load the category relationship
        $subcategory->load('category');

        // Return a successful response with the subcategory details
        return ApiResponse::success(
            data: ['subcategory' => new SubcategoryResource($subcategory)],
            message: __(
                'messages.common.retrieved',
                ['item' => __('messages.entities.subcategory.singular')]
            )
        );
    }

    /**
     * Update an existing subcategory.
     * 
     * This method handles the update of an existing subcategory by its model binding
     * by validating the request data and updating the record in the database.
     * @unauthenticated
     *
     * @param UpdateSubcategoryRequest $request The request object containing the data
     * for updating the subcategory.
     * @param Subcategory $subcategory The subcategory to be updated.
     * @return JsonResponse A JSON response indicating the success of the operation.
     * @throws \Throwable If there is an error during the update process,
     * such as database transaction failure or validation issues.
     */
    public function update(UpdateSubcategoryRequest $request, Subcategory $subcategory): JsonResponse
    {
        // Check if the subcategory exists
        if (!$subcategory->exists()) {
            // If the subcategory does not exist, return an error response
            return ApiResponse::error(
                message: __(
                    'messages.common.not_found',
                    ['item' => __('messages.entities.subcategory.singular')]
                ),
                status: Response::HTTP_NOT_FOUND
            );
        }

        // Validate the request data
        $data = $request->validated();

        try {
            // Begin a database transaction to ensure atomicity
            DB::beginTransaction();

            // Update the subcategory in the database
            $subcategory->update($data);

            // Commit the transaction if all operations are successful
            DB::commit();

            // Return a successful response with the updated subcategory
            return ApiResponse::success(
                data: ['subcategory' => new SubcategoryResource($subcategory->fresh('category'))],
                message: __(
                    'messages.common.updated',
                    ['item' => __('messages.entities.subcategory.singular')]
                )
            );
        } catch (\Throwable $th) {
            // Rollback the transaction if any operation fails
            DB::rollBack();

            // Return an error response with the exception message
            return ApiResponse::error(
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                message: __(
                    'messages.common.update_failed',
                    ['item' => __('messages.entities.subcategory.singular')]
                ),
                errors: ['exception' => $th->getMessage()]
            );
        }
    }

    /**
     * Remove a specific subcategory.
     * 
     * This method deletes a specific subcategory by its model binding from the database.
     * @unauthenticated
     *
     * @param Subcategory $subcategory The subcategory to be deleted.
     * @return JsonResponse A JSON response indicating the success of the operation.
     * @throws \Exception If there is an error during the deletion process.
     */
    public function destroy(Subcategory $subcategory): JsonResponse
    {
        // Check if the subcategory exists
        if (!$subcategory->exists()) {
            // If the subcategory does not exist, return an error response
            return ApiResponse::error(
                message: __(
                    'messages.common.not_found',
                    ['item' => __('messages.entities.subcategory.singular')]
                ),
                status: Response::HTTP_NOT_FOUND
            );
        }

        try {
            // Delete the subcategory from the database
            $subcategory->delete();

            // Return a successful response indicating the subcategory was deleted
            return ApiResponse::success(
                message: __(
                    'messages.common.deleted',
                    ['item' => __('messages.entities.subcategory.singular')]
                )
            );
        } catch (\Throwable $th) {
            // Return an error response with the exception message
            return ApiResponse::error(
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                message: __(
                    'messages.common.deletion_failed',
                    ['item' => __('messages.entities.subcategory.singular')]
                ),
                errors: ['exception' => $th->getMessage()]
            );
        }
    }
}
