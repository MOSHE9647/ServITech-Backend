<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase; // RefreshDatabase trait to reset the database after each test

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class); // Seed the database with test users
    }

    public function test_index_returns_categories()
    {
        // Given:
        Category::factory()->count(3)->create(); // Create 3 categories

        // When:
        $user = User::find(1)->first(); // Find the user with ID 1
        $this->assertNotNull($user, __('messages.user_not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'GET', "{$this->apiBase}/category");

        // Then:
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status', 'message', 'data' => ['categories']
        ]);

        $response->assertJsonFragment([
            'status' => 200,
            'message'=> __('messages.categories_retrieved'),
            'data' => ['categories' => Category::all()->toArray()],
        ]);
    }

    public function test_store_creates_category()
    {
        // Given:
        $data = ['name' => 'New Category'];

        // When:
        $user = User::find(1)->first(); // Find the user with ID 1
        $this->assertNotNull($user, __('messages.user_not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', "{$this->apiBase}/category", $data);

        // Then:
        $response->assertStatus(200);
        $response->assertJsonStructure(['message', 'status', 'data' => ['category']]);
        $response->assertJsonFragment([
            'status' => 200,
            'message'=> __('messages.category_created'),
            'data' => [
                'category' => [
                    'id'=> 1,
                    'name'=> $data['name'],
                    'created_at'=> now()->format('Y-m-d\TH:i:s.000000\Z'),
                    'updated_at'=> now()->format('Y-m-d\TH:i:s.000000\Z'),
                ]
            ],
        ]);

        // Ensure the category is in the database
        $this->assertDatabaseHas('categories', $data);
    }

    public function test_show_returns_category()
    {
        // Given:
        $category = Category::factory()->create(); // Create a category

        // When:
        $user = User::find(1)->first(); // Find the user with ID 1
        $this->assertNotNull($user, __('messages.user_not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'GET', "{$this->apiBase}/category/{$category->name}");

        // Then:
        $response->assertStatus(200);
        $response->assertJsonStructure(['message', 'status', 'data' => ['category']]);
        $response->assertJsonFragment([
            'status' => 200,
            'message'=> __('messages.category_retrieved'),
            'data' => [
                'category' => [
                    'id'=> 1,
                    'name'=> $category->name,
                    'description'=> $category->description,
                    'created_at'=> now()->format('Y-m-d\TH:i:s.000000\Z'),
                    'updated_at'=> now()->format('Y-m-d\TH:i:s.000000\Z'),
                    'deleted_at'=> null,
                ]
            ],
        ]);
    }

    public function test_update_modifies_category()
    {
        // Given:
        $category = Category::factory()->create(); // Create a category
        $data = ['name' => 'Updated Category'];

        // When:
        $user = User::find(1)->first(); // Find the user with ID 1
        $this->assertNotNull($user, __('messages.user_not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'PUT', "{$this->apiBase}/category/{$category->name}", $data);
        // dd($response->json());

        // Then:
        $response->assertStatus(200);
        $response->assertJsonStructure(['message', 'status', 'data' => ['category']]);
        $response->assertJsonFragment([
            'status' => 200,
            'message'=> __('messages.category_updated'),
            'data' => [
                'category' => [
                    'id'=> 1,
                    'name'=> $data['name'],
                    'description'=> $category->description,
                    'created_at'=> now()->format('Y-m-d\TH:i:s.000000\Z'),
                    'updated_at'=> now()->format('Y-m-d\TH:i:s.000000\Z'),
                    'deleted_at'=> null,
                ]
            ],
        ]);

        // Ensure the category is updated in the database
        $this->assertDatabaseHas('categories', $data);
    }

    public function test_destroy_deletes_category()
    {
        // Given:
        $category = Category::factory()->create(); // Create a category

        // When:
        $user = User::find(1)->first(); // Find the user with ID 1
        $this->assertNotNull($user, __('messages.user_not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'DELETE', "{$this->apiBase}/category/{$category->name}");

        // Then:
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'message']);
        $response->assertJsonFragment([
            'status'=> 200,
            'message'=> __('messages.category_deleted'),
        ]);

        // Ensure the category is soft deleted in the database
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'deleted_at' => now()->format('Y-m-d H:i:s')
        ]);
    }
}