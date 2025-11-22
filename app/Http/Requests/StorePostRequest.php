<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

final class StorePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        return $user->can('create', Post::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'description' => 'nullable|string|max:255',
            'featured_image' => 'nullable|url|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'categories' => 'nullable|array',
            'categories.*' => 'integer|exists:categories,id',
            'published_at' => 'nullable|date|after:1970-12-31T12:00|before:2038-01-09T03:14',
        ];
    }

    /**
     * Custom validation messages for clearer field feedback.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tags.array' => __('validation.posts.tags_array'),
            'tags.*.string' => __('validation.posts.tags_string'),
            'tags.*.max' => __('validation.posts.tags_length', ['max' => 50]),
            'categories.array' => __('validation.posts.categories_array'),
            'categories.*.integer' => __('validation.posts.categories_integer'),
            'categories.*.exists' => __('validation.posts.categories_exists'),
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * Thanks to SO user BenSampo!
     *
     * @see https://stackoverflow.com/a/54480210/5700388
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'published_at' => $this->normalizePublishedAt(),
            'tags' => $this->prepareTagsInput($this->input('tags_input')),
            'categories' => $this->prepareCategoryIds($this->input('categories')),
        ]);
    }

    /**
     * Normalize the incoming free-form tags input.
     */
    protected function prepareTagsInput(?string $raw): ?array
    {
        if ($raw === null) {
            return null;
        }

        $tags = collect(explode(',', $raw))
            ->map(fn ($tag) => trim($tag))
            ->filter()
            ->unique()
            ->values();

        return $tags->isEmpty() ? null : $tags->all();
    }

    /**
     * Normalize the incoming published_at value while preserving invalid input for validation feedback.
     */
    protected function normalizePublishedAt(): mixed
    {
        if ($this->is_draft || ! $this->filled('published_at')) {
            return null;
        }

        $value = $this->input('published_at');

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return $value;
        }
    }

    /**
     * Normalize the incoming category IDs to a unique integer list.
     */
    protected function prepareCategoryIds($raw): array
    {
        $candidates = is_array($raw) ? $raw : ($raw === null ? [] : [$raw]);

        return collect($candidates)
            ->map(fn ($id) => is_numeric($id) ? (int) $id : $id)
            ->filter(fn ($id) => is_int($id) && $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
