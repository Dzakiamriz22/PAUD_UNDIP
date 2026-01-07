<?php

namespace App\Livewire\Approval;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Support\Facades\Auth;

class ListUsers extends ListRecords
{

    protected static string $resource = UserResource::class;

    public array $role_names = ['-'];
    public User $user;

    public function getTabs(): array
    {
        /** @var \App\Models\User $user */
        // $user = Auth::user();
        $tabs = [];

        foreach ($this->role_names as $role_name) {
            $tabs[$role_name] = Tab::make($role_name)
                ->query(fn(Builder $query) => $query->with('roles')->whereRelation('roles', 'name', '=', $role_name));
        }

        return $tabs;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('username')->label('Username')
                    ->description(fn(User $record) => $record->firstname . ' ' . $record->lastname),
                Tables\Columns\TextColumn::make('roles.name')->label('Role')
                    ->formatStateUsing(fn($state): string => \Illuminate\Support\Str::headline($state))
                    ->badge(),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('verified_status')
                    ->label('Verified')
                    ->color(fn(string $state): string => match ($state) {
                        'Verified' => 'success',
                        'Unverified' => 'warning',
                    })
                    ->badge()

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->hiddenLabel()
                    ->modal(),
            ])
            ->bulkActions([
                //
            ])
            ->paginated(false)
            ->recordUrl(null);
    }

    public function render(): View
    {
        return view('livewire.approval-surat.list-users');
    }
}
