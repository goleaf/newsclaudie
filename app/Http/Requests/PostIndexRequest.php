<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\Pagination\PageSize;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PostIndexRequest extends FormRequest
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
        $perPageOptions = PageSize::contextOptions('posts');
        $perPageParam = PageSize::queryParam();

        return [
            'filterByTag' => ['nullable', 'string', 'max:50'],
            'author' => ['nullable', 'integer', 'exists:users,id'],
            'category' => ['nullable', 'string', 'exists:categories,slug'],
            $perPageParam => ['nullable', 'integer', Rule::in($perPageOptions)],
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $perPageParam = PageSize::queryParam();

        return [
            'filterByTag.string' => __('validation.posts.filter_tag_string'),
            'filterByTag.max' => __('validation.posts.filter_tag_max'),
            'author.integer' => __('validation.posts.author_integer'),
            'author.exists' => __('validation.posts.author_exists'),
            'category.string' => __('validation.posts.category_string'),
            'category.exists' => __('validation.posts.category_exists'),
            "{$perPageParam}.in" => __('validation.posts.per_page_options'),
        ];
    }
}
