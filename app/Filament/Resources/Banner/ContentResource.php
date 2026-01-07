<?php

namespace App\Filament\Resources\Banner;

use App\Filament\Resources\Banner\ContentResource\Pages;
use App\Models\Banner\Category;
use App\Models\Banner\Content;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Enums\ActionsPosition;

class ContentResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Content::class;
    protected static ?string $slug = 'banner/contents';
    protected static int $globalSearchResultsLimit = 10;
    protected static ?int $navigationSort = -2;
    protected static ?string $navigationIcon = 'fluentui-image-multiple-20-o';
    protected static ?string $recordTitleAttribute = 'title';
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
            'reorder',
        ];
    }

    protected static function getLastSortValue(): int
    {
        return Content::max('sort') ?? 0;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Banner Details')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('General')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\Section::make('Main Details')
                                    ->description('Fill out the main details of the banner')
                                    ->icon('heroicon-o-clipboard')
                                    ->schema([
                                        Forms\Components\Select::make('banner_category_id')
                                            ->label('Category')
                                            ->relationship('category', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->createOptionForm([
                                                Forms\Components\TextInput::make('name')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(fn($state, Forms\Set $set) => $set('slug', Str::slug($state))),
                                                Forms\Components\TextInput::make('slug')
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->unique(Category::class, 'slug', ignoreRecord: true)
                                                    ->helperText('URL-friendly version of the title - generated automatically')
                                                    ->suffixAction(
                                                        Forms\Components\Actions\Action::make('editSlug')
                                                            ->modal()
                                                            ->icon('heroicon-o-pencil-square')
                                                            ->modalHeading('Edit Slug')
                                                            ->modalDescription('Customize the URL slug for this Category. Use lowercase letters, numbers, and hyphens only.')
                                                            ->modalIcon('heroicon-o-link')
                                                            ->modalSubmitActionLabel('Update Slug')
                                                            ->form([
                                                                Forms\Components\TextInput::make('new_slug')
                                                                    ->hiddenLabel()
                                                                    ->required()
                                                                    ->maxLength(255)
                                                                    // ->live(debounce: 500)
                                                                    // ->afterStateUpdated(function (string $state, Forms\Set $set) {
                                                                    //     $set('new_slug', Str::slug($state));
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
                                                    ),
                                                // Forms\Components\Toggle::make('is_active')
                                                //     ->label('Active')
                                                //     ->default(true),
                                            ])
                                            ->required(),
                                        Forms\Components\Section::make('Status')
                                            ->description('Configure the active status of this banner')
                                            ->schema([
                                                Forms\Components\Toggle::make('is_active')
                                                    ->label('Active')
                                                    ->helperText('Control banner visibility')
                                                    ->default(true),
                                            ])
                                            ->collapsible(),
                                        Forms\Components\TextInput::make('title')
                                            ->label('Title')
                                            ->maxLength(255)
                                            ->columnSpan(2),
                                        Forms\Components\MarkdownEditor::make('description')
                                            ->label('Description')
                                            ->helperText('Provide a description for the banner')
                                            ->maxLength(500)
                                            ->columnSpanFull(),
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
                                    ])
                                    ->compact()
                                    ->columns(2),
                            ]),
                        Forms\Components\Tabs\Tab::make('Banner Image')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                Forms\Components\Section::make('Image')
                                    ->description('Upload banner image here')
                                    ->schema([
                                        SpatieMediaLibraryFileUpload::make('banners')
                                            ->collection('banners')
                                            ->image()
                                            ->maxSize(1024)
                                            ->multiple(false)
                                            ->maxFiles(1)
                                            ->imagePreviewHeight('250')
                                            ->panelLayout('compact')
                                            ->imageResizeMode('cover')
                                            ->imageResizeTargetWidth('1200')
                                            ->imageResizeTargetHeight('800')
                                            ->helperText('Upload a banner image. Recommended size: 1200x800px')
                                            ->columnSpanFull(),
                                    ])
                                    ->compact(),
                            ]),
                        Forms\Components\Tabs\Tab::make('Scheduling')
                            ->icon('heroicon-o-calendar')
                            ->schema([
                                Forms\Components\Section::make('Schedule')
                                    ->description('Set the scheduling details for the banner')
                                    ->schema([
                                        Forms\Components\DateTimePicker::make('start_date')
                                            ->label('Start Date')
                                            ->helperText('Select the start date and time')
                                            ->nullable(),
                                        Forms\Components\DateTimePicker::make('end_date')
                                            ->label('End Date')
                                            ->helperText('Select the end date and time')
                                            ->nullable()
                                            ->after('start_date'),
                                        Forms\Components\DateTimePicker::make('published_at')
                                            ->label('Publish Date')
                                            ->helperText('When should this banner be published?')
                                            ->nullable(),
                                    ])
                                    ->compact()
                                    ->columns(2),
                            ]),
                        Forms\Components\Tabs\Tab::make('Link & Tracking')
                            ->icon('heroicon-o-link')
                            ->schema([
                                Forms\Components\Section::make('Click Settings')
                                    ->description('Configure link and tracking options')
                                    ->schema([
                                        Forms\Components\TextInput::make('click_url')
                                            ->label('Click URL')
                                            ->helperText('Enter the URL to navigate to when the banner is clicked')
                                            ->url()
                                            ->maxLength(255),
                                        Forms\Components\Select::make('click_url_target')
                                            ->label('Click URL Target')
                                            ->helperText('Select how the URL should be opened')
                                            ->options([
                                                '_blank' => 'New Tab',
                                                '_self' => 'Current Tab',
                                            ])
                                            ->default('_self')
                                            ->native(false),
                                    ])
                                    ->compact()
                                    ->columns(2),
                                Forms\Components\Section::make('Tracking')
                                    ->description('Banner tracking statistics')
                                    ->schema([
                                        Forms\Components\Placeholder::make('impression_count')
                                            ->label('Impressions')
                                            ->content(fn(Content $record): string => number_format($record->impression_count ?? 0)),
                                        Forms\Components\Placeholder::make('click_count')
                                            ->label('Clicks')
                                            ->content(fn(Content $record): string => number_format($record->click_count ?? 0)),
                                        Forms\Components\Placeholder::make('ctr')
                                            ->label('CTR (Click Through Rate)')
                                            ->content(function (Content $record): string {
                                                if (($record->impression_count ?? 0) > 0) {
                                                    $ctr = ($record->click_count / $record->impression_count) * 100;
                                                    return number_format($ctr, 2) . '%';
                                                }
                                                return '0.00%';
                                            }),
                                    ])
                                    ->compact()
                                    ->columns(3)
                                    ->visible(fn(?Content $record) => $record !== null),
                            ]),
                        Forms\Components\Tabs\Tab::make('Advanced Settings')
                            ->icon('heroicon-o-cog')
                            ->schema([
                                Forms\Components\Section::make('Settings')
                                    ->description('Additional settings for the banner')
                                    ->schema([
                                        Forms\Components\TextInput::make('sort')
                                            ->label('Sort Order')
                                            ->helperText('Set the sort order of the banner')
                                            ->required()
                                            ->numeric()
                                            ->default(static::getLastSortValue() + 1),
                                        Forms\Components\KeyValue::make('options')
                                            ->keyLabel('Option Name')
                                            ->valueLabel('Option Value')
                                            ->helperText('Custom JSON options for this banner')
                                            ->addable()
                                            ->live()
                                            ->reorderable()
                                            ->columnSpanFull(),
                                    ])
                                    ->compact(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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
                    ->description(fn(Content $record): string => Str::limit(strip_tags($record->description), 100))
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->extraHeaderAttributes([
                        'class' => 'min-w-80'
                    ]),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('impression_count')
                    ->label('Views')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('click_count')
                    ->label('Clicks')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('locale')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
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
                Tables\Filters\SelectFilter::make('banner_category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
                Tables\Filters\SelectFilter::make('locale')
                    ->options([
                        'en' => 'English',
                        'id' => 'Indonesian',
                        'zh' => 'Chinese',
                        'ja' => 'Japanese',
                    ]),
                Tables\Filters\Filter::make('date_range')
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
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['from'] ?? null) {
                            $indicators['from'] = 'Active from ' . $data['from']->format('M j, Y');
                        }

                        if ($data['until'] ?? null) {
                            $indicators['until'] = 'Active until ' . $data['until']->format('M j, Y');
                        }

                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    // Tables\Actions\ViewAction::make(),
                    Tables\Actions\ViewAction::make()
                        ->url(fn(Content $content, $livewire): string =>
                        ContentResource::getUrl('view', [
                            'record' => $content,
                            'page' => $livewire->getPage(),
                            'activeTab' => $livewire->activeTab,
                            'tableFilters' => $livewire->tableFilters,
                            'tableSearch' => $livewire->tableSearch
                        ])),
                    Tables\Actions\EditAction::make()->color('primary')->hidden(fn(Content $content): bool => $content->trashed()),
                    Tables\Actions\DeleteAction::make()->label('Trash'),
                    Tables\Actions\ForceDeleteAction::make(),
                    Tables\Actions\RestoreAction::make()->color('success')
                ])->dropdownPlacement('down-start'),
                // Tables\Actions\ActionGroup::make([
                //     Tables\Actions\Action::make('preview')
                //         ->label('Preview Banner')
                //         ->icon('heroicon-m-eye')
                //         ->url(fn(Content $record) => $record->getImageUrl('large'))
                //         ->openUrlInNewTab(),
                //     Tables\Actions\Action::make('clone')
                //         ->label('Clone Banner')
                //         ->icon('heroicon-m-document-duplicate')
                //         ->requiresConfirmation()
                //         ->action(function (Content $record) {
                //             // Get only the fillable attributes
                //             $attributes = $record->only($record->getFillable());

                //             // Create a new instance and fill it with the attributes
                //             $clone = new Content($attributes);

                //             // Set the new title
                //             $clone->title = "{$record->title} (Clone)";

                //             // Update the sort value
                //             $clone->sort = static::getLastSortValue() + 1;

                //             // Set the creator/updater
                //             $clone->created_by = auth()->id();
                //             $clone->updated_by = auth()->id();

                //             // Reset counters
                //             $clone->impression_count = 0;
                //             $clone->click_count = 0;

                //             // Save the clone
                //             $clone->save();

                //             // If the original has media, copy it to the clone
                //             if ($record->hasMedia('banners')) {
                //                 $media = $record->getFirstMedia('banners');
                //                 $media->copy($clone, 'banners');
                //             }

                //             // Redirect to the edit page of the new clone
                //             return redirect()->route('filament.admin.resources.banner.contents.edit', ['record' => $clone->id]);
                //         }),
                //    Tables\Actions\DeleteAction::make()->hiddenLabel()->tooltip('Delete'),
                // ]),
            ], position: ActionsPosition::BeforeCells)
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
                        ->action(fn(\Illuminate\Database\Eloquent\Collection $records) => $records->each->update(['is_active' => true]))
                        ->hidden(fn($livewire): bool => $livewire->activeTab === 'trashed'),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Set Inactive')
                        ->icon('heroicon-m-x-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(fn(\Illuminate\Database\Eloquent\Collection $records) => $records->each->update(['is_active' => false]))
                        ->hidden(fn($livewire): bool => $livewire->activeTab === 'trashed'),
                ]),
            ])
            ->defaultSort('sort', 'asc')
            ->reorderable('sort');
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
            'index' => Pages\ListContents::route('/'),
            'create' => Pages\CreateContent::route('/create'),
            'edit' => Pages\EditContent::route('/{record}/edit'),
            'view' => Pages\ViewContent::route('/{record}'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __("menu.nav_group.banner");
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'gray';
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['category']);
    }

    // public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    // {
    //     return $record->title;
    // }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'category.name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Category' => $record->category->name,
            'Status' => $record->is_active ? 'Active' : 'Inactive',
        ];
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return ContentResource::getUrl('index', ['tableSearch' => $record->title]);
    }
}
