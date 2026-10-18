<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:150'],
            'year' => ['nullable', 'integer', 'min:1000', 'max:2100'],
            'category' => ['nullable', 'string', 'max:100'],
            'cover' => ['nullable', 'string', 'max:500'],
            'cover_data' => ['nullable', 'string'],
            'cover_file' => ['nullable', 'file', 'image', 'max:10240'],
            'literacy_level' => ['required', 'string', 'in:RENDAH,TINGGI,DASAR,LANJUTAN'],
            'file' => ['required', 'file', 'mimetypes:application/pdf', 'max:20480'],
        ];
    }
}
