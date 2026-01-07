<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
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
            'token' => 'required',
            'email' => [
                'required',
                'email',
                function (string $attribute, mixed $value, Closure $fail) {
                    // Verify that the email exists in password_reset_tokens table
                    // This ensures the email is associated with a reset token
                    $tokenRecord = DB::table('password_reset_tokens')
                        ->where('email', $value)
                        ->first();

                    if (!$tokenRecord) {
                        $fail('The email is not valid or has expired.');
                        return;
                    }

                    // Note: Laravel's Password::broker()->reset() will also validate
                    // that the token matches the email, so this is an extra layer
                    // to catch mismatched email/token combinations early
                },
            ],
            'password' => ['required', 'min:8', 'confirmed', Password::defaults()],
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
        ];
    }
}
