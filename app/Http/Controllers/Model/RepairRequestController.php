<?php

namespace App\Http\Controllers\Model;

use App\Http\Controllers\Controller;
use App\Http\Requests\RepairRequest\CreateRepairRequest;
use App\Http\Requests\RepairRequest\UpdateRepairRequest;
use App\Http\Responses\ApiResponse;
use App\Models\RepairRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class RepairRequestController for managing repair requests.
 *
 * @OA\Schema(
 *     schema="CreateRepairRequest",
 *     type="object",
 *     required={"customer_name", "customer_phone", "customer_email", "article_name", "article_type", "article_brand", "article_model", "article_problem", "repair_status", "received_at"},
 *     @OA\Property(property="customer_name", type="string", example="John Doe"),
 *     @OA\Property(property="customer_phone", type="string", example="123456789"),
 *     @OA\Property(property="customer_email", type="string", example="johndoe@example.com"),
 *     @OA\Property(property="article_name", type="string", example="Laptop"),
 *     @OA\Property(property="article_type", type="string", example="Electronics"),
 *     @OA\Property(property="article_brand", type="string", example="Dell"),
 *     @OA\Property(property="article_model", type="string", example="Inspiron 15"),
 *     @OA\Property(property="article_serialnumber", type="string", example="SN123456"),
 *     @OA\Property(property="article_accesories", type="string", example="Charger, Bag"),
 *     @OA\Property(property="article_problem", type="string", example="Screen not working"),
 *     @OA\Property(property="repair_status", type="string", example="Pending"),
 *     @OA\Property(property="repair_details", type="string", example="Screen replacement required"),
 *     @OA\Property(property="repair_price", type="number", format="float", example=150.75),
 *     @OA\Property(property="received_at", type="string", format="date", example="2025-03-29"),
 *     @OA\Property(property="repaired_at", type="string", format="date", example="2025-04-05")
 * )
 *
 * @OA\Schema(
 *     schema="UpdateRepairRequest",
 *     type="object",
 *     required={"repair_status"},
 *     @OA\Property(property="article_serialnumber", type="string", example="SN123456"),
 *     @OA\Property(property="article_accesories", type="string", example="Charger, Bag"),
 *     @OA\Property(property="repair_status", type="string", example="Completed"),
 *     @OA\Property(property="repair_details", type="string", example="Screen replaced successfully"),
 *     @OA\Property(property="repair_price", type="number", format="float", example=150.75),
 *     @OA\Property(property="repaired_at", type="string", format="date", example="2025-04-05")
 * )
 */
class RepairRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/{version}/repair-requests",
     *     summary="Get all repair requests",
     *     tags={"Repair Requests"},
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
     *         description="List of repair requests retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Repair requests retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="repairRequests", type="array",
     *                     @OA\Items(ref="#/components/schemas/CreateRepairRequest")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        // Fetch all repair requests from the database
        // and order them by ID in descending order
        $repairRequests = RepairRequest::orderBy("id", "desc")->get();

        // Return a successful response with the list of repair requests
        return ApiResponse::success(
            message: __('messages.repair_request.retrieved_list'),
            data: compact("repairRequests")
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *     path="/api/{version}/repair-requests",
     *     summary="Create a new repair request",
     *     tags={"Repair Requests"},
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
     *         @OA\JsonContent(ref="#/components/schemas/CreateRepairRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Repair request created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=201),
     *             @OA\Property(property="message", type="string", example="Repair request created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="repairRequest", ref="#/components/schemas/CreateRepairRequest")
     *             )
     *         )
     *     )
     * )
     */
    public function store(CreateRepairRequest $request): JsonResponse
    {
        // Validate the request data
        $data = $request->validated();

        // Create a new repair request in the database
        $repairRequest = RepairRequest::create($data);

        // Return a successful response with the created repair request
        return ApiResponse::success(
            data: compact('repairRequest'),
            message: __('messages.repair_request.created')
        );
    }

    /**
     * Display the specified resource.
     *
     * @OA\Get(
     *     path="/api/{version}/repair-requests/{receipt_number}",
     *     summary="Get a specific repair request",
     *     tags={"Repair Requests"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="version",
     *         in="path",
     *         required=true,
     *         description="API version",
     *         @OA\Schema(type="string", example="v1")
     *     ),
     *     @OA\Parameter(
     *         name="receipt_number",
     *         in="path",
     *         required=true,
     *         description="Receipt number of the repair request",
     *         @OA\Schema(type="string", example="RR-000000000001")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Repair request retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Repair request retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="repairRequest", ref="#/components/schemas/CreateRepairRequest")
     *             )
     *         )
     *     )
     * )
     */
    public function show(RepairRequest $repairRequest): JsonResponse
    {
        // Check if the repair request exists
        if (!$repairRequest->exists()) {
            return ApiResponse::error(
                message: __('messages.not_found', ['attribute' => RepairRequest::class]),
                status: Response::HTTP_BAD_REQUEST
            );
        }
        
        // Return a successful response with the repair request details
        return ApiResponse::success(
            data: compact('repairRequest'),
            message: __('messages.repair_request.retrieved')
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Put(
     *     path="/api/{version}/repair-requests/{receipt_number}",
     *     summary="Update a repair request",
     *     tags={"Repair Requests"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="version",
     *         in="path",
     *         required=true,
     *         description="API version",
     *         @OA\Schema(type="string", example="v1")
     *     ),
     *     @OA\Parameter(
     *         name="receipt_number",
     *         in="path",
     *         required=true,
     *         description="Receipt number of the repair request",
     *         @OA\Schema(type="string", example="RR-000000000001")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UpdateRepairRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Repair request updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Repair request updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="repairRequest", ref="#/components/schemas/UpdateRepairRequest")
     *             )
     *         )
     *     )
     * )
     */
    public function update(UpdateRepairRequest $request, RepairRequest $repairRequest): JsonResponse
    {
        // Check if the repair request exists
        // If not, return an error response
        if (! $repairRequest->exists()) {
            return ApiResponse::error(
                message: __('messages.not_found', ['attribute' => RepairRequest::class]),
                status: Response::HTTP_BAD_REQUEST
            );
        }

        // Validate the request data
        $data = $request->validated();

        // Update the repair request in the database
        $repairRequest->update($data);

        // Return a successful response with the updated repair request
        return ApiResponse::success(
            message: __('messages.repair_request.updated'),
            data: compact('repairRequest'),
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @OA\Delete(
     *     path="/api/{version}/repair-requests/{receipt_number}",
     *     summary="Delete a repair request",
     *     tags={"Repair Requests"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="version",
     *         in="path",
     *         required=true,
     *         description="API version",
     *         @OA\Schema(type="string", example="v1")
     *     ),
     *     @OA\Parameter(
     *         name="receipt_number",
     *         in="path",
     *         required=true,
     *         description="Receipt number of the repair request",
     *         @OA\Schema(type="string", example="RR-000000000001")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Repair request deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Repair request deleted successfully")
     *         )
     *     )
     * )
     */
    public function destroy(RepairRequest $repairRequest): JsonResponse
    {
        // Check if the repair request exists
        // If not, return an error response
        if (!$repairRequest->exists()) {
            return ApiResponse::error(
                message: __('messages.not_found', ['attribute' => RepairRequest::class]),
                status: Response::HTTP_BAD_REQUEST
            );
        }

        // Delete the repair request from the database
        // and return a successful response
        $repairRequest->delete();
        return ApiResponse::success(message: __('messages.repair_request.deleted'));
    }
}