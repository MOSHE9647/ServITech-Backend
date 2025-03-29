<?php

namespace App\Http\Controllers\Model;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 
 * Class CategoryController
 * 
 * @OA\Schema(
 *     schema="Category",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="description", type="string"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time")
 * )
 */
class CategoryController extends Controller
{
    /**
     * 
     * Display a listing of the resource.
     * 
     * @OA\Get(
     *     path="/categories",
     *     summary="List all categories",
     *     tags={"Categories"},
     *     @OA\Response(
     *         response=200,
     *         description="List of categories obtained successfully.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="List of categories obtained successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="categories",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Category")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error retrieving categories",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Error retrieving categories."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="error_name",
     *                     type="object",
     *                     @OA\Property(property="error1", type="string"),
     *                     @OA\Property(property="error2", type="string")
     *                 )
     *             )
     *         )
     *     )
     * )
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        $categories = Category::orderBy('id', 'desc')->get();
        return ApiResponse::success(
            data: compact('categories'),
            message: __('messages.category.retrieved_all')
        );
    }

    /**
     * 
     * Store a newly created resource in storage.
     * 
     * @OA\Post(
     *     path="/categories",
     *     summary="Create a new category",
     *     tags={"Categories"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", maxLength=255)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Category created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=201),
     *             @OA\Property(property="message", type="string", example="Category created successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="category",
     *                     ref="#/components/schemas/Category"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error creating category",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Error creating category."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="error_name",
     *                     type="object",
     *                     @OA\Property(property="error1", type="string"),
     *                     @OA\Property(property="error2", type="string")
     *                 )
     *             )
     *         )
     *     )
     * )
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255', // Needs to verify if the category name is unique
        ]);
        $category = Category::create($data);

        return ApiResponse::success(
            data: compact('category'),
            message: __('messages.category.created')
        );
    }

    /**
     * 
     * Display the specified resource.
     * 
     * @OA\Get(
     *     path="/categories/{name}",
     *     summary="Show a specific category",
     *     tags={"Categories"},
     *     @OA\Parameter(
     *         name="name",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category retrieved successfully.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Category retrieved successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="category",
     *                     ref="#/components/schemas/Category"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error retrieving category",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Error retrieving category."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="error_name",
     *                     type="object",
     *                     @OA\Property(property="error1", type="string"),
     *                     @OA\Property(property="error2", type="string")
     *                 )
     *             )
     *         )
     *     )
     * )
     * 
     * @param \App\Models\Category $category
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Category $category): JsonResponse
    {
        return ApiResponse::success(
            data: compact('category'),
            message: __('messages.category.retrieved')
        );
    }

    /**
     * 
     * Update the specified resource in storage.
     * 
     * @OA\Put(
     *     path="/categories/{name}",
     *     summary="Update an existing category",
     *     tags={"Categories"},
     *     @OA\Parameter(
     *         name="name",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", maxLength=255)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category updated successfully.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Category updated successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="category",
     *                     ref="#/components/schemas/Category"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error updating category",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Error updating category."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="error_name",
     *                     type="object",
     *                     @OA\Property(property="error1", type="string"),
     *                     @OA\Property(property="error2", type="string")
     *                 )
     *             )
     *         )
     *     )
     * )
     * 
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Category $category
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $category->update($data);

        return ApiResponse::success(
            data: compact('category'),
            message: __('messages.category.updated')
        );
    }

    /**
     * 
     * Remove the specified resource from storage.
     * 
     * @OA\Delete(
     *     path="/categories/{name}",
     *     summary="Delete a category",
     *     tags={"Categories"},
     *     @OA\Parameter(
     *         name="name",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category deleted successfully.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Category deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error deleting category",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Error deleting category."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="error_name",
     *                     type="object",
     *                     @OA\Property(property="error1", type="string"),
     *                     @OA\Property(property="error2", type="string")
     *                 )
     *             )
     *         )
     *     )
     * )
     * 
     * @param \App\Models\Category $category
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Category $category): JsonResponse
    {
        $category->delete();
        return ApiResponse::success(message: __('messages.category.deleted'));
    }
}