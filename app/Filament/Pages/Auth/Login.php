<?php

namespace App\Filament\Pages\Auth;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Forms\Form;
use Filament\Pages\Auth\Login as BasePage;
use Illuminate\Contracts\Support\Htmlable;
use DiogoGPinto\AuthUIEnhancer\Pages\Auth\Concerns\HasCustomLayout;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Validation\ValidationException;

class Login extends BasePage
{

    use HasCustomLayout;

    public function mount(): void
    {
        parent::mount();
        if (config('app.env') == 'local') {
            $this->form->fill([
                'email' => 'superadmin@local.com',
                'password' => config('app.default_user_password'),
            ]);
        }
    }

    // public function form(Form $form): Form
    // {
    //     return $form
    //         ->schema([
    //             $this->getEmailFormComponent()->label('Email'),
    //             // $this->getUsernameFormComponent(),
    //             $this->getPasswordFormComponent(),
    //             $this->getRememberFormComponent(),
    //             \Coderflex\FilamentTurnstile\Forms\Components\Turnstile::make('captcha')
    //                 ->hiddenLabel()
    //                 ->size('flexible'),
    //         ])->statePath('data');
    // }

    // public function getHeading(): string | Htmlable
    // {
    //     return '';
    // }

    public function getUsernameFormComponent(): Component
    {
        return TextInput::make('username')
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'email' => $data['email'],
            // 'username' => $data['username'],
            'password' => $data['password'],
        ];
    }

    protected function throwFailureValidationException(): never
    {
        // $this->dispatch('reset-captcha');

        throw ValidationException::withMessages([
            'data.email' => __('filament-panels::pages/auth/login.messages.failed'),
            // 'data.username' => __('filament-panels::pages/auth/login.messages.failed'),
        ]);
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        if (! Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
            $this->throwFailureValidationException();
        }

        $user = Filament::auth()->user();

        if (
            ($user instanceof FilamentUser) &&
            (! $user->canAccessPanel(Filament::getCurrentPanel()))
        ) {
            Filament::auth()->logout();

            $this->throwFailureValidationException();
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }
}
