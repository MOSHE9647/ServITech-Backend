<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiResponse
{
    public static function success(array $data = [], int $status = Response::HTTP_OK, string $message = 'OK'): JsonResponse
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    public static function error(string $message = 'Error', array $errors = [], int $status = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}