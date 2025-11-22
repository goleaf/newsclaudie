<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class ShowResetPasswordRequest extends FormRequest
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
            'token' => ['required', 'string'],
            'email' => ['nullable', 'email'],
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
            'token.required' => __('validation.auth.reset.token_required'),
            'email.email' => __('validation.auth.reset.email_email'),
        ];
    }

    /**
     * Ensure route parameters participate in validation.
     */
    protected function prepareForValidation(): void
    {
        if ($token = $this->route('token')) {
            $this->merge(['token' => $token]);
        }
    }
}
