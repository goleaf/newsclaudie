<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

final class UpdatePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $post = $this->route('post');

        if ($user === null || ! $post instanceof Post) {
            return false;
        }

        return $user->can('update', $post);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'description' => 'nullable|string|max:255',
            'featured_image' => 'nullable|url|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'published_at' => 'nullable|date|after:1970-12-31T12:00|before:2038-01-09T03:14',
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
            'published_at' => $this->is_draft
                ? null
                : Carbon::parse($this->published_at),
            'tags' => $this->prepareTagsInput($this->input('tags_input')),
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
}
