<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLessonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120', 'unique:lessons,title'],
            'category' => ['required', 'string', 'max:80'],
            'difficulty' => ['required', 'string', 'in:Beginner,Practice'],
            'duration_minutes' => ['required', 'integer', 'between:1,60'],
            'description' => ['required', 'string', 'max:255'],
            'sign_reference' => ['required', 'string', 'max:2000'],
            'practice_tip' => ['required', 'string', 'max:255'],
        ];
    }
}
