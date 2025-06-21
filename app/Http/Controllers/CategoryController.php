<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CategoryController for managing categories.
 * This controller handles CRUD operations for categories,
 * including creating, retrieving, updating, and deleting categories.
 */
class CategoryController extends Controller
{
    /**
     * Display a listing of categories.
     * 
     * This method retrieves all categories from the database,
     * orders them by ID in descending order, and returns them in a JSON response.
     * 
     * @return JsonResponse A JSON response containing the list of categories.
     * @throws \Exception If there is an error retrieving the categories.
     */
    public function index(): JsonResponse
    {
        /**
         * Fetch all categories from the database,
         * ordered by ID in descending order.
         */
        $categories = Category::orderBy('id', 'desc')->get();

        // Return a successful response with the list of categories
        return ApiResponse::success(
            data: compact('categories'),
            message: __(
                'messages.common.retrieved_all',
                ['items' => __('messages.entities.category.plural')]
            )
        );
    }

    /**
     * Store a new category.
     * 
     * This method handles the creation of a new category
     * by validating the request data and storing it in the database.
     * 
     * @param Request $request The request object containing the data 
     * for creating a category.
     * @return JsonResponse A JSON response indicating the success of the operation.
     * @throws \Exception If there is an error during the creation process.
     */
    public function store(Request $request): JsonResponse
    {
        /**
         * Validate the request data.
         * The name field is required and must be a string with a maximum length of 255 characters.
         * The description field is optional and must be a string with a maximum length of 255 characters.
         */
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
        ]);

        // Create a new category in the database
        $category = Category::create($data);

        // Return a successful response with the created category
        return ApiResponse::success(
            data: compact('category'),
            message: __(
                'messages.common.created',
                ['item' => __('messages.entities.category.singular')]
            ),
            status: Response::HTTP_CREATED
        );
    }

    /**
     * Display a specific category.
     * 
     * This method retrieves a specific category by its model binding
     * and returns its details in a JSON response.
     * 
     * @param Category $category The category to be displayed.
     * @return JsonResponse A JSON response containing the details of the category.
     * @throws \Exception If the category does not exist or if there is an error retrieving it.
     */
    public function show(Category $category): JsonResponse
    {
        // Check if the category exists
        if (!$category->exists()) {
            // If the category does not exist, return an error response
            return ApiResponse::error(
                message: __(
                    'messages.common.not_found',
                    ['item' => __('messages.entities.category.singular')]
                ),
                status: Response::HTTP_NOT_FOUND
            );
        }

        // Return a successful response with the category details
        return ApiResponse::success(
            data: compact('category'),
            message: __(
                'messages.common.retrieved',
                ['item' => __('messages.entities.category.singular')]
            )
        );
    }

    /**
     * Update an existing category.
     * 
     * This method handles the update of an existing category by its model binding
     * by validating the request data and updating the record in the database.
     *
     * @param Request $request The request object containing the data
     * for updating the category.
     * @param Category $category The category to be updated.
     * @return JsonResponse A JSON response indicating the success of the operation.
     * @throws \Exception If there is an error during the update process.
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        /**
         * Validate the request data.
         * The name field is required and must be a string with a maximum length of 255 characters.
         * The description field is optional and must be a string with a maximum length of 255 characters.
         */
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
        ]);

        // Update the category in the database
        $category->update($data);

        // Return a successful response with the updated category
        return ApiResponse::success(
            data: compact('category'),
            message: __(
                'messages.common.updated',
                ['item' => __('messages.entities.category.singular')]
            )
        );
    }

    /**
     * Remove a specific category.
     * 
     * This method deletes a specific category by its model binding from the database.
     *
     * @param Category $category The category to be deleted.
     * @return JsonResponse A JSON response indicating the success of the operation.
     * @throws \Exception If there is an error during the deletion process.
     */
    public function destroy(Category $category): JsonResponse
    {
        // Check if the category exists
        if (!$category->exists()) {
            // If the category does not exist, return an error response
            return ApiResponse::error(
                message: __(
                    'messages.common.not_found',
                    ['item' => __('messages.entities.category.singular')]
                ),
                status: Response::HTTP_NOT_FOUND
            );
        }

        // Delete the category from the database
        $category->delete();

        // Return a successful response indicating the category was deleted
        return ApiResponse::success(message: __(
            'messages.common.deleted',
            ['item' => __('messages.entities.category.singular')]
        ));
    }
}