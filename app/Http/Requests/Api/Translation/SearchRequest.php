<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Translation;

use App\Http\Requests\Api\BaseFormRequest;

class SearchRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'query' => 'required|string|min:1'
        ];
    }

} 