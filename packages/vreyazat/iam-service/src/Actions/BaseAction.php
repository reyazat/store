<?php

namespace IamApi\Actions;

use Illuminate\Validation\ValidationException;

abstract class BaseAction
{
    /**
     * Execute the action.
     */
    abstract public function execute(array $data = []): mixed;

    /**
     * Validate input data.
     * @throws ValidationException
     */
    protected function validate(array $data, array $rules): array
    {
        $validator = validator($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Handle an action result.
     */
    protected function success(mixed $data = null, ?string $message = null): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];
    }

    /**
     * Handle action error.
     */
    protected function error(string $message, mixed $data = null, int $code = 400): array
    {
        return [
            'success' => false,
            'message' => $message,
            'data' => $data,
            'code' => $code
        ];
    }
}
