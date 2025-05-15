<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Translation;

use App\Http\Requests\Api\BaseFormRequest;

class CreateRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'key' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9_\.]+$/', // Only allow lowercase letters, numbers, underscores, and dots
            ],
            'locale' => [
                'required',
                'string',
                'max:5', // Reduced from 10 to 5 to match model validation
                'regex:/^[a-z]{2}(_[A-Z]{2})?$/', // Allow 'en' or 'en_US' format
            ],
            'value' => [
                'required',
                'string',
                'max:65535', // Maximum text field length
            ],
            'tag_ids' => [
                'array',
                'min:0',
            ],
            'tag_ids.*' => [
                'string',
                'uuid',
                'exists:tags,id',
                'distinct', // Ensure no duplicate tag IDs
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'key.regex' => 'The key must contain only lowercase letters, numbers, underscores, and dots.',
            'locale.regex' => 'The locale must be in the format "en" or "en_US".',
            'tag_ids.*.distinct' => 'Duplicate tag IDs are not allowed.',
        ];
    }
}
