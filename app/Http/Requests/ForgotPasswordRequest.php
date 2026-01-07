<?php

namespace App\Http\Requests;

use Coderflex\LaravelTurnstile\Rules\TurnstileCheck;
use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
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
            'email' => 'required|email|exists:users,email',
            'cf-turnstile-response' => [new TurnstileCheck()],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.exists' => 'We could not find a user with that email address.',
            'turnstile_check_message' => 'The CAPTCHA failed, please refresh and try again.',
        ];
    }
}

