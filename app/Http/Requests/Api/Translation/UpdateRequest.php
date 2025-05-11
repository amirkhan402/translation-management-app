<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Translation;

use App\Http\Requests\Api\BaseFormRequest;

class UpdateRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'key' => 'sometimes|string|max:255',
            'locale' => 'sometimes|string|max:10',
            'value' => 'sometimes|string',
            'tag_ids' => 'array',
            'tag_ids.*' => 'integer|exists:tags,id'
        ];
    }
} 