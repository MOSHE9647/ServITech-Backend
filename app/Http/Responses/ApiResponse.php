<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiResponse
{
    /**
     * Generate a successful JSON response.
     * 
     * @param array $data
     * @param int $status
     * @param string $message
     * @return JsonResponse|mixed
     */
    public static function success(array $data = [], int $status = Response::HTTP_OK, string $message = null): JsonResponse
    {
        if ($message === null) {
            $message = __('http-statuses.200');
        }
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => empty($data) ? (object)[] : $data,
        ], $status);
    }

    /**
     * Generate an error JSON response.
     * @param string $message
     * @param array $errors
     * @param int $status
     * @return JsonResponse|mixed
     */
    public static function error(string $message = 'Error', array $errors = [], int $status = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'errors' => empty($errors) ? (object)[] : $errors,
        ], $status);
    }
}