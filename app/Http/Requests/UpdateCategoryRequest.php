<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateCategoryRequest extends FormRequest
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
        $category = $this->route('category');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('categories', 'slug')->ignore($category?->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
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
            'name.required' => __('validation.category.name_required'),
            'name.string' => __('validation.category.name_string'),
            'name.max' => __('validation.category.name_max'),
            'slug.required' => __('validation.category.slug_required'),
            'slug.string' => __('validation.category.slug_string'),
            'slug.max' => __('validation.category.slug_max'),
            'slug.regex' => __('validation.category.slug_regex'),
            'slug.unique' => __('validation.category.slug_unique'),
            'description.string' => __('validation.category.description_string'),
            'description.max' => __('validation.category.description_max'),
        ];
    }
}
