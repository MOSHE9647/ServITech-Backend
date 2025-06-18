<?php

namespace Tests\Feature\RepairRequest;

use App\Enums\RepairStatus;
use App\Enums\UserRoles;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RepairRequestCreateTest extends TestCase
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
            UserSeeder::class,
        ]);
    }

    /**
     * Test that an authenticated admin user can create a repair request.
     * This ensures that only admin users can create repair requests successfully.
     */
    public function test_an_authenticated_admin_user_can_create_a_repair_request(): void
    {
        Storage::fake('public');
        
        // Given: A valid repair request payload
        $repairRequestData = [
            'customer_name' => 'John Doe',
            'customer_phone' => '1234567890',
            'customer_email' => 'john.doe@example.com',
            'article_name' => 'iPhone 13',
            'article_type' => 'Smartphone',
            'article_brand' => 'Apple',
            'article_model' => 'iPhone 13',
            'article_serialnumber' => 'ABC123456789',
            'article_accesories' => 'Charger, Case',
            'article_problem' => 'Screen is cracked and not responding to touch',
            'repair_status' => RepairStatus::PENDING->value,
            'repair_details' => 'Initial diagnostic pending',
            'repair_price' => 150.00,
            'received_at' => now()->format('Y-m-d'),
            'repaired_at' => null,
            'images' => [
                UploadedFile::fake()->image('repair1.jpg'),
                UploadedFile::fake()->image('repair2.jpg'),
            ]
        ];

        // When: An admin user attempts to create a repair request
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'POST', route('repair-request.store'), $repairRequestData);

        // Then: The request should succeed, and the repair request should be stored in the database
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'repairRequest' => [
                    'id',
                    'receipt_number',
                    'customer_name',
                    'customer_phone',
                    'customer_email',
                    'article_name',
                    'article_type',
                    'article_brand',
                    'article_model',
                    'article_serialnumber',
                    'article_accesories',
                    'article_problem',
                    'repair_status',
                    'repair_details',
                    'repair_price',
                    'received_at',
                    'repaired_at',
                    'images'
                ]
            ]
        ]);

        $response->assertJsonFragment([
            'message' => __('messages.common.created', ['item' => __('messages.entities.repair_request.singular')])
        ]);

        // Verify the repair request data exists in the database
        $this->assertDatabaseHas('repair_requests', [
            'customer_name' => $repairRequestData['customer_name'],
            'customer_email' => $repairRequestData['customer_email'],
            'article_name' => $repairRequestData['article_name'],
            'repair_status' => $repairRequestData['repair_status'],
        ]);

        // Verify the images are stored
        $responseData = $response->json();
        $repairRequest = $responseData['data']['repairRequest'];
        $this->assertCount(2, $repairRequest['images']);
    }

    /**
     * Test that an authenticated non-admin user cannot create a repair request.
     * This ensures that only admin users can create repair requests.
     */
    public function test_an_authenticated_non_admin_user_cannot_create_a_repair_request(): void
    {
        // Given: A valid repair request payload
        $repairRequestData = [
            'customer_name' => 'Jane Doe',
            'customer_phone' => '0987654321',
            'customer_email' => 'jane.doe@example.com',
            'article_name' => 'Samsung Galaxy S21',
            'article_type' => 'Smartphone',
            'article_brand' => 'Samsung',
            'article_model' => 'Galaxy S21',
            'article_problem' => 'Battery drains quickly',
            'repair_status' => RepairStatus::PENDING->value,
            'received_at' => now()->format('Y-m-d'),
        ];

        // When: A non-admin user attempts to create a repair request
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequestData);

        // Then: The request should fail with a 403 Forbidden status
        $response->assertStatus(403);
        $response->assertJsonStructure(['status', 'message']);

        // Verify that the repair request was not created in the database
        $this->assertDatabaseMissing('repair_requests', [
            'customer_email' => $repairRequestData['customer_email']
        ]);
    }

    /**
     * Test that a non-authenticated user cannot create a repair request.
     * This ensures that only authenticated users can create repair requests.
     */
    public function test_a_non_authenticated_user_cannot_create_a_repair_request(): void
    {
        // Given: A valid repair request payload
        $repairRequestData = [
            'customer_name' => 'Bob Smith',
            'customer_phone' => '5555555555',
            'customer_email' => 'bob.smith@example.com',
            'article_name' => 'MacBook Pro',
            'article_type' => 'Laptop',
            'article_brand' => 'Apple',
            'article_model' => 'MacBook Pro 13"',
            'article_problem' => 'Keyboard not working',
            'repair_status' => RepairStatus::PENDING->value,
            'received_at' => now()->format('Y-m-d'),
        ];

        // When: A non-authenticated user attempts to create a repair request
        $response = $this->postJson(route('repair-request.store'), $repairRequestData);

        // Then: The request should fail with a 401 Unauthorized status
        $response->assertStatus(401);
        $response->assertJsonStructure(['status', 'message']);

        // Verify that the repair request was not created in the database
        $this->assertDatabaseMissing('repair_requests', [
            'customer_email' => $repairRequestData['customer_email']
        ]);
    }

    /**
     * Test that the customer_name field is required.
     * This ensures that missing the customer_name field returns a 422 status with validation errors.
     */
    public function test_customer_name_must_be_required(): void
    {
        // Given: A repair request payload with a missing customer_name
        $repairRequestData = [
            'customer_phone' => '1234567890',
            'customer_email' => 'test@example.com',
            'article_name' => 'Test Article',
            'article_type' => 'Test Type',
            'article_brand' => 'Test Brand',
            'article_model' => 'Test Model',
            'article_problem' => 'Test problem description',
            'repair_status' => RepairStatus::PENDING->value,
            'received_at' => now()->format('Y-m-d'),
        ];

        // When: An admin user attempts to create a repair request
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'POST', route('repair-request.store'), $repairRequestData);        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['customer_name']]);

        // Check that the error message for customer_name exists
        $responseData = $response->json();
        $this->assertArrayHasKey('customer_name', $responseData['errors']);
        $this->assertStringContainsString('obligatorio', $responseData['errors']['customer_name']);
    }

    /**
     * Test that the customer_name must be a string.
     * This ensures that non-string values for the customer_name field return a 422 status with validation errors.
     */
    public function test_customer_name_must_be_a_string(): void
    {
        // Given: A repair request payload with a non-string customer_name
        $repairRequestData = [
            'customer_name' => 12345, // Non-string value
            'customer_phone' => '1234567890',
            'customer_email' => 'test@example.com',
            'article_name' => 'Test Article',
            'article_type' => 'Test Type',
            'article_brand' => 'Test Brand',
            'article_model' => 'Test Model',
            'article_problem' => 'Test problem description',
            'repair_status' => RepairStatus::PENDING->value,
            'received_at' => now()->format('Y-m-d'),
        ];

        // When: An admin user attempts to create a repair request
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'POST', route('repair-request.store'), $repairRequestData);        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['customer_name']]);

        // Check that the error message for customer_name exists
        $responseData = $response->json();
        $this->assertArrayHasKey('customer_name', $responseData['errors']);
        $this->assertStringContainsString('cadena', $responseData['errors']['customer_name']);
    }

    /**
     * Test that the customer_name must have at least 3 characters.
     * This ensures that short names return a 422 status with validation errors.
     */
    public function test_customer_name_must_have_at_least_3_characters(): void
    {
        // Given: A repair request payload with a customer_name that has less than 3 characters
        $repairRequestData = [
            'customer_name' => 'Jo', // Less than 3 characters
            'customer_phone' => '1234567890',
            'customer_email' => 'test@example.com',
            'article_name' => 'Test Article',
            'article_type' => 'Test Type',
            'article_brand' => 'Test Brand',
            'article_model' => 'Test Model',
            'article_problem' => 'Test problem description',
            'repair_status' => RepairStatus::PENDING->value,
            'received_at' => now()->format('Y-m-d'),
        ];

        // When: An admin user attempts to create a repair request
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'POST', route('repair-request.store'), $repairRequestData);        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['customer_name']]);

        // Check that the error message for customer_name exists
        $responseData = $response->json();
        $this->assertArrayHasKey('customer_name', $responseData['errors']);
        $this->assertStringContainsString('3 caracteres', $responseData['errors']['customer_name']);
    }

    /**
     * Test that the customer_email field is required.
     * This ensures that missing the customer_email field returns a 422 status with validation errors.
     */
    public function test_customer_email_must_be_required(): void
    {
        // Given: A repair request payload with a missing customer_email
        $repairRequestData = [
            'customer_name' => 'John Doe',
            'customer_phone' => '1234567890',
            'article_name' => 'Test Article',
            'article_type' => 'Test Type',
            'article_brand' => 'Test Brand',
            'article_model' => 'Test Model',
            'article_problem' => 'Test problem description',
            'repair_status' => RepairStatus::PENDING->value,
            'received_at' => now()->format('Y-m-d'),
        ];

        // When: An admin user attempts to create a repair request
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'POST', route('repair-request.store'), $repairRequestData);        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['customer_email']]);

        // Check that the error message for customer_email exists
        $responseData = $response->json();
        $this->assertArrayHasKey('customer_email', $responseData['errors']);
        $this->assertStringContainsString('obligatorio', $responseData['errors']['customer_email']);
    }

    /**
     * Test that the customer_email must be a valid email.
     * This ensures that invalid email formats return a 422 status with validation errors.
     */
    public function test_customer_email_must_be_valid_email(): void
    {
        // Given: A repair request payload with an invalid email
        $repairRequestData = [
            'customer_name' => 'John Doe',
            'customer_phone' => '1234567890',
            'customer_email' => 'invalid-email', // Invalid email format
            'article_name' => 'Test Article',
            'article_type' => 'Test Type',
            'article_brand' => 'Test Brand',
            'article_model' => 'Test Model',
            'article_problem' => 'Test problem description',
            'repair_status' => RepairStatus::PENDING->value,
            'received_at' => now()->format('Y-m-d'),
        ];

        // When: An admin user attempts to create a repair request
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'POST', route('repair-request.store'), $repairRequestData);        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['customer_email']]);

        // Check that the error message for customer_email exists
        $responseData = $response->json();
        $this->assertArrayHasKey('customer_email', $responseData['errors']);
        $this->assertStringContainsString('correo válido', $responseData['errors']['customer_email']);
    }

    /**
     * Test that the repair_status field is required.
     * This ensures that missing the repair_status field returns a 422 status with validation errors.
     */
    public function test_repair_status_must_be_required(): void
    {
        // Given: A repair request payload with a missing repair_status
        $repairRequestData = [
            'customer_name' => 'John Doe',
            'customer_phone' => '1234567890',
            'customer_email' => 'test@example.com',
            'article_name' => 'Test Article',
            'article_type' => 'Test Type',
            'article_brand' => 'Test Brand',
            'article_model' => 'Test Model',
            'article_problem' => 'Test problem description',
            'received_at' => now()->format('Y-m-d'),
        ];

        // When: An admin user attempts to create a repair request
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'POST', route('repair-request.store'), $repairRequestData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['repair_status']]);
    }

    /**
     * Test that the repair_status must be a valid enum value.
     * This ensures that invalid repair status values return a 422 status with validation errors.
     */
    public function test_repair_status_must_be_valid_enum_value(): void
    {
        // Given: A repair request payload with an invalid repair_status
        $repairRequestData = [
            'customer_name' => 'John Doe',
            'customer_phone' => '1234567890',
            'customer_email' => 'test@example.com',
            'article_name' => 'Test Article',
            'article_type' => 'Test Type',
            'article_brand' => 'Test Brand',
            'article_model' => 'Test Model',
            'article_problem' => 'Test problem description',
            'repair_status' => 'invalid_status', // Invalid enum value
            'received_at' => now()->format('Y-m-d'),
        ];

        // When: An admin user attempts to create a repair request
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'POST', route('repair-request.store'), $repairRequestData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors' => ['repair_status']]);
    }

    /**
     * Test that images must be valid image files.
     * This ensures that invalid file types return a 422 status with validation errors.
     */
    public function test_images_must_be_valid_image_files(): void
    {
        Storage::fake('public');
        
        // Given: A repair request payload with invalid image files
        $repairRequestData = [
            'customer_name' => 'John Doe',
            'customer_phone' => '1234567890',
            'customer_email' => 'test@example.com',
            'article_name' => 'Test Article',
            'article_type' => 'Test Type',
            'article_brand' => 'Test Brand',
            'article_model' => 'Test Model',
            'article_problem' => 'Test problem description',
            'repair_status' => RepairStatus::PENDING->value,
            'received_at' => now()->format('Y-m-d'),
            'images' => [
                UploadedFile::fake()->create('document.pdf'), // Invalid file type
                UploadedFile::fake()->create('document.txt'), // Invalid file type
            ]
        ];

        // When: An admin user attempts to create a repair request
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'POST', route('repair-request.store'), $repairRequestData);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
    }

    /**
     * Test that repair requests can be created without images.
     * This ensures that images are optional for repair request creation.
     */
    public function test_repair_request_can_be_created_without_images(): void
    {
        // Given: A valid repair request payload without images
        $repairRequestData = [
            'customer_name' => 'Alice Johnson',
            'customer_phone' => '1111111111',
            'customer_email' => 'alice@example.com',
            'article_name' => 'iPad Air',
            'article_type' => 'Tablet',
            'article_brand' => 'Apple',
            'article_model' => 'iPad Air 4th Gen',
            'article_problem' => 'Screen has black spots',
            'repair_status' => RepairStatus::PENDING->value,
            'received_at' => now()->format('Y-m-d'),
        ];

        // When: An admin user attempts to create a repair request
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'POST', route('repair-request.store'), $repairRequestData);

        // Then: The request should succeed
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'repairRequest' => [
                    'id',
                    'customer_name',
                    'customer_email',
                    'images'
                ]
            ]
        ]);

        // Verify no images are attached
        $responseData = $response->json();
        $repairRequest = $responseData['data']['repairRequest'];
        $this->assertEmpty($repairRequest['images']);
    }
}
