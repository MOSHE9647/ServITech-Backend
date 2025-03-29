<?php

namespace App\Http\Controllers\Model;

use App\Http\Controllers\Controller;
use App\Http\Requests\ArticleRequest\CreateArticleRequest;
use App\Http\Requests\ArticleRequest\UpdateArticleRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() 
    {
        // Fetch all articles with their associated category and subcategory
        $articles = Article::orderBy('id', 'desc')->with(['category', 'subcategory', 'images'])->get();

        // Return the articles as a JSON response
        return ApiResponse::success(
            data: compact('articles'),
            message: __('messages.article.retrieved_all')
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateArticleRequest $request)
    {
        $data = $request->validated();
        $article = Article::create($data);

        return ApiResponse::success(
            data: compact('article'),
            message: __('messages.article.created')
        );
    }
    

    /**
     * Display the specified resource.
     */
    public function show(Article $article)
    {
        return ApiResponse::success(
            data: compact('article'),
            message: __('messages.article.retrieved')
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateArticleRequest $request, Article $article)
    {
        $data = $request->validated();
        $article->update($data);
        return ApiResponse::success(
            message: __('messages.article.updated'),
            data: compact('article'),
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Article $article)
    {
        $article->delete();
        return ApiResponse::success(message: __('messages.article.deleted'));
    }
}
