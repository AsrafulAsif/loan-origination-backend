<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponseTrait
{
    protected function successResponse($data = null, string $message = 'Operation successful', int $statusCode = 200): JsonResponse
    {

        $response = [
            'status_code' => $statusCode,
            'message' => $message,
            'timestamp' => now()->toIso8601String(),
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }


    protected function paginatedResponse(LengthAwarePaginator $paginator, string $message = 'Operation successful', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'status_code' => $statusCode,
            'message' => $message,
            'timestamp' => now()->toIso8601String(),
            'data' => $paginator->items(),
            'pagination' => [
                'current_page'  => $paginator->currentPage(),
                'per_page'      => $paginator->perPage(),
                'total'         => $paginator->total(),
                'last_page'     => $paginator->lastPage(),
                'from'          => $paginator->firstItem(),
                'to'            => $paginator->lastItem(),
                'has_more_pages'=> $paginator->hasMorePages(),
            ],
        ], $statusCode);
    }


    protected function errorResponse(string $message = 'Operation failed', int $statusCode = 400, ?array $errors = null): JsonResponse
    {
        $response = [
            'status_code' => $statusCode,
            'message' => $message,
            'timestamp' => now()->toIso8601String(),
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }
}
