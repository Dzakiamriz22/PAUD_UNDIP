<?php

namespace App\Filament\Resources\Banner\ContentCategoryResource\RelationManagers;

use App\Filament\Resources\Banner\ContentCategoryResource;
use App\Filament\Resources\Banner\ContentResource;
use App\Models\Banner\Category;
use App\Models\Banner\Content;
use Filament\Actions\StaticAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class BannersRelationManager extends RelationManager
{
    protected static string $relationship = 'banners';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\MarkdownEditor::make('description')
                    ->columnSpan('full'),
                Forms\Components\TextInput::make('click_url')
                    ->label('Link URL')
                    ->url()
                    ->maxLength(255),
                Forms\Components\Select::make('click_url_target')
                    ->label('Link Target')
                    ->options([
                        '_self' => 'Same Window',
                        '_blank' => 'New Window',
                    ])
                    ->default('_self'),
                Forms\Components\DateTimePicker::make('start_date')
                    ->nullable(),
                Forms\Components\DateTimePicker::make('end_date')
                    ->nullable()
                    ->after('start_date'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                SpatieMediaLibraryImageColumn::make('banner')
                    ->label('Banner')
                    ->collection('banners')
                    ->conversion('thumbnail')
                    ->size(60)
                    ->circular(false)
                    ->alignCenter()
                    ->defaultImageUrl('https://placehold.co/60?text=No\nImage'),
                Tables\Columns\TextColumn::make('title')
                    ->description(fn(Content $record): string => \Illuminate\Support\Str::limit(strip_tags($record->description), 100))
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('locale')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->dateTime()
                    ->sortable(),
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
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
                Tables\Filters\Filter::make('active_date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->where(function ($q) use ($date) {
                                    $q->whereNull('end_date')->orWhere('end_date', '>=', $date);
                                }),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->where(function ($q) use ($date) {
                                    $q->whereNull('start_date')->orWhere('start_date', '<=', $date);
                                }),
                            );
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Create Record')
                    ->color('primary')
                    ->icon('heroicon-o-plus-circle')
                    ->url(fn(): string => ContentResource::getUrl('create')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->hiddenLabel()
                    ->modal()
                    ->modalAutofocus(false)
                    ->modalSubmitAction(fn(StaticAction $action) => $action->label('Save Changes')
                        ->color('success')
                        ->icon('heroicon-o-check-circle'))
                    ->extraModalFooterActions(
                        [
                            Tables\Actions\Action::make('open_full_edit')
                                ->label('Open Full Edit')
                                ->color('primary')
                                ->icon('heroicon-o-arrow-top-right-on-square')
                                ->url(fn(Content $content): string => ContentResource::getUrl('edit', ['record' => $content])),
                        ]
                    ),
                Tables\Actions\DeleteAction::make()->hiddenLabel(),
            ], position: ActionsPosition::BeforeCells)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Trash selected'),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Set Active')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn(\Illuminate\Database\Eloquent\Collection $records) => $records->each->update(['is_active' => true]))
                        ->visible(fn($livewire): bool => !$livewire->isReadOnly()),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Set Inactive')
                        ->icon('heroicon-m-x-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(fn(\Illuminate\Database\Eloquent\Collection $records) => $records->each->update(['is_active' => false]))
                        ->visible(fn($livewire): bool => !$livewire->isReadOnly()),
                ]),
            ]);
    }
}
