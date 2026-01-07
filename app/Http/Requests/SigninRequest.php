<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Coderflex\LaravelTurnstile\Rules\TurnstileCheck;

class SigninRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required',
            'remember' => 'nullable|boolean',
            'cf-turnstile-response' => [new TurnstileCheck()]
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'The email field is required.',
            'email.email' => 'Please enter a valid email address.',
            'password.required' => 'The password field is required.',
            'turnstile_check_message' => 'The CAPTCHA failed, please refresh and try again.',
        ];
    }
}

