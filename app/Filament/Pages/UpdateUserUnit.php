<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

use function Laravel\Prompts\form;

class UpdateUserUnit extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.update-user-unit';

    public $defaultAction = 'onboarding';

    public function onboardingAction(): Action
    {
        return Action::make('onboarding')
            ->form([
                \Filament\Forms\Components\Select::make('kode_unit')
                    ->label('Unit/Prodi')
                    ->searchable()
                    ->options(function () {
                        $unit = [];
                        $unit_list = \App\Models\Unit::whereRaw('LENGTH(code) = 6')->with('parent')->orderBy('code')->get();
                        foreach ($unit_list as $key => $value) {
                            $unit[$value->code] = $value->parent->name . ' > ' . $value->name;
                        }
                        return $unit;
                    })
                    ->helperText(function (): Htmlable {
                        return new HtmlString('<span class="text-sm text-orange-500">mohon diperhatikan unit/prodi yang dipilih tidak dapat diubah</span>');
                    })
                    ->required(),
            ])
            ->closeModalByClickingAway(false)
            ->closeModalByEscaping(false)
            ->modalCloseButton(false)
            ->modalCancelAction(false)
            ->modalHeading('Pilih Unit/Prodi Anda')
            ->modalDescription('Sebelum anda melanjutkan mohon dapat dipilih unit/prodi anda.')
            ->action(function ($data) {
                /** @var \App\Models\User $user */
                $user = Auth::user();
                $user->kode_unit = $data['kode_unit'];
                $user->save();

                // redirect ke halaman dashboard
                $this->redirect(route('filament.admin.pages.dashboard'));
            });
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
