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
use Illuminate\Support\Str;

class ChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'children';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn(string $operation, $state, Forms\Set $set) =>
                    $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                // Forms\Components\TextInput::make('slug')
                //     ->required()
                //     ->maxLength(255)
                //     ->unique(ignoreRecord: true),
                Forms\Components\Select::make('locale')
                    ->options([
                        'en' => 'English',
                        'id' => 'Indonesian',
                        'zh' => 'Chinese',
                        'ja' => 'Japanese',
                    ])
                    ->default('en')
                    ->required(),
                Forms\Components\MarkdownEditor::make('description')
                    ->columnSpan('full'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn(Category $record) => $record->parent ? "Child of {$record->parent->name}" : '')
                    ->wrap(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('locale')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('banners_count')
                    ->label('Banners')
                    ->counts('banners')
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
                Tables\Filters\SelectFilter::make('locale')
                    ->options([
                        'en' => 'English',
                        'id' => 'Indonesian',
                        'zh' => 'Chinese',
                        'ja' => 'Japanese',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Create Record')
                    ->color('primary')
                    ->icon('heroicon-o-plus-circle')
                    ->url(fn(): string => ContentCategoryResource::getUrl('create')),
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
                                ->url(fn(Category $category): string => ContentCategoryResource::getUrl('edit', ['record' => $category])),
                        ]
                    ),
                Tables\Actions\DeleteAction::make()->hiddenLabel(),
            ], position: ActionsPosition::BeforeCells)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
