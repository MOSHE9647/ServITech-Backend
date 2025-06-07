<?php

namespace App\Http\Controllers\Model;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubCategoryRequest\CreateSubcategoryRequest;
use App\Http\Requests\SubCategoryRequest\UpdateSubcategoryRequest;
use App\Http\Resources\SubcategoryResource;
use App\Http\Responses\ApiResponse;
use App\Models\Subcategory;
use Exception;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * @OA\Schema(
 *     schema="Subcategory",
 *     type="object",
 *     title="Subcategoría",
 *     required={"name", "category_id"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Laptops"),
 *     @OA\Property(property="description", type="string", example="Subcategoría de laptops"),
 *     @OA\Property(property="category_id", type="integer", example=2),
 *     @OA\Property(
 *         property="category",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=2),
 *         @OA\Property(property="name", type="string", example="Electrónica"),
 *         @OA\Property(property="description", type="string", example="Artículos tecnológicos")
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-06-05T18:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-06-05T18:30:00Z")
 * )
 */
class SubcategoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/subcategories",
     *     summary="Obtener todas las subcategorías",
     *     tags={"Subcategories"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Subcategorías obtenidas correctamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Subcategorías obtenidas correctamente."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="subcategories",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Subcategory")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $subcategories = Subcategory::with('category')->orderBy('id', 'desc')->get();

        return ApiResponse::success(
            data: ['subcategories' => SubcategoryResource::collection($subcategories)],
            message: __('messages.subcategory.retrieved_all')
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/subcategories",
     *     summary="Crear una nueva subcategoría",
     *     tags={"Subcategories"},
     *     security={{"BearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "category_id"},
     *             @OA\Property(property="name", type="string", example="Tablets"),
     *             @OA\Property(property="description", type="string", example="Subcategoría de tablets"),
     *             @OA\Property(property="category_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Subcategoría creada correctamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=201),
     *             @OA\Property(property="message", type="string", example="Subcategoría creada correctamente."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="subcategory", ref="#/components/schemas/Subcategory")
     *             )
     *         )
     *     )
     * )
     */
    public function store(CreateSubcategoryRequest $request)
    {
        DB::beginTransaction();
        try {
            $subcategory = Subcategory::create($request->validated());
            DB::commit();

            return ApiResponse::success(
                status: Response::HTTP_CREATED,
                data: ['subcategory' => new SubcategoryResource($subcategory)],
                message: __('messages.subcategory.created')
            );
        } catch (Exception $e) {
            DB::rollBack();

            return ApiResponse::error(
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                message: __('messages.subcategory.creation_failed'),
                errors: ['exception' => $e->getMessage()]
            );
        }
    }

    public function show(Subcategory $subcategory)
    {
        $subcategory->load('category');

        return ApiResponse::success(
            data: ['subcategory' => new SubcategoryResource($subcategory)],
            message: __('messages.subcategory.retrieved')
        );
    }

    public function update(UpdateSubcategoryRequest $request, Subcategory $subcategory)
    {
        DB::beginTransaction();
        try {
            $subcategory->update($request->validated());
            DB::commit();

            return ApiResponse::success(
                data: ['subcategory' => new SubcategoryResource($subcategory->fresh('category'))],
                message: __('messages.subcategory.updated')
            );
        } catch (Exception $e) {
            DB::rollBack();

            return ApiResponse::error(
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                message: __('messages.subcategory.update_failed'),
                errors: ['exception' => $e->getMessage()]
            );
        }
    }

    public function destroy(Subcategory $subcategory)
    {
        try {
            $subcategory->delete();

            return ApiResponse::success(
                message: __('messages.subcategory.deleted')
            );
        } catch (Exception $e) {
            return ApiResponse::error(
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                message: __('messages.subcategory.deletion_failed'),
                errors: ['exception' => $e->getMessage()]
            );
        }
    }
}
