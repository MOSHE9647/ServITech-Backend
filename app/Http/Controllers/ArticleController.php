<?php

namespace App\Http\Controllers;

use App\Enums\UserRoles;
use App\Http\Requests\ArticleRequest\CreateArticleRequest;
use App\Http\Requests\ArticleRequest\UpdateArticleRequest;
use App\Http\Resources\ArticleResource;
use App\Http\Responses\ApiResponse;
use App\Models\Article;
use App\Traits\HandleImageUploads;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ArticleController for managing articles.
 * This controller handles CRUD operations for articles,
 * including creating, retrieving, updating, and deleting articles.
 */
class ArticleController extends Controller implements HasMiddleware
{
    // Use the HandleImageUploads trait to manage image uploads
    use HandleImageUploads;

    /**
     * Define the middleware for the controller.
     * This method specifies which middleware should be applied to the controller's actions.
     * The 'auth:api' middleware is applied to the 'update', 'destroy', and 'store' actions,
     * ensuring that only authenticated users can access these actions.
     * @return Middleware[]
     */
    public static function middleware(): array
    {
        return [
            new Middleware(
                middleware: ['auth:api', "role:" . UserRoles::ADMIN->value],
                only: ['update', 'destroy', 'store']
            ),
        ];
    }

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
        $articles = Article::orderBy('id', 'desc')->with(['category', 'subcategory', 'images'])->get();

