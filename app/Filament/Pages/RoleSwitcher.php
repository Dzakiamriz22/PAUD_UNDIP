<?php

namespace App\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use CodeWithDennis\SimpleAlert\Components\Forms\SimpleAlert;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class RoleSwitcher extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'fluentui-person-tag-20-o';


    protected static string $view = 'filament.pages.role-switcher';

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $activeRole = session()->get('active_role');

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($activeRole && $user->roles()->where('name', $activeRole)->exists()) {
            $this->form->fill([
                'select_role' => $activeRole
            ]);
        }
    }

    /**
     * @return array<int | string, string | Form>
     */
    protected function getForms(): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $userRoles = $user->roles()
            ->pluck('name', 'roles.id');
        return [
            'form' => $this->makeForm()
                ->schema([
                    SimpleAlert::make('before_select_role')
                        ->columnSpanFull()
                        ->warning()
                        ->border()
                        ->title('You must select a role')
                        ->description('Before you can continue, please pick a role from the list below')
                        ->hidden(fn(): bool => !empty($this->data['select_role'])),
                    SimpleAlert::make('after_select_role')
                        ->columnSpanFull()
                        ->info()
                        ->border()
                        ->title('You have selected a role')
                        ->description(fn() => new HtmlString('You are currently in <strong>' . strtoupper(str_replace('_', ' ',  $this->data['select_role'])) . '</strong> role, you can switch it by selecting a role from the list below.'))
                        ->hidden(fn(): bool => empty($this->data['select_role'])),
                    \JaOcero\RadioDeck\Forms\Components\RadioDeck::make('select_role')
                        ->options(function () use ($userRoles) {
                            return $userRoles->mapWithKeys(function ($role, $id) {
                                return [$role => strtoupper(str_replace('_', ' ',  $role))];
                            });
                        })
                        ->descriptions(function () use ($userRoles) {
                            return $userRoles->mapWithKeys(function ($role, $id) {
                                return [$role => 'Pick ' . str_replace('_', ' ', $role) . ' role'];
                            });
                        })
                        ->icons(function () use ($userRoles) {
                            return $userRoles->mapWithKeys(function ($role, $id) {
                                return [$role => 'fluentui-person-star-20-o'];
                            });
                        })
                        ->required()
                        ->color('danger')
                        ->columns(3),
                ])
                ->statePath('data')
                ->inlineLabel($this->hasInlineLabels()),
        ];
    }

    public function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Pick Selected Role')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            /** @var \App\Models\User $user */
            $user = Auth::user();
            $selectedRole = $user->roles()->where('name', $data['select_role'])->first();

            session()->put('active_role', $selectedRole->name);
            session()->put('active_role_id', $selectedRole->id);

            $this->dispatch('role-switched');
        } catch (\Throwable $th) {

            throw $th;
        }
    }
}
