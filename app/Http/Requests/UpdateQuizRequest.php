<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['GURU', 'ADMIN'], true);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'bookId' => ['sometimes', 'required', 'integer', 'exists:books,id'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'active' => ['sometimes', 'boolean'],
            'questions' => ['sometimes', 'required', 'array', 'min:1'],
            'questions.*.type' => ['required_with:questions', Rule::in(['MCQ', 'ESSAY'])],
            'questions.*.question' => ['required_with:questions', 'string'],
            'questions.*.options' => ['nullable', 'array'],
            'questions.*.options.*' => ['string'],
            'questions.*.answer' => ['nullable', 'string'],
            'questions.*.points' => ['nullable', 'integer', 'min:1'],
            'questions.*.order' => ['nullable', 'integer'],
            'questions.*.explanation' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $questions = $this->input('questions', []);

            if (! is_array($questions)) {
                return;
            }

            foreach ($questions as $index => $question) {
                if (isset($question['type']) && $question['type'] === 'MCQ') {
                    $options = array_values(array_filter(
                        array_map(fn ($o) => trim((string) $o), (array) ($question['options'] ?? [])),
                        fn ($o) => $o !== ''
                    ));

                    if (count($options) < 2) {
                        $validator->errors()->add("questions.{$index}.options", 'Pilihan ganda harus memiliki minimal 2 pilihan jawaban.');
                    }

                    $answer = trim((string) ($question['answer'] ?? ''));
                    if ($answer === '') {
                        $validator->errors()->add("questions.{$index}.answer", 'Kunci jawaban tidak boleh kosong.');
                    } elseif (! in_array($answer, $options, true)) {
                        $validator->errors()->add("questions.{$index}.answer", 'Kunci jawaban harus sesuai dengan salah satu pilihan yang tersedia.');
                    }
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $questions = $this->input('questions', []);

        if (is_array($questions)) {
            $this->merge(['questions' => array_values($questions)]);
        }
    }
}
