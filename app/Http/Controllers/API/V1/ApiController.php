<?php

// Base API Controller
namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

abstract class ApiController extends Controller
{
    protected function successResponse($data = null, string $message = 'Success', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    protected function errorResponse(string $message = 'Error', int $status = 400, array $errors = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $status);
    }

    protected function resourceResponse(JsonResource $resource, string $message = 'Success', int $status = 200): JsonResponse
    {
        return $this->successResponse($resource, $message, $status);
    }

    protected function collectionResponse(ResourceCollection $collection, string $message = 'Success'): JsonResponse
    {
        return $this->successResponse($collection, $message);
    }
}