<?php

namespace Tests\Feature\Article;

use App\Enums\UserRoles;
use App\Models\Article;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\User;
use Database\Seeders\ArticleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleShowByCategoryTest extends TestCase
{
    use RefreshDatabase; // Reset the database after each test

    /**
     * Set up the test environment.
     * This method seeds the database before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            // Seed the database with necessary data
            UserSeeder::class,
            ArticleSeeder::class,
        ]);
    }    
    
    /**
     * Test that articles can be retrieved by an existing category name.
     * This ensures that the endpoint returns articles for a valid category.
     */
    public function test_can_get_articles_by_existing_category(): void
    {
        // Given: A category with articles
        $category = Category::has('articles')->first();
        $this->assertNotNull($category, 'Category with articles not found');
        
        $expectedArticleCount = $category->articles()->count();
        $this->assertGreaterThan(0, $expectedArticleCount, 'Category should have articles');

        // When: Making a GET request to retrieve articles by category
        $response = $this->getJson(route('articles.show', ['article' => $category->name]));

        // Then: The request should succeed and return the articles
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message', 
            'data' => [
                'articles' => [
                    '*' => [
                        'id',
                        'category_id',
                        'subcategory_id',
                        'name',
                        'description',
                        'price',
                        'images'
                    ]
                ]
            ]
        ]);

        $response->assertJsonFragment([
            'message' => __('messages.common.retrieved', ['item' => __('messages.entities.article.singular')])
        ]);

        // Verify the correct number of articles are returned
        $responseData = $response->json();
        $this->assertCount($expectedArticleCount, $responseData['data']['articles']);

        // Verify all returned articles belong to the requested category
        foreach ($responseData['data']['articles'] as $article) {
            $this->assertEquals($category->id, $article['category_id']);
        }
    }

    /**
     * Test that a 404 error is returned for a non-existent category.
     * This ensures proper error handling for invalid categories.
     */
    public function test_returns_404_for_non_existent_category(): void
    {
        // Given: A non-existent category name
        $nonExistentCategoryName = 'non-existent-category-12345';

        // When: Making a GET request with the non-existent category
        $response = $this->getJson(route('articles.show', ['article' => $nonExistentCategoryName]));

        // Then: The request should return a 404 error
        $response->assertStatus(404);
        $response->assertJsonStructure(['status', 'message']);
        
        $response->assertJsonFragment([
            'status' => 404,
            'message' => __('messages.common.not_found', ['item' => __('messages.entities.article.singular')])
        ]);
    }

    /**
     * Test that a 404 error is returned for a category with no articles.
     * This ensures proper handling when a category exists but has no articles.
     */
    public function test_returns_404_for_category_without_articles(): void
    {
        // Given: A category without articles
        $category = Category::doesntHave('articles')->first();
        
        // If no such category exists, create one
        if (!$category) {
            $category = Category::factory()->create([
                'name' => 'empty-category',
                'description' => 'A category without articles'
            ]);
        }

        $this->assertEquals(0, $category->articles()->count(), 'Category should have no articles');

        // When: Making a GET request for the empty category
        $response = $this->getJson(route('articles.show', ['article' => $category->name]));

        // Then: The request should return a 404 error
        $response->assertStatus(404);
        $response->assertJsonStructure(['status', 'message']);
        
        $response->assertJsonFragment([
            'status' => 404,
            'message' => __('messages.common.not_found', ['item' => __('messages.entities.article.singular')])
        ]);
    }
    
    /**
     * Test that the endpoint works with different category names.
     * This ensures proper handling of category names.
     */
    public function test_handles_different_category_names(): void
    {
        // Given: Create a category with a simple name
        $categoryName = 'TestCategorySpaces';
        $category = Category::factory()->create([
            'name' => $categoryName,
            'description' => 'A test category'
        ]);
        
        // Create a subcategory for this category
        $subcategory = Subcategory::factory()->create([
            'category_id' => $category->id,
            'name' => 'Test Subcategory',
            'description' => 'Test subcategory'
        ]);
        
        // Create some articles for this category
        $article = Article::factory()->create([
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id
        ]);

        // When: Making a GET request with the category name
        $response = $this->getJson(route('articles.show', ['article' => $categoryName]));

        // Then: The request should succeed
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => ['articles']
        ]);
        
        // Verify the returned article belongs to the correct category
        $responseData = $response->json();
        $this->assertCount(1, $responseData['data']['articles']);
        $this->assertEquals($category->id, $responseData['data']['articles'][0]['category_id']);
    }
    
    /**
     * Test that articles are returned with proper relationships loaded.
     * This ensures that category_id, subcategory_id, and images are properly included.
     */
    public function test_articles_include_proper_relationships(): void
    {
        // Given: A category with articles that have relationships
        $category = Category::has('articles')->first();
        $this->assertNotNull($category, 'Category with articles not found');

        // When: Making a GET request to retrieve articles by category
        $response = $this->getJson(route('articles.show', ['article' => $category->name]));

        // Then: The articles should include all necessary relationships
        $response->assertStatus(200);
        
        $responseData = $response->json();
        $articles = $responseData['data']['articles'];
        
        $this->assertNotEmpty($articles, 'Should return articles');
        
        foreach ($articles as $article) {
            // Verify category_id
            $this->assertArrayHasKey('category_id', $article);
            $this->assertEquals($category->id, $article['category_id']);
            
            // Verify subcategory_id
            $this->assertArrayHasKey('subcategory_id', $article);
            $this->assertIsNumeric($article['subcategory_id']);
            
            // Verify images relationship (array should exist even if empty)
            $this->assertArrayHasKey('images', $article);
            $this->assertIsArray($article['images']);
            
            // Verify other required fields
            $this->assertArrayHasKey('id', $article);
            $this->assertArrayHasKey('name', $article);
            $this->assertArrayHasKey('description', $article);
            $this->assertArrayHasKey('price', $article);
        }
    }

    /**
     * Test that case-sensitive category matching works correctly.
     * This ensures that category names are matched correctly regardless of case.
     */
    public function test_category_matching_is_case_sensitive(): void
    {
        // Given: A category with articles
        $category = Category::has('articles')->first();
        $this->assertNotNull($category, 'Category with articles not found');
        
        $originalCategoryName = $category->name;
        $uppercaseCategoryName = strtoupper($originalCategoryName);

        // When: Making requests with different cases
        $originalResponse = $this->getJson(route('articles.show', ['article' => $originalCategoryName]));
        $uppercaseResponse = $this->getJson(route('articles.show', ['article' => $uppercaseCategoryName]));

        // Then: Original case should work, uppercase should fail (if they're different)
        $originalResponse->assertStatus(200);
        
        if ($originalCategoryName !== $uppercaseCategoryName) {
            $uppercaseResponse->assertStatus(404);
        }
    }

    /**
     * Test that soft-deleted articles are not included in the results.
     * This ensures that only active articles are returned.
     */
    public function test_soft_deleted_articles_are_not_included(): void
    {
        // Given: A category with articles, where we soft delete one article
        $category = Category::has('articles')->first();
        $this->assertNotNull($category, 'Category with articles not found');
        
        $articlesInCategory = $category->articles;
        $this->assertGreaterThan(0, $articlesInCategory->count(), 'Category should have articles');
        
        $articleToDelete = $articlesInCategory->first();
        $originalCount = $articlesInCategory->count();
        
        // Soft delete one article
        $articleToDelete->delete();

        // When: Making a GET request to retrieve articles by category
        $response = $this->getJson(route('articles.show', ['article' => $category->name]));

        // Then: The soft-deleted article should not be included
        if ($originalCount > 1) {
            $response->assertStatus(200);
            $responseData = $response->json();
            $this->assertCount($originalCount - 1, $responseData['data']['articles']);
            
            // Verify the deleted article is not in the results
            $returnedArticleIds = array_column($responseData['data']['articles'], 'id');
            $this->assertNotContains($articleToDelete->id, $returnedArticleIds);
        } else {
            // If it was the only article, should return 404
            $response->assertStatus(404);
        }
    }

    /**
     * Test that the endpoint is accessible without authentication.
     * This ensures that article browsing is public.
     */
    public function test_endpoint_is_accessible_without_authentication(): void
    {
        // Given: A category with articles
        $category = Category::has('articles')->first();
        $this->assertNotNull($category, 'Category with articles not found');

        // When: Making a GET request without authentication
        $response = $this->getJson(route('articles.show', ['article' => $category->name]));

        // Then: The request should succeed (public endpoint)
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => ['articles']
        ]);
    }

    /**
     * Test that the endpoint works with authenticated users.
     * This ensures that authenticated users can also access the endpoint.
     */
    public function test_endpoint_works_with_authenticated_users(): void
    {
        // Given: A category with articles and an authenticated user
        $category = Category::has('articles')->first();
        $this->assertNotNull($category, 'Category with articles not found');
        
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        // When: Making an authenticated GET request
        $response = $this->apiAs($user, 'GET', route('articles.show', ['article' => $category->name]));

        // Then: The request should succeed
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => ['articles']
        ]);
    }

    /**
     * Test that the endpoint works with admin users.
     * This ensures that admin users can also access the endpoint.
     */
    public function test_endpoint_works_with_admin_users(): void
    {
        // Given: A category with articles and an admin user
        $category = Category::has('articles')->first();
        $this->assertNotNull($category, 'Category with articles not found');
        
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        // When: Making an authenticated GET request as admin
        $response = $this->apiAs($admin, 'GET', route('articles.show', ['article' => $category->name]));

        // Then: The request should succeed
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => ['articles']
        ]);
    }
}