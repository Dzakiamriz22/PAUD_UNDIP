<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Coderflex\LaravelTurnstile\Rules\TurnstileCheck;

class SignupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'telp' => ['required', 'string', 'max:255', 'regex:/^\+?[0-9]+$/'],
            'password' => ['required', 'string', 'min:8', 'confirmed', Password::defaults()],
            'accept_terms' => 'required|accepted',
            'cf-turnstile-response' => [new TurnstileCheck()]
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
            'password.confirmed' => 'The password confirmation does not match.',
            'accept_terms.accepted' => 'You must accept the terms and conditions.',
            'telp.regex' => 'Phone number can only contain numbers.',
            'turnstile_check_message' => 'The CAPTCHA failed, please refresh and try again.',
        ];
    }
}
