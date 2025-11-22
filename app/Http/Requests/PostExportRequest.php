<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PostExportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('access-admin') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'filterByTag' => ['nullable', 'string', 'max:50'],
            'author' => ['nullable', 'integer', 'exists:users,id'],
            'category' => ['nullable', 'string', 'exists:categories,slug'],
            'format' => ['required', 'string', Rule::in(['csv', 'json'])],
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
            'filterByTag.string' => __('validation.posts.filter_tag_string'),
            'filterByTag.max' => __('validation.posts.filter_tag_max'),
            'author.integer' => __('validation.posts.author_integer'),
            'author.exists' => __('validation.posts.author_exists'),
            'category.string' => __('validation.posts.category_string'),
            'category.exists' => __('validation.posts.category_exists'),
            'format.in' => __('validation.posts.export_format'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return collect($this->only(['filterByTag', 'author', 'category']))
            ->reject(static fn ($value) => $value === null || $value === '')
            ->toArray();
    }
}
