<?php

namespace App\Http\Controllers\Model;

use App\Http\Controllers\Controller;
use App\Models\SupportRequest;
use Illuminate\Http\Request;
use App\Http\Responses\ApiResponse;

class SupportRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $supportRequest = SupportRequest::orderBy('id', 'desc')->get();
        return ApiResponse::success(
        data: compact( 'supportRequest' ),
        status: 200
    );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
      $data = $request->validate([
        'date' => 'required|date',
        'location' => 'required|string',
        'detail' => 'required|string',
      ]);
        //when creating a new support request, the user_id is the authenticated user
        $data['user_id'] = auth()->guard('api')->user()->id;
        $supportRequest = SupportRequest::create($data);
        return ApiResponse::success(
            data: compact('supportRequest'),
            status: 200
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(SupportRequest $supportRequest)
    {
        return ApiResponse::success(
            data: compact('supportRequest'),
            status: 200

        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SupportRequest $supportRequest)
    {
        return ApiResponse::success(
            data: compact('supportRequest'),
            status: 200
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SupportRequest $supportRequest)
    {
        return ApiResponse::success(
            data: compact('supportRequest'),
            status: 200
        );
    }
}