        // Return the articles
        return ApiResponse::success(
            data: ['articles' => $articles],
            message: __(
                'messages.common.retrieved_all', 
                ['items' => __('messages.entities.article.plural')]
            )
        );
    }

    /**
     * Store a new article.
     * 
     * This method handles the creation of a new article
     * by validating the request data and storing it in the database.
     * @requestMediaType multipart/form-data
     * 
     * @param CreateArticleRequest $request The request object containing the data 
     * for creating an article.
     * @return ApiResponse A JSON response indicating the success of the operation.
     * @throws \Throwable If there is an error during the creation process,
     * such as database transaction failure or file storage issues.
     */
    public function store(CreateArticleRequest $request): JsonResponse
    {
        // Validate the request data
        $data = $request->validated();

        try {
            // Begin a database transaction to ensure atomicity
            DB::beginTransaction();

            // Create a new article in the database
            $article = Article::create($data);

            // Check if images are provided in the request
            if ($request->hasFile('images')) {
                // Store each image and create a record in the database
                $images = $this->storeImages(
                    images: $request->file('images'), 
                    relatedId: $article->id,
                    prefix: 'article_image',
                    directory: 'articles'
                );
                $article->images()->createMany($images);
            }

            // Commit the transaction if all operations are successful
            DB::commit();

            // Return a successful response with the created article
            return ApiResponse::success(
                status: Response::HTTP_CREATED,
                message: __(
                    'messages.common.created', 
                    ['item' => __('messages.entities.article.singular')]
                ),
                data: [
                    'article' => ArticleResource::make(
                        $article->load('images')
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
                    ['item' => __('messages.entities.article.singular')]
                ),
                errors: ['exception' => $th->getMessage()]
            );
        }
    }

    /**
     * Display a listing of articles in a specific category.
     * 
     * This method retrieves all articles in a specific category
     * and returns them in a JSON response.
     * 
     * @param string $category The category of the articles to be displayed.
     * @return ApiResponse A JSON response containing the details of the articles.
     * @throws \Exception If the articles do not exist or if there is an error retrieving them.
     */
    public function show(string $category): JsonResponse
    {
        $articles = Article::whereHas('category', function ($query) use ($category) {
            $query->where('name', $category);
        })
        ->with(['images', 'category', 'subcategory'])
        ->get(); // Retrieve all articles in the specified category

        if ($articles->isEmpty()) {
            return ApiResponse::error(
                message: __(
                    'messages.common.not_found', 
                    ['item' => __('messages.entities.article.singular')]
                ),
                status: Response::HTTP_NOT_FOUND
            );
        }

        return ApiResponse::success(
            data: ['articles' => ArticleResource::collection($articles)],
            message: __(
                'messages.common.retrieved',
                ['item' => __('messages.entities.article.singular')]
            )
        );
    }

    /**
     * Display a specific article.
     * 
     * This method retrieves a specific article by its ID
     * and returns its details in a JSON response.
     * 
     * @param Article $article The article to be displayed.
     * @return ApiResponse A JSON response containing the details of the article.
     * @throws \Exception If the article does not exist or if there is an error retrieving it.
     */
    public function showById(Article $article): JsonResponse
    {
        // Check if the article exists
        if (!$article->exists()) {
            return ApiResponse::error(
                message: __(
                    'messages.common.not_found', 
                    ['item' => __('messages.entities.article.singular')]
                ),
                status: Response::HTTP_NOT_FOUND
            );
        }
        
        // Load the images associated with the article
        $article->load(['images', 'category', 'subcategory']);

        // Return a successful response with the article details
        return ApiResponse::success(
            data: ['article' => new ArticleResource($article)],
            message: __(
                'messages.common.retrieved',
                ['item' => __('messages.entities.article.singular')]
            )
        );
    }

    /**
     * Update an existing article.
     * 
     * This method handles the update of an existing article by its ID
     * by validating the request data and updating the record in the database.
     *
     * @param Article $article The article to be updated.
     * @param UpdateArticleRequest $request The request object containing the data
     * for updating the article.
     * @return ApiResponse A JSON response indicating the success of the operation.
     * @throws \Throwable If there is an error during the update process,
     * such as database transaction failure or validation issues.
     */
    public function update(UpdateArticleRequest $request, Article $article): JsonResponse
    {
        // Validate the request data
        $data = $request->validated();

        try {
            // Begin a database transaction to ensure atomicity
            DB::beginTransaction();

            // Update the article in the database
            $article->update($data);

            // Check if images are provided in the request
            if ($request->hasFile('images')) {
                // First, delete existing images
                if ($article->images()->exists()) {
                    $this->deleteImages($article->images());
                }
                
                // Then store new images
                $images = $this->storeImages(
                    images: $request->file('images'), 
                    relatedId: $article->id,
                    prefix: 'article_image',
                    directory: 'articles'
                );
                $article->images()->createMany($images);
            }

            // Commit the transaction if all operations are successful
            DB::commit();

            // Return a successful response with the updated article
            return ApiResponse::success(
                message: __(
                    'messages.common.updated', 
                    ['item' => __('messages.entities.article.singular')]
                ),
                data: [
                    'article' => ArticleResource::make(
                        $article->load('images')
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
                    'messages.common.update_failed', 
                    ['item' => __('messages.entities.article.singular')]
                ),
                errors: ['exception' => $th->getMessage()]
            );
        }
    }

    /**
     * Remove a specific article.
     * 
     * This method deletes a specific article by its ID from the database,
     * including its associated images, if they exist, using a soft delete approach.
     *
     * @param Article $article The article to be deleted.
     * @return ApiResponse A JSON response indicating the success of the operation.
     * @throws \Throwable If there is an error during the deletion process,
     * such as database transaction failure or file storage issues.
     */
    public function destroy(Article $article): JsonResponse
    {
        // Check if the article exists
        if (!$article->exists()) {
            // If the article does not exist, return an error response
            return ApiResponse::error(
                status: Response::HTTP_BAD_REQUEST,
                message: __(
                    'messages.common.not_found',
                    ['item' => __('messages.entities.article.singular')]
                )
            );
        }

        try {
            // Begin a database transaction to ensure atomicity
            DB::beginTransaction();
        
            // Check if the article has associated images
            // If so, delete each image from storage and remove the record from the database
            if ($article->images()->exists()) {
                $this->deleteImages($article->images());
            }

            // Attempt to delete the article
            // If deletion fails, return an error response
            if (!$article->delete()) {
                throw new \Exception(__(
                    'messages.common.deletion_failed', 
                    ['item' => __('messages.entities.article.singular')]
                ));
            }

            // Commit the transaction if all operations are successful
            DB::commit();

            // Return a successful response indicating the article was deleted
            return ApiResponse::success(message: __(
                'messages.common.deleted',
                ['item' => __('messages.entities.article.singular')]
            ));
        } catch (\Throwable $th) {
            // Rollback the transaction if any operation fails
            DB::rollBack();

            // Return an error response with the exception message
            return ApiResponse::error(
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                message: __(
                    'messages.common.deletion_failed',
                    ['item' => __('messages.entities.article.singular')]
                ),
                errors: ['exception' => $th->getMessage()]
            );
        }
    }
}
