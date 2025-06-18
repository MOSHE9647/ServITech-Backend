<?php

namespace Tests\Feature\RepairRequest;

use App\Enums\RepairStatus;
use App\Enums\UserRoles;
use App\Models\RepairRequest;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepairRequestShowByReceiptNumberTest extends TestCase
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
     * Test that an authenticated admin user can view a repair request by receipt number.
     * This ensures that admin users can access specific repair request details.
     */
    public function test_an_authenticated_admin_user_can_view_repair_request_by_receipt_number(): void
    {
        // Given: An existing repair request
        $repairRequest = RepairRequest::factory()->create([
            'repair_status' => RepairStatus::PENDING->value,
            'customer_name' => 'John Doe',
            'customer_email' => 'john.doe@example.com',
        ]);
        $this->assertNotNull($repairRequest, 'Repair request not found');
        $this->assertNotNull($repairRequest->receipt_number, 'Receipt number should not be null');

        // When: An admin user attempts to view the repair request by receipt number
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'GET', route('repair-request.show', ['repairRequest' => $repairRequest->receipt_number]));

        // Then: The request should succeed and return the repair request details
        $response->assertStatus(200);
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
                    'repair_details',
                    'repair_price',
                    'received_at',
                    'repaired_at',
                    'repair_status',
                    'images'
                ]
            ]
        ]);

        $response->assertJsonFragment([
            'message' => __('messages.common.retrieved', ['item' => __('messages.entities.repair_request.singular')])
        ]);

        // Verify the returned repair request data matches the expected repair request
        $responseData = $response->json();
        $repairRequestData = $responseData['data']['repairRequest'];
        // dd($repairRequestData, $responseData); // Debugging line to inspect the repair request data

        $this->assertEquals($repairRequest->id, $repairRequestData['id']);
        $this->assertEquals($repairRequest->receipt_number, $repairRequestData['receipt_number']);
        $this->assertEquals($repairRequest->customer_name, $repairRequestData['customer_name']);
        $this->assertEquals($repairRequest->customer_email, $repairRequestData['customer_email']);
        $this->assertEquals($repairRequest->repair_status->value, $repairRequestData['repair_status']);
    }

    /**
     * Test that an authenticated non-admin user cannot view a repair request.
     * This ensures that only admin users can access repair request details.
     */
    public function test_an_authenticated_non_admin_user_cannot_view_repair_request(): void
    {
        // Given: An existing repair request
        $repairRequest = RepairRequest::factory()->create([
            'repair_status' => RepairStatus::PENDING->value,
        ]);
        $this->assertNotNull($repairRequest, 'Repair request not found');

        // When: A non-admin user attempts to view the repair request
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $response = $this->apiAs($user, 'GET', route('repair-request.show', ['repairRequest' => $repairRequest->receipt_number]));

        // Then: The request should fail with a 403 Forbidden status
        $response->assertStatus(403);
        $response->assertJsonStructure(['status', 'message']);
    }

    /**
     * Test that a non-authenticated user cannot view a repair request.
     * This ensures that only authenticated users can access repair request details.
     */
    public function test_a_non_authenticated_user_cannot_view_repair_request(): void
    {
        // Given: An existing repair request
        $repairRequest = RepairRequest::factory()->create([
            'repair_status' => RepairStatus::PENDING->value,
        ]);
        $this->assertNotNull($repairRequest, 'Repair request not found');

        // When: A non-authenticated user attempts to view the repair request
        $response = $this->getJson(route('repair-request.show', ['repairRequest' => $repairRequest->receipt_number]));

        // Then: The request should fail with a 401 Unauthorized status
        $response->assertStatus(401);
        $response->assertJsonStructure(['status', 'message']);
    }

    /**
     * Test that viewing a non-existent repair request returns 404.
     * This ensures proper error handling for invalid receipt numbers.
     */
    public function test_viewing_non_existent_repair_request_returns_404(): void
    {
        // Given: A non-existent receipt number
        $nonExistentReceiptNumber = 'REC-999999';

        // When: An admin user attempts to view the non-existent repair request
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'GET', route('repair-request.show', ['repairRequest' => $nonExistentReceiptNumber]));

        // Then: The request should fail with a 404 Not Found status
        $response->assertStatus(404);
    }

    /**
     * Test that viewing a soft-deleted repair request returns 404.
     * This ensures that deleted repair requests cannot be accessed.
     */
    public function test_viewing_soft_deleted_repair_request_returns_404(): void
    {
        // Given: A repair request that has been soft deleted
        $repairRequest = RepairRequest::factory()->create([
            'repair_status' => RepairStatus::PENDING->value,
        ]);
        $repairRequest->delete(); // Soft delete the repair request

        $this->assertSoftDeleted('repair_requests', ['id' => $repairRequest->id]);

        // When: An admin user attempts to view the deleted repair request
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'GET', route('repair-request.show', ['repairRequest' => $repairRequest->receipt_number]));

        // Then: The request should fail with a 404 Not Found status
        $response->assertStatus(404);
    }

    /**
     * Test that repair request includes associated images when present.
     * This ensures that images are properly loaded and included in the response.
     */
    public function test_repair_request_includes_images_when_present(): void
    {
        // Given: A repair request with associated images
        $repairRequest = RepairRequest::factory()->create([
            'repair_status' => RepairStatus::PENDING->value,
        ]);

        // Create some test images
        $repairRequest->images()->createMany([
            [
                'path' => 'repair_requests/test1.jpg',
                'title' => 'repair_request_image_' . $repairRequest->receipt_number . '_1',
                'alt' => 'Test image 1'
            ],
            [
                'path' => 'repair_requests/test2.jpg',
                'title' => 'repair_request_image_' . $repairRequest->receipt_number . '_2',
                'alt' => 'Test image 2'
            ]
        ]);

        $this->assertEquals(2, $repairRequest->images()->count(), 'Repair request should have 2 images');

        // When: An admin user views the repair request
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'GET', route('repair-request.show', ['repairRequest' => $repairRequest->receipt_number]));

        // Then: The repair request should include its images
        $response->assertStatus(200);
        $responseData = $response->json();
        $repairRequestData = $responseData['data']['repairRequest'];

        $this->assertArrayHasKey('images', $repairRequestData);
        $this->assertCount(2, $repairRequestData['images']);
        
        // Verify image structure
        $this->assertArrayHasKey('path', $repairRequestData['images'][0]);
        $this->assertArrayHasKey('title', $repairRequestData['images'][0]);
        $this->assertArrayHasKey('alt', $repairRequestData['images'][0]);
    }

    /**
     * Test that repair request shows empty images array when no images are present.
     * This ensures that repair requests without images are handled correctly.
     */
    public function test_repair_request_shows_empty_images_when_none_present(): void
    {
        // Given: A repair request without images
        $repairRequest = RepairRequest::factory()->create([
            'repair_status' => RepairStatus::PENDING->value,
        ]);

        $this->assertEquals(0, $repairRequest->images()->count(), 'Repair request should have no images');

        // When: An admin user views the repair request
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'GET', route('repair-request.show', ['repairRequest' => $repairRequest->receipt_number]));

        // Then: The repair request should have an empty images array
        $response->assertStatus(200);
        $responseData = $response->json();
        $repairRequestData = $responseData['data']['repairRequest'];

        $this->assertArrayHasKey('images', $repairRequestData);
        $this->assertEmpty($repairRequestData['images']);
    }

    /**
     * Test that repair requests with different statuses can be viewed.
     * This ensures that the endpoint works regardless of repair status.
     */
    public function test_can_view_repair_requests_with_different_statuses(): void
    {
        // Test with different repair statuses
        $statuses = [
            RepairStatus::PENDING,
            RepairStatus::IN_PROGRESS,
            RepairStatus::WAITING_PARTS,
            RepairStatus::COMPLETED,
            RepairStatus::DELIVERED,
            RepairStatus::CANCELED,
        ];

        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        foreach ($statuses as $status) {
            // Given: A repair request with the current status
            $repairRequest = RepairRequest::factory()->create([
                'repair_status' => $status->value,
                'customer_name' => "Customer with {$status->value} status",
            ]);

            // When: Admin views the repair request
            $response = $this->apiAs($admin, 'GET', route('repair-request.show', ['repairRequest' => $repairRequest->receipt_number]));

            // Then: The request should succeed regardless of status
            $response->assertStatus(200);
            
            $responseData = $response->json();
            $repairRequestData = $responseData['data']['repairRequest'];
            
            $this->assertEquals($status->value, $repairRequestData['repair_status'], "Failed to view repair request with status: {$status->value}");
            $this->assertEquals("Customer with {$status->value} status", $repairRequestData['customer_name']);
        }
    }

    /**
     * Test that the response contains the correct success message.
     * This ensures that the API returns the expected localized message.
     */
    public function test_response_contains_correct_success_message(): void
    {
        // Given: A repair request
        $repairRequest = RepairRequest::factory()->create([
            'repair_status' => RepairStatus::PENDING->value,
        ]);

        // When: An admin user views the repair request
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'GET', route('repair-request.show', ['repairRequest' => $repairRequest->receipt_number]));

        // Then: The response should contain the correct success message
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'message', 'data']);
        $response->assertJsonFragment([
            'status' => 200,
            'message' => __('messages.common.retrieved', ['item' => __('messages.entities.repair_request.singular')])
        ]);
    }

    /**
     * Test that all repair request fields are properly returned.
     * This ensures that the RepairRequestResource includes all expected fields.
     */
    public function test_all_repair_request_fields_are_returned(): void
    {
        // Given: A repair request with all fields populated
        $repairRequest = RepairRequest::factory()->create([
            'repair_status' => RepairStatus::IN_PROGRESS->value,
            'customer_name' => 'Jane Smith',
            'customer_phone' => '1234567890',
            'customer_email' => 'jane.smith@example.com',
            'article_name' => 'MacBook Pro',
            'article_type' => 'Laptop',
            'article_brand' => 'Apple',
            'article_model' => 'MacBook Pro 13"',
            'article_serialnumber' => 'ABC123456789',
            'article_accesories' => 'Charger, Mouse',
            'article_problem' => 'Screen flickering',
            'repair_details' => 'Needs new display',
            'repair_price' => 299.99,
            'received_at' => now(),
            'repaired_at' => now()->addDays(3),
        ]);

        // When: An admin user views the repair request
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'GET', route('repair-request.show', ['repairRequest' => $repairRequest->receipt_number]));

        // Then: All fields should be present and correct
        $response->assertStatus(200);
        $responseData = $response->json();
        $repairRequestData = $responseData['data']['repairRequest'];

        $expectedFields = [
            'id', 'receipt_number', 'customer_name', 'customer_phone', 'customer_email',
            'article_name', 'article_type', 'article_brand', 'article_model',
            'article_serialnumber', 'article_accesories', 'article_problem',
            'repair_status', 'repair_details', 'repair_price', 'received_at',
            'repaired_at', 'images'
        ];

        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $repairRequestData, "Field {$field} is missing from response");
        }

        // Verify specific field values
        $this->assertEquals($repairRequest->customer_name, $repairRequestData['customer_name']);
        $this->assertEquals($repairRequest->customer_email, $repairRequestData['customer_email']);
        $this->assertEquals($repairRequest->article_name, $repairRequestData['article_name']);
        $this->assertEquals($repairRequest->repair_status->value, $repairRequestData['repair_status']);
        $this->assertEquals($repairRequest->repair_price, $repairRequestData['repair_price']);
    }

    /**
     * Test that receipt number validation works correctly.
     * This ensures that invalid receipt number formats return appropriate errors.
     */
    public function test_receipt_number_validation_works(): void
    {        // Given: Various invalid receipt number formats
        $invalidReceiptNumbers = [
            'invalid-format',
            '123',
            'REC',
            'special-chars-@#$%',
        ];

        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        foreach ($invalidReceiptNumbers as $invalidReceiptNumber) {
            // When: Admin tries to view repair request with invalid receipt number
            $response = $this->apiAs($admin, 'GET', route('repair-request.show', ['repairRequest' => $invalidReceiptNumber]));            // Then: The request should fail with 404 status
            $response->assertStatus(404);
        }
    }

    /**
     * Test that the endpoint works with different receipt number formats.
     * This ensures that valid receipt numbers in different formats work correctly.
     */
    public function test_endpoint_works_with_different_receipt_number_formats(): void
    {
        // Given: Repair requests with different receipt number formats
        $repairRequests = [];
        
        // Create multiple repair requests to test different formats
        for ($i = 1; $i <= 3; $i++) {
            $repairRequests[] = RepairRequest::factory()->create([
                'repair_status' => RepairStatus::PENDING->value,
                'customer_name' => "Customer {$i}",
            ]);
        }

        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        foreach ($repairRequests as $repairRequest) {
            // When: Admin views each repair request by its receipt number
            $response = $this->apiAs($admin, 'GET', route('repair-request.show', ['repairRequest' => $repairRequest->receipt_number]));

            // Then: Each request should succeed
            $response->assertStatus(200);
            
            $responseData = $response->json();
            $repairRequestData = $responseData['data']['repairRequest'];
            
            $this->assertEquals($repairRequest->receipt_number, $repairRequestData['receipt_number']);
            $this->assertEquals($repairRequest->customer_name, $repairRequestData['customer_name']);
        }
    }
}