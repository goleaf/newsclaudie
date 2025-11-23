<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Comment;
use Illuminate\Foundation\Http\FormRequest;

final class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Comment::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'content' => [
                'required',
                'string',
                'min:3',
                'max:5000', // Increased from 1024 to match TEXT column capacity
                function ($attribute, $value, $fail) {
                    // Security: Check for excessive links (spam indicator)
                    $linkCount = substr_count(strtolower($value), 'http');
                    if ($linkCount > 3) {
                        $fail(__('validation.comment_too_many_links'));
                    }
                    
                    // Security: Check for excessive uppercase (spam indicator)
                    $uppercaseCount = strlen(preg_replace('/[^A-Z]/', '', $value));
                    $totalLetters = strlen(preg_replace('/[^A-Za-z]/', '', $value));
                    if ($totalLetters > 0 && ($uppercaseCount / $totalLetters) > 0.7) {
                        $fail(__('validation.comment_excessive_caps'));
                    }
                },
            ],
        ];
    }
    
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Security: Strip HTML tags to prevent XSS
        if ($this->has('content')) {
            $this->merge([
                'content' => strip_tags($this->input('content')),
            ]);
        }
    }
}



