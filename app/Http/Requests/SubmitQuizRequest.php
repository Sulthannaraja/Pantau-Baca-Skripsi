<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SubmitQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'SISWA';
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quizId' => ['required', 'integer', 'exists:quizzes,id'],
            'durationSeconds' => ['nullable', 'integer', 'min:0'],
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.questionId' => ['required', 'integer', 'exists:quiz_questions,id'],
            'answers.*.response' => ['required', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $answers = $this->input('answers', []);

        if (is_array($answers)) {
            $this->merge(['answers' => array_values($answers)]);
        }
    }
}
