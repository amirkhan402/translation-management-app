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
            'key' => 'required|string|max:255',
            'locale' => 'required|string|max:10',
            'value' => 'required|string',
            'tag_ids' => 'array',
            'tag_ids.*' => 'integer|exists:tags,id'
        ];
    }
} 