<?php

namespace Tests\Feature;

use App\Models\RepairRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepairRequestTest extends TestCase {
    use RefreshDatabase;

    public function test_repair_request_is_created_with_unique_receipt_number() {
        $repairRequest = RepairRequest::factory()->create();
        // dd($repairRequest);

        $this->assertNotNull($repairRequest->receipt_number);
        $this->assertMatchesRegularExpression('/^RR-\d{12}$/', $repairRequest->receipt_number);
    }
}