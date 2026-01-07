<?php

namespace App\Filament\Resources\Banner;

use App\Filament\Resources\Banner\ContentCategoryResource\Pages;
use App\Filament\Resources\Banner\ContentCategoryResource\RelationManagers;
use App\Models\Banner\Category;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Infolists;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Schmeits\FilamentCharacterCounter\Forms\Components\Textarea;

class ContentCategoryResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Category::class;
    protected static ?string $slug = 'banner/categories';
    protected static ?int $navigationSort = -1;
    protected static ?string $navigationIcon = 'fluentui-stack-20-o';
    protected static ?string $navigationLabel = 'Categories';
    protected static ?string $recordTitleAttribute = 'name';

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'restore',
            'restore_any',
            'delete',
            'delete_any',
            'force_delete',
            'force_delete_any',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Category Details')
                    ->tabs([
                        Tabs\Tab::make('Basic Information')
                            ->schema([
                                Forms\Components\Select::make('parent_id')
                                    ->label('Parent Category')
                                    ->options(function () {
                                        // Exclude the current category if editing
                                        $query = Category::query();
                                        if (request()->route('record')) {
                                            $query->where('id', '!=', request()->route('record'));
                                        }
                                        return $query->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->nullable()
                                    ->preload()
                                    ->columnSpan('full'),

                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn(string $operation, $state, Forms\Set $set) =>
                                    $operation === 'create' ? $set('slug', Str::slug($state)) : null),

                                Forms\Components\TextInput::make('slug')
                                    ->disabled()
                                    ->dehydrated()
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(Category::class, 'slug', ignoreRecord: true)
                                    ->helperText('URL-friendly name. Will be auto-generated from the name if left empty.')
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('editSlug')
                                            ->modal()
                                            ->icon('heroicon-o-pencil-square')
                                            ->modalHeading('Edit Slug')
                                            ->modalDescription('Customize the URL slug for this Post. Use lowercase letters, numbers, and hyphens only.')
                                            ->modalIcon('heroicon-o-link')
                                            ->modalSubmitActionLabel('Update Slug')
                                            ->form([
                                                Forms\Components\TextInput::make('new_slug')
                                                    ->hiddenLabel()
                                                    ->required()
                                                    ->maxLength(255)
                                                    // ->live(debounce: 500)
                                                    // ->afterStateUpdated(function (?string $state, Set $set) {
                                                    //     if(!empty($state)) {
                                                    //         $set('slug', Str::slug($state));
                                                    //     }
                                                    // })
                                                    ->unique(Category::class, 'slug', ignoreRecord: true)
                                                    ->helperText('The slug will be automatically formatted as you type.')
                                            ])
                                            ->fillForm(fn(Get $get): array => [
                                                'new_slug' => $get('slug'),
                                            ])
                                            ->action(function (Forms\Components\Actions\Action $action, array $data, Set $set) {
                                                // Validate the new slug
                                                if (empty($data['new_slug']) || !preg_match('/^[a-z0-9-]+$/', $data['new_slug'])) {
                                                    Notification::make()
                                                        ->title('Slug Update Failed')
                                                        ->body('The slug must contain only lowercase letters, numbers, and hyphens.')
                                                        ->danger()
                                                        ->send();

                                                    $action->halt();
                                                }
                                                $set('slug', $data['new_slug']);

                                                // Notification::make()
                                                //     ->title('Slug updated')
                                                //     ->success()
                                                //     ->send();
                                            })
                                            ->hidden(fn(string $operation): bool => $operation === 'view')
                                    ),

                                Forms\Components\Select::make('locale')
                                    ->options([
                                        'en' => 'English',
                                        'id' => 'Indonesian',
                                        'zh' => 'Chinese',
                                        'ja' => 'Japanese',
                                        // Add more languages as needed
                                    ])
                                    ->default('en')
                                    ->required(),

                                Forms\Components\Section::make('Status')
                                    ->description('Configure the active status of this category')
                                    ->schema([
                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Active')
                                            ->helperText('Only active categories will be shown on the frontend')
                                            ->default(true),
                                    ])
                                    ->collapsible(),
                            ]),

                        Tabs\Tab::make('Content')
                            ->schema([
                                Forms\Components\MarkdownEditor::make('description')
                                    ->columnSpan('full')
                                    ->fileAttachmentsDisk('public')
                                    ->fileAttachmentsDirectory('category-images')
                                    ->fileAttachmentsVisibility('public'),
                            ]),

                        Tabs\Tab::make('SEO & Meta')
                            ->schema([
                                Forms\Components\TextInput::make('meta_title')
                                    ->maxLength(255)
                                    ->helperText('Leave blank to use the category name'),

                                Forms\Components\Textarea::make('meta_description')
                                    ->maxLength(500)
                                    ->rows(3)
                                    ->helperText('Brief description for search engines. Recommended length: 150-160 characters.'),
                            ]),

                        Tabs\Tab::make('Advanced Options')
                            ->schema([
                                Forms\Components\KeyValue::make('options')
                                    ->keyLabel('Option Name')
                                    ->valueLabel('Option Value')
                                    ->addable()
                                    ->reorderable()
                                    ->columnSpan('full')
                                    ->helperText('Custom options for this category (JSON format)')
                            ]),
                    ])
                    ->columnSpan('full'),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Basic Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                        Infolists\Components\TextEntry::make('slug')
                            ->label('Slug (URL)'),
                        Infolists\Components\TextEntry::make('parent.name')
                            ->label('Parent Category')
                            ->default('None'),
                        Infolists\Components\IconEntry::make('is_active')
                            ->label('Status')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('locale')
                            ->label('Language'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Description')
                    ->schema([
                        Infolists\Components\TextEntry::make('description')
                            ->markdown(),
                    ]),

                Infolists\Components\Section::make('SEO Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('meta_title'),
                        Infolists\Components\TextEntry::make('meta_description'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Statistics')
                    ->schema([
                        Infolists\Components\TextEntry::make('banners_count')
                            ->label('Number of Banners')
                            ->state(fn(Category $record): int => $record->banners()->count()),
                        Infolists\Components\TextEntry::make('created_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('creator.name')
                            ->label('Created By')
                            ->default('System'),
                        Infolists\Components\TextEntry::make('updater.name')
                            ->label('Last Updated By')
                            ->default('System'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn(Category $record) => $record->parent ? "Child of : {$record->parent->name}" : '')
                    ->wrap(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('locale')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->sortable()
                    ->toggleable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('banners_count')
                    ->label('Banners')
                    ->counts('banners')
                    ->sortable()
                    ->alignCenter(),
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
                Tables\Filters\SelectFilter::make('parent_id')
                    ->label('Parent Category')
                    ->options(fn() => Category::pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('locale')
                    ->options([
                        'en' => 'English',
                        'id' => 'Indonesian',
                        'zh' => 'Chinese',
                        'ja' => 'Japanese',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
                Tables\Filters\TernaryFilter::make('root')
                    ->label('Root Categories Only')
                    ->queries(
                        true: fn(Builder $query) => $query->whereNull('parent_id'),
                        false: fn(Builder $query) => $query->whereNotNull('parent_id'),
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn(Category $category, $livewire): string =>
                    ContentCategoryResource::getUrl('view', [
                        'record' => $category,
                        'page' => $livewire->getPage(),
                        'activeTab' => $livewire->activeTab,
                        'tableFilters' => $livewire->tableFilters,
                        'tableSearch' => $livewire->tableSearch
                    ]))
                    ->hiddenLabel(),
                Tables\Actions\EditAction::make()
                    ->color('primary')
                    ->hiddenLabel()
                    ->url(fn(Category $category, $livewire): string =>
                    ContentCategoryResource::getUrl('edit', [
                        'record' => $category,
                        'page' => $livewire->getPage(),
                        'activeTab' => $livewire->activeTab,
                        'tableFilters' => $livewire->tableFilters,
                        'tableSearch' => $livewire->tableSearch
                    ]))
                    ->hidden(fn(Category $category): bool => $category->trashed()),
                // Tables\Actions\ActionGroup::make([
                //     Tables\Actions\Action::make('view_banners')
                //         ->label('View Banners')
                //         ->icon('heroicon-m-photo')
                //         ->url(fn(Category $record): string => ContentResource::getUrl('index', [
                //             'tableFilters[banner_category_id][value]' => $record->id,
                //         ]))
                //         ->openUrlInNewTab(),
                //     Tables\Actions\Action::make('clone')
                //         ->label('Clone Category')
                //         ->icon('heroicon-m-document-duplicate')
                //         ->requiresConfirmation()
                //         ->action(function (Category $record) {
                //             // Get only the fillable attributes
                //             $attributes = $record->only($record->getFillable());

                //             // Create a new instance and fill it with the attributes
                //             $clone = new Category($attributes);

                //             // Set the new name and slug
                //             $clone->name = "{$record->name} (Clone)";
                //             $clone->slug = Str::slug($clone->name);

                //             // Set the creator/updater
                //             $clone->created_by = auth()->id();
                //             $clone->updated_by = auth()->id();

                //             // Save the clone
                //             $clone->save();

                //             // Redirect to the edit page of the new clone
                //             return redirect()->route('filament.admin.resources.banner.categories.edit', ['record' => $clone->id]);
                //         }),
                // ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Trash selected')
                        ->visible(fn($livewire): bool => $livewire->activeTab !== 'trashed'),
                    Tables\Actions\RestoreBulkAction::make()
                        ->color('success')
                        ->visible(fn($livewire): bool => $livewire->activeTab === 'trashed'),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn($livewire): bool => $livewire->activeTab === 'trashed'),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Set Active')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn(Category $records) => $records->each->update(['is_active' => true]))
                        ->visible(fn($livewire): bool => $livewire->activeTab !== 'trashed'),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Set Inactive')
                        ->icon('heroicon-m-x-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(fn(Category $records) => $records->each->update(['is_active' => false]))
                        ->visible(fn($livewire): bool => $livewire->activeTab !== 'trashed'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\BannersRelationManager::class,
            RelationManagers\ChildrenRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
            'view' => Pages\ViewCategory::route('/{record}'),
        ];
    }

    public static function getLabel(): string
    {
        return 'Content Category';
    }

    public static function getNavigationGroup(): ?string
    {
        return __("menu.nav_group.banner");
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('banners');
    }

    // public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    // {
    //     return $record->name;
    // }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Author' => $record->creator->fullname,
        ];
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return ContentCategoryResource::getUrl('index', ['tableSearch' => $record->name]);
    }
}
