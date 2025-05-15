<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

abstract class BaseFormRequest extends FormRequest
{
    protected function failedValidation(Validator $validator): void
    {
        if ($this->expectsJson()) {
            Log::info('Validation failed', [
                'errors' => $validator->errors()->toArray(),
                'input' => $this->all(),
                'route' => $this->route()->getName(),
                'method' => $this->method()
            ]);

            throw new HttpResponseException(
                response()->json([
                    'success' => false,
                    'message' => 'The given data was invalid.',
                    'errors' => $validator->errors(),
                ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
            );
        }
        parent::failedValidation($validator);
    }
}
