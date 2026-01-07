<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleHasScopeResource\Pages;
use App\Filament\Resources\RoleHasScopeResource\RelationManagers;
use App\Models\RoleHasScope;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Schema;

class RoleHasScopeResource extends Resource
{
    protected static ?string $model = RoleHasScope::class;
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationIcon = 'fluentui-shield-lock-20-o';
    protected static ?string $navigationLabel = 'Role Scopes';
    protected static ?string $pluralModelLabel = 'Role Scopes';
    protected static ?string $label = 'Role Scope';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('model_role_id')
                    ->label('User & Role')
                    ->options(function () {
                        return \App\Models\ModelHasRole::with('model', 'role')
                            ->whereHas('role', function ($query) {
                                $query->whereNotIn('name', [
                                    config('filament-shield.super_admin.name'),
                                    'admin',
                                ]);
                            })
                            ->whereHas('model')
                            ->get()
                            ->mapWithKeys(function ($modelRole) {
                                return [
                                    $modelRole->id => $modelRole->model->fullname . ' - ' . $modelRole->role->name,
                                ];
                            })->toArray();
                    })
                    ->searchable()
                    ->required(),

                Forms\Components\Select::make('scope_type')
                    ->label('Scope Type')
                    ->options(\App\Models\RoleHasScope::getAvailableScopes())
                    ->reactive()
                    ->required(),

                Forms\Components\Select::make('scope_id')
                    ->label('Scope')
                    ->options(function (callable $get) {
                        $scopeType = $get('scope_type');

                        if (! $scopeType || ! class_exists($scopeType)) {
                            return [];
                        }

                        $query = $scopeType::query();

                        $result = $query->get()
                            ->mapWithKeys(function ($record) {
                                $label = $record->name;
                                if (! empty($record->code)) {
                                    $label = "{$record->code} | {$record->name}";
                                }

                                return [
                                    $record->id => $label
                                ];
                            });

                        // Cek apakah model punya kolom code
                        $data = $result->sortBy('id')->toArray();

                        asort($data);

                        return $data;
                    })
                    ->searchable()
                    ->required()
                    ->rules([
                        fn($set, $get, $record): Closure => function (string $attribute, $value, Closure $fail) use ($set, $get, $record) {
                            $modelRoleId = $get('model_role_id');
                            $scopeType = $get('scope_type');
                            $scopeId = $value;

                            if (! $modelRoleId || ! $scopeType || ! $scopeId) {
                                return [];
                            }

                            $existingRoleHasScope = \App\Models\RoleHasScope::where('model_role_id', $modelRoleId)
                                ->where('scope_type', $scopeType)
                                ->where('scope_id', $scopeId)
                                ->when($record, function (Builder $query) use ($record) {
                                    return $query->where('id', '!=', $record->id);
                                })
                                ->exists();
                            if ($existingRoleHasScope) {
                                $fail('The :attribute has already been taken.');
                            }
                        },
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('modelRole.model.fullname')
                    ->label('User'),

                Tables\Columns\TextColumn::make('modelRole.role.name')
                    ->label('Role')
                    ->searchable(),

                Tables\Columns\TextColumn::make('scope_type')
                    ->label('Scope Type')
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        return class_basename($state);
                    }),

                Tables\Columns\TextColumn::make('scope.name')
                    ->label('Scope Name'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Update')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('scope_type')
                    ->options(RoleHasScope::getAvailableScopes()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoleHasScopes::route('/'),
            'create' => Pages\CreateRoleHasScope::route('/create'),
            'edit' => Pages\EditRoleHasScope::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __("menu.nav_group.access");
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        return $query->whereHas('modelRole', function ($inner_query) {
            $inner_query->whereHas('model');
        });
    }
}
