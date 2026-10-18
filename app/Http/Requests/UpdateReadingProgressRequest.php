<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateReadingProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'bookId' => ['required', 'integer', 'exists:books,id'],
            'duration' => ['required', 'integer', 'min:0'],
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
            'lastPage' => ['required', 'integer', 'min:1'],
        ];
    }
}
