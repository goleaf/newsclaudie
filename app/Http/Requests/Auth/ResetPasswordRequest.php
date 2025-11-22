<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class ResetPasswordRequest extends FormRequest
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
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::defaults()],
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
            'email.required' => __('validation.auth.reset.email_required'),
            'email.email' => __('validation.auth.reset.email_email'),
            'password.required' => __('validation.auth.reset.password_required'),
            'password.confirmed' => __('validation.auth.reset.password_confirmed'),
            'password.*' => __('validation.auth.reset.password_rules'),
        ];
    }

    /**
     * Ensure route parameters are available to the validator.
     */
    protected function prepareForValidation(): void
    {
        if ($this->route('token') && ! $this->filled('token')) {
            $this->merge([
                'token' => $this->route('token'),
            ]);
        }
    }
}
