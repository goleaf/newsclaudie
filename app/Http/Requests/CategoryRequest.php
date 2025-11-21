<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
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
        $categoryId = $this->route('category') ? $this->route('category')->id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('categories', 'slug')->ignore($categoryId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('validation.category_name_required'),
            'name.string' => __('validation.category_name_string'),
            'name.max' => __('validation.category_name_max'),
            'slug.required' => __('validation.category_slug_required'),
            'slug.string' => __('validation.category_slug_string'),
            'slug.max' => __('validation.category_slug_max'),
            'slug.regex' => __('validation.category_slug_regex'),
            'slug.unique' => __('validation.category_slug_unique'),
            'description.string' => __('validation.category_description_string'),
            'description.max' => __('validation.category_description_max'),
        ];
    }
}
