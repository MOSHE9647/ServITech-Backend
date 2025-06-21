<?php

namespace Tests\Feature\RepairRequest;

use App\Enums\RepairStatus;
use App\Enums\UserRoles;
use App\Models\RepairRequest;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepairRequestIndexTest extends TestCase
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
     * Test that an authenticated admin user can retrieve all repair requests.
     * This ensures that only admin users can access the repair requests index.
     */
    public function test_an_authenticated_admin_user_can_retrieve_all_repair_requests(): void
    {
        // Given: Multiple repair requests in the database
        $repairRequests = RepairRequest::factory()->count(5)->create([
            'repair_status' => RepairStatus::PENDING->value,
        ]);

        // When: An admin user attempts to retrieve all repair requests
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'GET', route('repair-request.index'));

        // Then: The request should succeed and return all repair requests
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'repairRequests' => [
                    '*' => [
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
            ]
        ]);

        $response->assertJsonFragment([
            'message' => __('messages.common.retrieved_all', ['items' => __('messages.entities.repair_request.plural')])
        ]);

        // Verify that all repair requests are returned
        $responseData = $response->json();
        $this->assertCount(5, $responseData['data']['repairRequests']);
    }

    /**
     * Test that an authenticated non-admin user cannot retrieve repair requests.
     * This ensures that only admin users can access the repair requests index.
     */
    public function test_an_authenticated_non_admin_user_cannot_retrieve_repair_requests(): void
    {
        // Given: Some repair requests in the database
        RepairRequest::factory()->count(3)->create([
            'repair_status' => RepairStatus::PENDING->value,
        ]);

        // When: A non-admin user attempts to retrieve repair requests
        $user = User::role(UserRoles::USER)->first();
        $this->assertNotNull($user, 'User not found');

        $response = $this->apiAs($user, 'GET', route('repair-request.index'));

        // Then: The request should fail with a 403 Forbidden status
        $response->assertStatus(403);
        $response->assertJsonStructure(['status', 'message']);
    }

    /**
     * Test that a non-authenticated user cannot retrieve repair requests.
     * This ensures that only authenticated users can access the repair requests index.
     */
    public function test_a_non_authenticated_user_cannot_retrieve_repair_requests(): void
    {
        // Given: Some repair requests in the database
        RepairRequest::factory()->count(3)->create([
            'repair_status' => RepairStatus::PENDING->value,
        ]);

        // When: A non-authenticated user attempts to retrieve repair requests
        $response = $this->getJson(route('repair-request.index'));

        // Then: The request should fail with a 401 Unauthorized status
        $response->assertStatus(401);
        $response->assertJsonStructure(['status', 'message']);
    }

    /**
     * Test that repair requests are returned in descending order by ID.
     * This ensures that the most recent repair requests appear first.
     */
    public function test_repair_requests_are_returned_in_descending_order_by_id(): void
    {
        // Given: Multiple repair requests created in sequence
        $firstRequest = RepairRequest::factory()->create([
            'repair_status' => RepairStatus::PENDING->value,
            'customer_name' => 'First Customer',
        ]);

        $secondRequest = RepairRequest::factory()->create([
            'repair_status' => RepairStatus::IN_PROGRESS->value,
            'customer_name' => 'Second Customer',
        ]);

        $thirdRequest = RepairRequest::factory()->create([
            'repair_status' => RepairStatus::COMPLETED->value,
            'customer_name' => 'Third Customer',
        ]);

        // When: An admin user retrieves all repair requests
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'GET', route('repair-request.index'));

        // Then: The repair requests should be ordered by ID in descending order
        $response->assertStatus(200);
        $responseData = $response->json();
        $repairRequestsData = $responseData['data']['repairRequests'];

        // The third request (highest ID) should be first
        $this->assertEquals($thirdRequest->id, $repairRequestsData[0]['id']);
        $this->assertEquals('Third Customer', $repairRequestsData[0]['customer_name']);

        // The second request should be second
        $this->assertEquals($secondRequest->id, $repairRequestsData[1]['id']);
        $this->assertEquals('Second Customer', $repairRequestsData[1]['customer_name']);

        // The first request (lowest ID) should be last
        $this->assertEquals($firstRequest->id, $repairRequestsData[2]['id']);
        $this->assertEquals('First Customer', $repairRequestsData[2]['customer_name']);
    }

    /**
     * Test that repair requests include their associated images.
     * This ensures that images are properly loaded and included in the response.
     */
    public function test_repair_requests_include_associated_images(): void
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

        // When: An admin user retrieves all repair requests
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'GET', route('repair-request.index'));

        // Then: The repair request should include its images
        $response->assertStatus(200);
        $responseData = $response->json();
        $repairRequestData = $responseData['data']['repairRequests'][0];

        $this->assertArrayHasKey('images', $repairRequestData);
        $this->assertCount(2, $repairRequestData['images']);
        
        // Verify image structure
        $this->assertArrayHasKey('path', $repairRequestData['images'][0]);
        $this->assertArrayHasKey('title', $repairRequestData['images'][0]);
        $this->assertArrayHasKey('alt', $repairRequestData['images'][0]);
    }

    /**
     * Test that repair requests without images show empty images array.
     * This ensures that repair requests without images are handled correctly.
     */
    public function test_repair_requests_without_images_show_empty_images_array(): void
    {
        // Given: A repair request without images
        $repairRequest = RepairRequest::factory()->create([
            'repair_status' => RepairStatus::PENDING->value,
        ]);

        $this->assertEquals(0, $repairRequest->images()->count(), 'Repair request should have no images');

        // When: An admin user retrieves all repair requests
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'GET', route('repair-request.index'));

        // Then: The repair request should have an empty images array
        $response->assertStatus(200);
        $responseData = $response->json();
        $repairRequestData = $responseData['data']['repairRequests'][0];

        $this->assertArrayHasKey('images', $repairRequestData);
        $this->assertEmpty($repairRequestData['images']);
    }

    /**
     * Test that the endpoint returns an empty array when no repair requests exist.
     * This ensures that the endpoint handles empty states correctly.
     */
    public function test_returns_empty_array_when_no_repair_requests_exist(): void
    {
        // Given: No repair requests in the database
        $this->assertEquals(0, RepairRequest::count(), 'Database should be empty');

        // When: An admin user attempts to retrieve repair requests
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'GET', route('repair-request.index'));

        // Then: The request should succeed with an empty array
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => ['repairRequests']
        ]);

        $responseData = $response->json();
        $this->assertEmpty($responseData['data']['repairRequests']);
    }

    /**
     * Test that soft-deleted repair requests are not included in the index.
     * This ensures that deleted repair requests don't appear in the listing.
     */
    public function test_soft_deleted_repair_requests_are_not_included(): void
    {
        // Given: Active and soft-deleted repair requests
        $activeRequest = RepairRequest::factory()->create([
            'repair_status' => RepairStatus::PENDING->value,
            'customer_name' => 'Active Customer',
        ]);

        $deletedRequest = RepairRequest::factory()->create([
            'repair_status' => RepairStatus::CANCELED->value,
            'customer_name' => 'Deleted Customer',
        ]);
        $deletedRequest->delete(); // Soft delete

        $this->assertSoftDeleted('repair_requests', ['id' => $deletedRequest->id]);

        // When: An admin user retrieves all repair requests
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'GET', route('repair-request.index'));

        // Then: Only the active repair request should be returned
        $response->assertStatus(200);
        $responseData = $response->json();
        $repairRequestsData = $responseData['data']['repairRequests'];

        $this->assertCount(1, $repairRequestsData);
        $this->assertEquals($activeRequest->id, $repairRequestsData[0]['id']);
        $this->assertEquals('Active Customer', $repairRequestsData[0]['customer_name']);
    }

    /**
     * Test that repair requests with different statuses are all included.
     * This ensures that the endpoint returns repair requests regardless of their status.
     */
    public function test_repair_requests_with_different_statuses_are_included(): void
    {
        // Given: Repair requests with different statuses
        $statuses = [
            RepairStatus::PENDING,
            RepairStatus::IN_PROGRESS,
            RepairStatus::WAITING_PARTS,
            RepairStatus::COMPLETED,
            RepairStatus::DELIVERED,
            RepairStatus::CANCELED,
        ];

        $createdRequests = [];
        foreach ($statuses as $status) {
            $createdRequests[] = RepairRequest::factory()->create([
                'repair_status' => $status->value,
                'customer_name' => "Customer with {$status->value} status",
            ]);
        }

        // When: An admin user retrieves all repair requests
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'GET', route('repair-request.index'));

        // Then: All repair requests should be returned regardless of status
        $response->assertStatus(200);
        $responseData = $response->json();
        $repairRequestsData = $responseData['data']['repairRequests'];

        $this->assertCount(6, $repairRequestsData);

        // Verify all statuses are present
        $returnedStatuses = array_column($repairRequestsData, 'repair_status');
        foreach ($statuses as $status) {
            $this->assertContains($status->value, $returnedStatuses);
        }
    }

    /**
     * Test that the response contains the correct success message.
     * This ensures that the API returns the expected localized message.
     */
    public function test_response_contains_correct_success_message(): void
    {
        // Given: A repair request in the database
        RepairRequest::factory()->create([
            'repair_status' => RepairStatus::PENDING->value,
        ]);

        // When: An admin user retrieves all repair requests
        $admin = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($admin, 'Admin user not found');

        $response = $this->apiAs($admin, 'GET', route('repair-request.index'));

        // Then: The response should contain the correct success message
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'message', 'data']);
        $response->assertJsonFragment([
            'status' => 200,
            'message' => __('messages.common.retrieved_all', ['items' => __('messages.entities.repair_request.plural')])
        ]);
    }
}