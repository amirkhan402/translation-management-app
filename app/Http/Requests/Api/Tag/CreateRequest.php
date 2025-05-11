<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Tag;

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
            'name' => 'required|string|max:255'
        ];
    }
} 