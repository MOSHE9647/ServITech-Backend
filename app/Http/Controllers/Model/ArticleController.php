<?php

namespace App\Http\Controllers\Model;

use App\Http\Controllers\Controller;
use App\Http\Requests\ArticleRequest\CreateArticleRequest;
use App\Http\Requests\ArticleRequest\UpdateArticleRequest;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\ImageResource;
use App\Http\Responses\ApiResponse;
use App\Models\Article;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ArticleController for managing articles.
 *
 * @OA\SecurityScheme(
 *     securityScheme="BearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 *
 * @OA\Schema(
 *     schema="CreateArticleRequest",
 *     type="object",
 *     required={"name", "description", "price", "category_id", "subcategory_id"},
 *     @OA\Property(property="name", type="string", example="Laptop"),
 *     @OA\Property(property="description", type="string", example="A high-performance laptop with 16GB RAM and 512GB SSD."),
 *     @OA\Property(property="price", type="number", format="float", example=1200.50),
 *     @OA\Property(property="category_id", type="integer", example=1),
 *     @OA\Property(property="subcategory_id", type="integer", example=2),
 *     @OA\Property(
 *         property="images",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="path", type="string", example="/images/articles/laptop.jpg")
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="UpdateArticleRequest",
 *     type="object",
 *     required={"name", "description", "price", "category_id", "subcategory_id"},
 *     @OA\Property(property="name", type="string", example="Updated Laptop"),
 *     @OA\Property(property="description", type="string", example="An updated high-performance laptop with 32GB RAM and 1TB SSD."),
 *     @OA\Property(property="price", type="number", format="float", example=1500.75),
 *     @OA\Property(property="category_id", type="integer", example=1),
 *     @OA\Property(property="subcategory_id", type="integer", example=2),
 *     @OA\Property(
 *         property="images",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="path", type="string", example="/images/articles/updated-laptop.jpg")
 *         )
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="Article",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Laptop"),
 *     @OA\Property(property="description", type="string", example="A high-performance laptop with 16GB RAM and 512GB SSD."),
 *     @OA\Property(property="price", type="number", format="float", example=1200.50),
 *     @OA\Property(property="category_id", type="integer", example=1),
 *     @OA\Property(property="subcategory_id", type="integer", example=2),
 *     @OA\Property(
 *         property="images",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="path", type="string", example="/images/articles/laptop.jpg"),
 *         )
 *     ),
 *     @OA\Property(
 *         property="category",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="Electronics"),
 *         @OA\Property(property="description", type="string", example="All electronic items"),
 *     ),
 *     @OA\Property(
 *         property="subcategory",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=2),
 *         @OA\Property(property="name", type="string", example="Laptops"),
 *         @OA\Property(property="description", type="string", example="All types of laptops"),
 *     )
 * )
 */
class ArticleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/{version}/articles",
     *     summary="Get all articles",
     *     tags={"Articles"},
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
     *         description="List of articles retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Articles retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="articles", type="array",
     *                     @OA\Items(ref="#/components/schemas/Article")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $articles = Article::orderBy('id', 'desc')->with(['category', 'subcategory', 'images'])->get();

        return ApiResponse::success(
            data: ['articles' => $articles],
            message: __('messages.article.retrieved_all')
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *     path="/api/{version}/articles",
     *     summary="Create a new article",
     *     tags={"Articles"},
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
     *         @OA\JsonContent(ref="#/components/schemas/CreateArticleRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Article created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=201),
     *             @OA\Property(property="message", type="string", example="Article created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="article", ref="#/components/schemas/Article")
     *             )
     *         )
     *     )
     * )
     */
    public function store(CreateArticleRequest $request): JsonResponse
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {
            $article = Article::create($data);

            if ($request->hasFile('images')) {
                $images = array_map(function ($image) {
                    $path = Storage::put('articles', $image);
                    return ['path' => Storage::url($path)];
                }, $request->file('images'));

                $article->images()->createMany($images);
            }

            DB::commit();

            return ApiResponse::success(
                status: Response::HTTP_CREATED,
                data: ['article' => ArticleResource::make($article->load('images'))],
                message: __('messages.article.created')
            );
        } catch (Exception $e) {
            DB::rollBack();

            return ApiResponse::error(
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                message: __('messages.article.creation_failed'),
                errors: ['exception' => $e->getMessage()]
            );
        }
    }

    /**
     * Display the specified resource.
     *
     * @OA\Get(
     *     path="/api/{version}/articles/{id}",
     *     summary="Get a specific article",
     *     tags={"Articles"},
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
     *         description="ID of the article",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Article retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Article retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="article", ref="#/components/schemas/Article")
     *             )
     *         )
     *     )
     * )
     */
    public function show(Article $article): JsonResponse
    {
        return ApiResponse::success(
            data: $article->load(['images'])->toArray(),
            message: __('messages.article.retrieved')
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Put(
     *     path="/api/{version}/articles/{id}",
     *     summary="Update an article",
     *     tags={"Articles"},
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
     *         description="ID of the article",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UpdateArticleRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Article updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Article updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="article", ref="#/components/schemas/Article")
     *             )
     *         )
     *     )
     * )
     */
    public function update(UpdateArticleRequest $request, Article $article): JsonResponse
    {
        // Validate the request
        $data = $request->validated();
        $article->update($data);

        // Check if new images are provided
        if ($request->hasFile('images')) {
            // Delete current images
            $article->images->each(function ($image) {
                Storage::delete(str_replace('/storage/', '', $image->path));
                $image->delete();
            });

            $images = array_map(function ($image) {
                $path = Storage::put('articles', $image);
                return ['path' => Storage::url($path)];
            }, $request->file('images'));

            $article->images()->createMany($images);
        }

        return ApiResponse::success(
            message: __('messages.article.updated'),
            data: ['article' => ArticleResource::make($article->load('images'))],
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @OA\Delete(
     *     path="/api/{version}/articles/{id}",
     *     summary="Delete an article",
     *     tags={"Articles"},
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
     *         description="ID of the article",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Article deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Article deleted successfully")
     *         )
     *     )
     * )
     */
    public function destroy(Article $article): JsonResponse
    {
        // Delete all related images
        $article->images->each(function ($image) {
            Storage::delete(str_replace('/storage/', '', $image->path));
            $image->delete();
        });

        // Delete the article
        $article->delete();

        return ApiResponse::success(
            message: __('messages.article.deleted')
        );
    }
}