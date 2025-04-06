<?php

namespace Tests\Feature\RepairRequestTests;

use App\Enums\RepairStatus;
use App\Enums\UserRoles;
use App\Models\RepairRequest;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateRepairRequestTest extends TestCase
{
    use RefreshDatabase; // Reset the database after each test

    /**
     * Set up the test environment.
     * This method seeds the database before each test.
     */
    protected function setUp(): void
    {
        parent::setUp(); // Call the parent setUp method
        $this->seed(class: DatabaseSeeder::class); // Seed the database
    }
    
    /**
     * Test that a repair request is created with a unique receipt number.
     * This ensures that each repair request has a unique receipt number following the format "RR-XXXXXXXXXXXX".
     */
    public function test_repair_request_is_created_with_unique_receipt_number()
    {
        // Given: A repair request created via the factory
        $repairRequest = RepairRequest::factory()->create();

        // Then: The receipt number should not be null and should match the expected format
        $this->assertNotNull($repairRequest->receipt_number);
        $this->assertMatchesRegularExpression('/^RR-\d{12}$/', $repairRequest->receipt_number);
    }

    /**
     * Test that an authenticated admin user can create repair requests.
     * This ensures that only admin users can create repair requests successfully.
     */
    public function test_an_authenticated_admin_user_can_create_repair_requests()
    {
        // Given: A valid repair request payload
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should succeed, and the repair request should be stored in the database
        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'status', 'message']);
        $response->assertJsonFragment([
            'status' => 200,
            'message' => __('messages.repair_request.created'),
        ]);

        $this->assertDatabaseHas('repair_requests', $repairRequest);
    }

    /**
     * Test that a non-authenticated admin user cannot create repair requests.
     * This ensures that only admin users can create repair requests.
     */
    public function test_a_non_authenticated_admin_user_can_not_create_repair_requests()
    {
        // Given: A valid repair request payload
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // Create a non-admin user
        $user = User::factory()->create(); // Create a regular user
        $user->assignRole(UserRoles::USER); // Assign the "USER" role
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        // When: The non-admin user attempts to create a repair request
        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should fail with a 403 Forbidden status
        $response->assertStatus(403);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 403,
            'message' => __('User does not have the right roles.'),
        ]);
    }

    /**
     * Test that the customer name field is required.
     * This ensures that missing the customer name field returns a 422 status with validation errors.
     */
    public function test_customer_name_must_be_required()
    {
        // Given: A repair request payload with a missing customer name
        $repairRequest = [
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'customer_name' => [
                    __('validation.required', [
                        'attribute' => __('validation.attributes.customer_name')
                    ])
                ],
            ],
        ]);
    }

    /**
     * Test that the customer name must be a string.
     * This ensures that non-string values for the customer name field return a 422 status with validation errors.
     */
    public function test_customer_name_must_be_a_string()
    {
        // Given: A repair request payload with a non-string customer name
        $repairRequest = [
            "customer_name"         => 12345678,
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'customer_name' => [
                    __('validation.string', [
                        'attribute' => __('validation.attributes.customer_name')
                    ])
                ],
            ],
        ]);
    }

    /**
     * Test that the customer name must have at least 3 characters.
     * This ensures that short customer names return a 422 status with validation errors.
     */
    public function test_customer_name_must_have_at_least_3_characters()
    {
        // Given: A repair request payload with a customer name that has less than 3 characters
        $repairRequest = [
            "customer_name"         => "Ej",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'customer_name' => [
                    __('validation.min.string', [
                        'attribute' => __('validation.attributes.customer_name'),
                        'min' => 3,
                    ])
                ],
            ],
        ]);
    }

    /**
     * Test that the customer phone field is required.
     * This ensures that missing the customer phone field returns a 422 status with validation errors.
     */
    public function test_customer_phone_must_be_required()
    {
        // Given: A repair request payload with a missing customer phone
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'customer_phone' => [
                    __('validation.required', [
                        'attribute' => __('validation.attributes.phone')
                    ])
                ],
            ],
        ]);
    }

    /**
     * Test that the customer phone must be a string.
     * This ensures that non-string values for the customer phone field return a 422 status with validation errors.
     */
    public function test_customer_phone_must_be_a_string()
    {
        // Given: A repair request payload with a non-string customer phone
        $repairRequest = [
            'customer_name'         => "Juan Pérez",
            'customer_phone'        => 12345678,
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'customer_phone' => [
                    __('validation.string', [
                        'attribute' => __('validation.attributes.phone')
                    ])
                ],
            ],
        ]);
    }

    /**
     * Test that the customer phone must have at least 8 characters.
     * This ensures that short customer phone numbers return a 422 status with validation errors.
     */
    public function test_customer_phone_must_have_at_least_8_characters()
    {
        // Given: A repair request payload with a customer phone that has less than 8 characters
        $repairRequest = [
            'customer_name'         => "Juan Pérez",
            'customer_phone'        => "1234567",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'customer_phone' => [
                    __('validation.min.string', [
                        'attribute' => __('validation.attributes.phone'),
                        'min' => 8,
                    ])
                ],
            ],
        ]);
    }

    /**
     * Test that the customer email field is required.
     * This ensures that missing the customer email field returns a 422 status with validation errors.
     */
    public function test_customer_email_must_be_required()
    {
        // Given: A repair request payload with a missing customer email
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'customer_email' => [
                    __('validation.required', [
                        'attribute' => __('validation.attributes.email')
                    ])
                ],
            ],
        ]);
    }

    /**
     * Test that the customer email must be a valid email address.
     * This ensures that invalid email formats return a 422 status with validation errors.
     */
    public function test_customer_email_must_be_a_valid_email()
    {
        // Given: A repair request payload with an invalid customer email
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'customer_email' => [
                    __('validation.email', [
                        'attribute' => __('validation.attributes.email')
                    ])
                ],
            ],
        ]);
    }

    /**
     * Test that the article name field is required.
     * This ensures that missing the article name field returns a 422 status with validation errors.
     */
    public function test_article_name_must_be_required()
    {
        // Given: A repair request payload with a missing article name
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_name' => [
                    __('validation.required', [
                        'attribute' => __('validation.attributes.article_name')
                    ])
                ],
            ],
        ]);
    }

    /**
     * Test that the article name must be a string.
     * This ensures that non-string values for the article name field return a 422 status with validation errors.
     */
    public function test_article_name_must_be_a_string()
    {
        // Given: A repair request payload with a non-string article name
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => 12345678,
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_name' => [
                    __('validation.string', [
                        'attribute' => __('validation.attributes.article_name')
                    ])
                ],
            ],
        ]);
    }

    /**
     * Test that the article name must have at least 3 characters.
     * This ensures that short article names return a 422 status with validation errors.
     */
    public function test_article_name_must_have_at_least_3_characters()
    {
        // Given: A repair request payload with an article name that has less than 3 characters
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Ej",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_name' => [
                    __('validation.min.string', [
                        'attribute' => __('validation.attributes.article_name'),
                        'min' => 3,
                    ])
                ],
            ],
        ]);
    }

    /**
     * Test that the article type field is required.
     * This ensures that missing the article type field returns a 422 status with validation errors.
     */
    public function test_article_type_must_be_required()
    {
        // Given: A repair request payload with a missing article type
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_type' => [
                    __('validation.required', [
                        'attribute' => __('validation.attributes.article_type')
                    ])
                ],
            ],
        ]);
    }

    /**
     * Test that the article type must be a string.
     * This ensures that non-string values for the article type field return a 422 status with validation errors.
     */
    public function test_article_type_must_be_a_string()
    {
        // Given: A repair request payload with a non-string article type
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => 12345678,
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_type' => [
                    __('validation.string', [
                        'attribute' => __('validation.attributes.article_type')
                    ])
                ],
            ],
        ]);
    }

    /**
     * Test that the article type must have at least 3 characters.
     * This ensures that short article types return a 422 status with validation errors.
     */
    public function test_article_type_must_have_at_least_3_characters()
    {
        // Given: A repair request payload with an article type that has less than 3 characters
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Ej",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_type' => [
                    __('validation.min.string', [
                        'attribute' => __('validation.attributes.article_type'),
                        'min' => 3,
                    ])
                ],
            ],
        ]);
    }

    /**
     * Test that the article brand field is required.
     * This ensures that missing the article brand field returns a 422 status with validation errors.
     */
    public function test_article_brand_must_be_required()
    {
        // Given: A repair request payload with a missing article brand
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_brand' => [
                    __('validation.required', [
                        'attribute' => __('validation.attributes.article_brand')
                    ])
                ],
            ],
        ]);
    }

    /**
     * Test that the article brand must have at least 2 characters.
     * This ensures that short article brands return a 422 status with validation errors.
     */
    public function test_article_brand_must_have_at_least_2_characters()
    {
        // Given: A repair request payload with an article brand that has less than 2 characters
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "A",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_brand' => [
                    __('validation.min.string', [
                        'attribute' => __('validation.attributes.article_brand'),
                        'min' => 2,
                    ])
                ],
            ],
        ]);
    }

    /**
     * Test that the article model field is required.
     * This ensures that missing the article model field returns a 422 status with validation errors.
     */
    public function test_article_model_must_be_required()
    {
        // Given: A repair request payload with a missing article model
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_model' => [
                    __('validation.required', [
                        'attribute' => __('validation.attributes.article_model')
                    ])
                ],
            ],
        ]);
    }

    /**
     * Test that the article model must be a string.
     * This ensures that non-string values for the article model field return a 422 status with validation errors.
     */
    public function test_article_model_must_be_a_string()
    {
        // Given: A repair request payload with a non-string article model
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => 12345678,
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_model' => [
                    __('validation.string', [
                        'attribute' => __('validation.attributes.article_model')
                    ])
                ],
            ],
        ]);
    }

    /**
     * Test that the article model must have at least 2 characters.
     * This ensures that short article models return a 422 status with validation errors.
     */
    public function test_article_model_must_have_at_least_2_characters()
    {
        // Given: A repair request payload with an article model that has less than 2 characters
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "A",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_model' => [
                    __('validation.min.string', [
                        'attribute' => __('validation.attributes.article_model'),
                        'min' => 2,
                    ])
                ],
            ],
        ]);
    }

    /**
     * Test that the article serial number can be nullable.
     * This ensures that the article serial number field is optional.
     */
    public function test_article_serialnumber_can_be_nullable()
    {
        // Given: A repair request payload with a missing article serial number
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => null, // Nullable field
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should succeed with a 200 OK status
        $response->assertStatus(200);
        $this->assertDatabaseHas('repair_requests', [
            "article_serialnumber" => null
        ]);
    }

    /**
     * Test that the article serial number must be a string.
     * This ensures that non-string values for the article serial number field return a 422 status with validation errors.
     */
    public function test_article_serialnumber_must_be_a_string()
    {
        // Given: A repair request payload with a non-string article serial number
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => 123456, // Invalid type
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_serialnumber' => [
                    __('validation.string', [
                        'attribute' => __('validation.attributes.serialnumber')
                    ])
                ],
            ],
        ]);
    }

    /**
     * Test that the article serial number must have at least 6 characters.
     * This ensures that short article serial numbers return a 422 status with validation errors.
     */
    public function test_article_serialnumber_must_have_at_least_6_characters()
    {
        // Given: A repair request payload with an article serial number that has less than 6 characters
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "12345", // Too short
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_serialnumber' => [
                    __('validation.min.string', [
                        'attribute' => __('validation.attributes.serialnumber'),
                        'min' => 6,
                    ])
                ],
            ],
        ]);
    }

    /**
     * Test that the article accessories can be nullable.
     * This ensures that the article accessories field is optional.
     */
    public function test_article_accesories_can_be_nullable()
    {
        // Given: A repair request payload with a missing article accessories
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => null, // Nullable field
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should succeed with a 200 OK status
        $response->assertStatus(200);
        $this->assertDatabaseHas('repair_requests', [
            "article_accesories" => null
        ]);
    }

    /**
     * Test that the article accessories must be a string.
     * This ensures that non-string values for the article accessories field return a 422 status with validation errors.
     */
    public function test_article_accesories_must_be_a_string()
    {
        // Given: A repair request payload with a non-string article accessories
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => 12345, // Invalid type
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_accesories' => [
                    __('validation.string', [
                        'attribute' => __('validation.attributes.accesories')
                    ])
                ],
            ],
        ]);
    }

    /**
     * Test that the article accessories must have at least 3 characters.
     * This ensures that short article accessories return a 422 status with validation errors.
     */
    public function test_article_accesories_must_have_at_least_3_characters()
    {
        // Given: A repair request payload with article accessories that have less than 3 characters
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "AB", // Too short
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_accesories' => [
                    __('validation.min.string', [
                        'attribute' => __('validation.attributes.accesories'),
                        'min' => 3,
                    ])
                ],
            ],
        ]);
    }

    /**
     * Test that the article problem field is required.
     * This ensures that missing the article problem field returns a 422 status with validation errors.
     */
    public function test_article_problem_must_be_required()
    {
        // Given: A repair request payload with a missing article problem
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_problem' => [
                    __('validation.required', [
                        'attribute' => __('validation.attributes.article_problem')
                    ])
                ],
            ],
        ]);
    }

    /**
     * Test that the article problem must be a string.
     * This ensures that non-string values for the article problem field return a 422 status with validation errors.
     * This test is similar to the one above but focuses on the type validation.
     */
    public function test_article_problem_must_be_a_string()
    {
        // Given: A repair request payload with a non-string article problem
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => 12345, // Invalid type
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_problem' => [
                    __('validation.string', [
                        'attribute' => __('validation.attributes.article_problem')
                    ])
                ],
            ],
        ]);
    }

    /**
     * Test that the article problem must have at least 3 characters.
     * This ensures that short article problems return a 422 status with validation errors.
     */
    public function test_article_problem_must_have_at_least_3_characters()
    {
        // Given: A repair request payload with an article problem that has less than 3 characters
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "AB", // Too short
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'article_problem' => [
                    __('validation.min.string', [
                        'attribute' => __('validation.attributes.article_problem'),
                        'min' => 3,
                    ])
                ],
            ],
        ]);
    }

    /**
     * Test that the repair status field is required.
     * This ensures that missing the repair status field returns a 422 status with validation errors.
     */
    public function test_repair_status_must_be_required()
    {
        // Given: A repair request payload with a missing repair status
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'repair_status' => [
                    __('validation.required', [
                        'attribute' => __('validation.attributes.repair_status')
                    ])
                ],
            ],
        ]);
    }

    /**
     * Test that the repair status must be a string.
     * This ensures that non-string values for the repair status field return a 422 status with validation errors.
     */
    public function test_repair_status_must_be_a_string()
    {
        // Given: A repair request payload with a non-string repair status
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => 12345, // Invalid type
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first();
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'repair_status' => [
                    __('validation.string', [
                        'attribute' => __('validation.attributes.repair_status')
                    ]),
                    __('validation.enum', [
                        'attribute' => __('validation.attributes.repair_status'),
                        'values' => implode(', ', array_column(RepairStatus::cases(), 'value'))
                    ])
                ],
            ],
        ]);
    }

    /**
     * Test that the repair status must be a valid enum value.
     * This ensures that invalid enum values for the repair status field return a 422 status with validation errors.
     */
    public function test_repair_status_must_be_a_valid_enum_value()
    {
        // Given: A repair request payload with an invalid repair status
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => "INVALID_STATUS", // Invalid enum value
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'repair_status' => [
                    __('validation.enum', [
                        'attribute' => __('validation.attributes.repair_status'),
                        'values' => implode(', ', array_column(RepairStatus::cases(), 'value'))
                    ])
                ],
            ],
        ]);
    }

    /**
     * Test that the repair details can be nullable.
     * This ensures that the repair details field is optional.
     */
    public function test_repair_details_can_be_nullable()
    {
        // Given: A repair request payload with a missing repair details
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => null, // Nullable field
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should succeed with a 200 OK status
        $response->assertStatus(200);
        $this->assertDatabaseHas('repair_requests', [
            "repair_details" => null
        ]);
    }

    /**
     * Test that the repair details must be a string.
     * This ensures that non-string values for the repair details field return a 422 status with validation errors.
     */
    public function test_repair_details_must_be_a_string()
    {
        // Given: A repair request payload with a non-string repair details
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => 12345, // Invalid type
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'repair_details' => [
                    __('validation.string', [
                        'attribute' => __('validation.attributes.repair_details')
                    ])
                ],
            ],
        ]);
    }

    /**
     * Test that the repair details must have at least 3 characters.
     * This ensures that short repair details return a 422 status with validation errors.
     */
    public function test_repair_details_must_have_at_least_3_characters()
    {
        // Given: A repair request payload with repair details that have less than 3 characters
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "AB", // Too short
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'repair_details' => [
                    __('validation.min.string', [
                        'attribute' => __('validation.attributes.repair_details'),
                        'min' => 3,
                    ])
                ],
            ],
        ]);
    }

    /**
     * Test that the repair price can be nullable.
     * This ensures that the repair price field is optional.
     */
    public function test_repair_price_can_be_nullable()
    {
        // Given: A repair request payload with a missing repair price
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => null, // Nullable field
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should succeed with a 200 OK status
        $response->assertStatus(200);
        $this->assertDatabaseHas('repair_requests', [
            "repair_price" => null
        ]);
    }

    /**
     * Test that the repair price must be numeric.
     * This ensures that non-numeric values for the repair price field return a 422 status with validation errors.
     */
    public function test_repair_price_must_be_numeric()
    {
        // Given: A repair request payload with a non-numeric repair price
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "invalid_price", // Invalid type
            "received_at"           => "2023-10-01",
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'repair_price' => [
                    __('validation.numeric', [
                        'attribute' => __('validation.attributes.repair_price')
                    ])
                ],
            ],
        ]);
    }

    /**
     * Test that the received_at field is required.
     * This ensures that missing the received_at field returns a 422 status with validation errors.
     */
    public function test_received_at_must_be_required()
    {
        // Given: A repair request payload with a missing received_at
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => null, // Missing field
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'received_at' => [
                    __('validation.required', [
                        'attribute' => __('validation.attributes.received_at')
                    ])
                ],
            ],
        ]);
    }

    /**
     * Test that the received_at field must be a valid date.
     * This ensures that invalid date formats for the received_at field return a 422 status with validation errors.
     */
    public function test_received_at_must_be_a_valid_date()
    {
        // Given: A repair request payload with an invalid received_at
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "invalid_date", // Invalid date
            "repaired_at"           => null
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'received_at' => [
                    __('validation.date', [
                        'attribute' => __('validation.attributes.received_at')
                    ])
                ],
            ],
        ]);
    }

    /**
     * Test that the repaired_at field can be nullable.
     * This ensures that the repaired_at field is optional.
     */
    public function test_repaired_at_can_be_nullable()
    {
        // Given: A repair request payload with a missing repaired_at
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => null // Nullable field
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should succeed with a 200 OK status
        $response->assertStatus(200);
        $this->assertDatabaseHas('repair_requests', [
            "repaired_at" => null
        ]);
    }

    /**
     * Test that the repaired_at field must be a valid date.
     * This ensures that invalid date formats for the repaired_at field return a 422 status with validation errors.
     */
    public function test_repaired_at_must_be_a_valid_date()
    {
        // Given: A repair request payload with an invalid repaired_at
        $repairRequest = [
            "customer_name"         => "Juan Pérez",
            "customer_phone"        => "12345678",
            "customer_email"        => "juan.perez@example.com",
            "article_name"          => "Laptop",
            "article_type"          => "Electrónica",
            "article_brand"         => "Dell",
            "article_model"         => "Inspiron 15",
            "article_serialnumber"  => "SN123456",
            "article_accesories"    => "Cargador, funda",
            "article_problem"       => "No enciende",
            "repair_status"         => RepairStatus::PENDING,
            "repair_details"        => "Pendiente de diagnóstico",
            "repair_price"          => "1500.50",
            "received_at"           => "2023-10-01",
            "repaired_at"           => "invalid_date" // Invalid date
        ];

        // When: An admin user attempts to create a repair request
        $user = User::role(UserRoles::ADMIN)->first(); // Get the first admin user
        $this->assertNotNull($user, __('messages.user.not_found')); // Ensure the user exists

        $response = $this->apiAs($user, 'POST', route('repair-request.store'), $repairRequest);

        // Then: The request should fail with a 422 Unprocessable Entity status
        $response->assertStatus(422);
        $response->assertJsonStructure(['status', 'message', 'errors']);
        $response->assertJsonFragment([
            'status' => 422,
            'errors' => [
                'repaired_at' => [
                    __('validation.date', [
                        'attribute' => __('validation.attributes.repaired_at')
                    ])
                ],
            ],
        ]);
    }
}