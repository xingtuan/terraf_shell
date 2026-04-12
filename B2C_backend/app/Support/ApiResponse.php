<?php

namespace App\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use JsonSerializable;

trait ApiResponse
{
    protected function successResponse(
        mixed $data = null,
        ?string $message = null,
        int $status = 200
    ): JsonResponse {
        return self::success($data, $message, $status);
    }

    protected function errorResponse(
        string $message,
        array $errors = [],
        int $status = 422
    ): JsonResponse {
        return self::error($message, $errors, $status);
    }

    protected function paginatedResponse(
        LengthAwarePaginator $paginator,
        mixed $data,
        ?string $message = null,
        int $status = 200
    ): JsonResponse {
        return self::paginated($paginator, $data, $message, $status);
    }

    public static function success(
        mixed $data = null,
        ?string $message = null,
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => self::resolveData($data),
        ], $status);
    }

    public static function error(
        string $message,
        array $errors = [],
        int $status = 422
    ): JsonResponse {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }

    public static function paginated(
        LengthAwarePaginator $paginator,
        mixed $data,
        ?string $message = null,
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => self::resolveData($data),
            'meta' => PaginatesResources::meta($paginator),
        ], $status);
    }

    protected static function resolveData(mixed $data): mixed
    {
        if (is_array($data)) {
            return array_map(fn (mixed $item): mixed => self::resolveData($item), $data);
        }

        if ($data instanceof JsonResource) {
            return $data->resolve(app(Request::class));
        }

        if ($data instanceof Arrayable) {
            return $data->toArray();
        }

        if ($data instanceof JsonSerializable) {
            return $data->jsonSerialize();
        }

        return $data;
    }
}
