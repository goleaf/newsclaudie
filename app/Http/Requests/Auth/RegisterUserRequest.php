<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class RegisterUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) config('blog.allowRegistrations');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
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
            'name.required' => __('validation.auth.register.name_required'),
            'name.string' => __('validation.auth.register.name_string'),
            'name.max' => __('validation.auth.register.name_max'),
            'email.required' => __('validation.auth.register.email_required'),
            'email.email' => __('validation.auth.register.email_email'),
            'email.unique' => __('validation.auth.register.email_unique'),
            'password.required' => __('validation.auth.register.password_required'),
            'password.confirmed' => __('validation.auth.register.password_confirmed'),
            'password.*' => __('validation.auth.register.password_rules'),
        ];
    }
}

