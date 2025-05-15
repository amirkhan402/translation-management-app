<?php

namespace App\Exceptions;

use Exception;

class DuplicateTranslationException extends Exception
{
    public function render($request)
    {
        return response()->json([
            'message' => $this->getMessage(),
            'errors' => [
                'key' => ['A translation with this key and locale already exists.']
            ]
        ], 422);
    }
}
