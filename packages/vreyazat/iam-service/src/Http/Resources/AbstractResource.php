<?php

namespace IamApi\Http\Resources;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

class AbstractResource extends JsonResource
{
    public static $wrap;

    /**
     * Errors array for validation errors
     */
    protected array $errors = [];

    /**
     * Create a success response with data
     */
    public static function success($data = null, string $message = null): array
    {
        // Resolve resources to arrays when nested
        if ($data instanceof JsonResource) {
            $data = $data->resolve(request());
        }
        return [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];
    }

    /**
     * Create an error response
     */
    public static function error(string $message, $data = null, int $status = 400): array
    {
        return [
            'success' => false,
            'message' => $message,
            'data' => $data,
            'status' => $status
        ];
    }

    /**
     * @template T of JsonResource
     * @param Paginator|LengthAwarePaginator $paginator
     * @param string|null $message
     * @param class-string<T>|null $resourceClass
     * @return array
     */
    public static function paginated(Paginator|LengthAwarePaginator $paginator, ?string $message = null, ?string $resourceClass = null): array
    {
        // Transform items if a resource class is provided
        if ($resourceClass) {
            $data = $resourceClass::collection($paginator->getCollection())->resolve(request());
        } else {
            $data = $paginator->items();
        }

        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => [
                'current_page' => method_exists($paginator, 'currentPage') ? $paginator->currentPage() : null,
                'last_page' => method_exists($paginator, 'lastPage') ? $paginator->lastPage() : null,
                'per_page' => method_exists($paginator, 'perPage') ? $paginator->perPage() : null,
                'total' => method_exists($paginator, 'total') ? $paginator->total() : null,
                'from' => method_exists($paginator, 'firstItem') ? $paginator->firstItem() : null,
                'to' => method_exists($paginator, 'lastItem') ? $paginator->lastItem() : null,
            ]
        ];
    }

    /**
     * Set validation errors
     */
    public function setErrors(array $errors): self
    {
        $this->errors = $errors;
        return $this;
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if there are errors
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        // If there are errors, return error response
        if ($this->hasErrors()) {
            return [
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $this->errors,
            ];
        }

        // Otherwise return success response
        return [
            'success' => true,
            'message' => $this->additional['message'] ?? null,
            'data' => $this->resource,
        ];
    }
}
