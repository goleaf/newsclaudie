<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class NewsIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'categories' => ['nullable', 'array', 'max:10'],
            'categories.*' => ['integer', 'exists:categories,id'],
            'authors' => ['nullable', 'array', 'max:10'],
            'authors.*' => ['integer', 'exists:users,id'],
            'from_date' => ['nullable', 'date', 'before_or_equal:today', 'before_or_equal:to_date'],
            'to_date' => ['nullable', 'date', 'before_or_equal:today', 'after_or_equal:from_date'],
            'sort' => ['nullable', 'string', 'in:newest,oldest'],
            'page' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'categories.array' => 'Categories must be an array.',
            'categories.max' => 'You can select up to 10 categories.',
            'categories.*.integer' => 'Each category must be a valid ID.',
            'categories.*.exists' => 'One or more selected categories do not exist.',
            'authors.array' => 'Authors must be an array.',
            'authors.max' => 'You can select up to 10 authors.',
            'authors.*.integer' => 'Each author must be a valid ID.',
            'authors.*.exists' => 'One or more selected authors do not exist.',
            'from_date.date' => 'The from date must be a valid date.',
            'from_date.before_or_equal' => 'The from date must be before or equal to the to date and not in the future.',
            'to_date.date' => 'The to date must be a valid date.',
            'to_date.before_or_equal' => 'The to date must not be in the future.',
            'to_date.after_or_equal' => 'The to date must be after or equal to the from date.',
            'sort.in' => 'Sort must be either "newest" or "oldest".',
            'page.integer' => 'Page must be a valid number.',
            'page.min' => 'Page must be at least 1.',
            'page.max' => 'Page number too high.',
        ];
    }
}
