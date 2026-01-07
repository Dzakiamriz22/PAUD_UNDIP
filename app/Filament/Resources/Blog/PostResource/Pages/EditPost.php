<?php

namespace App\Filament\Resources\Blog\PostResource\Pages;

use App\Enums\Blog\PostStatus;
use App\Filament\Resources\Blog\PostResource;
use App\Models\Blog\Post;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;
use Livewire\Features\SupportRedirects\Redirector;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Livewire\Attributes\Url;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    #[Url]
    public ?string $page = null;

    #[Url]
    public ?string $activeTab = null;

    #[Url]
    public ?array $tableFilters = null;

    #[Url]
    public ?string $tableSearch = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ActionGroup::make([
                // Publication status actions
                Actions\Action::make('publish')
                    ->label('Publish Now')
                    ->form([
                        Forms\Components\Toggle::make('notify_subscribers')
                            ->label('Notify subscribers')
                            ->helperText('Send an email notification to all blog subscribers')
                            ->default(false),
                    ])
                    ->action(function () {
                        $this->record->update([
                            'status' => PostStatus::PUBLISHED,
                            'published_at' => now(),
                            'last_published_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Post published successfully!')
                            ->success()
                            ->send();

                        $this->refreshFormData([
                            'status',
                            'published_at',
                            'last_published_at'
                        ]);
                    })
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn() => $this->record->status !== PostStatus::PUBLISHED),

                Actions\Action::make('schedule')
                    ->label('Schedule Publication')
                    ->form([
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('Publication Date & Time')
                            ->seconds(false)
                            ->timezone('UTC')
                            ->required()
                            ->default(now()->addDay()->startOfHour()),

                        Forms\Components\Toggle::make('notify_subscribers')
                            ->label('Notify subscribers when published')
                            ->helperText('Send an email notification to all blog subscribers')
                            ->default(false),
                    ])
                    ->action(function (array $data) {
                        $this->record->update([
                            'status' => PostStatus::PENDING,
                            'scheduled_at' => $data['scheduled_at'],
                        ]);

                        Notification::make()
                            ->title('Post scheduled for publication')
                            ->body('It will be automatically published on ' . $data['scheduled_at']->format('M d, Y \a\t h:i A'))
                            ->success()
                            ->send();

                        $this->refreshFormData([
                            'status',
                            'scheduled_at',
                        ]);
                    })
                    ->icon('heroicon-m-clock')
                    ->color('warning')
                    ->visible(fn() => $this->record->status !== PostStatus::PUBLISHED),

                Actions\Action::make('unpublish')
                    ->label('Unpublish')
                    ->requiresConfirmation()
                    ->action(function () {
                        $this->record->update([
                            'status' => PostStatus::DRAFT,
                            'scheduled_at' => null,
                        ]);

                        Notification::make()
                            ->title('Post unpublished')
                            ->body('Post has been moved to drafts')
                            ->success()
                            ->send();

                        $this->refreshFormData([
                            'status',
                            'scheduled_at',
                        ]);
                    })
                    ->icon('heroicon-m-archive-box')
                    ->color('danger')
                    ->visible(fn() => $this->record->status === PostStatus::PUBLISHED || $this->record->status === PostStatus::PENDING),

                // Featuring action
                Actions\Action::make('toggle_featured')
                    ->label(fn() => $this->record->is_featured ? 'Remove Featured' : 'Mark as Featured')
                    ->action(function () {
                        $newValue = !$this->record->is_featured;

                        $this->record->update([
                            'is_featured' => $newValue,
                        ]);

                        $status = $newValue ? 'featured' : 'unfeatured';

                        Notification::make()
                            ->title("Post {$status}")
                            ->success()
                            ->send();

                        $this->refreshFormData(['is_featured']);
                    })
                    ->icon(fn() => $this->record->is_featured ? 'heroicon-m-x-mark' : 'heroicon-m-star')
                    ->color(fn() => $this->record->is_featured ? 'info' : 'info'),

                // Duplication action
                // Actions\Action::make('duplicate')
                //     ->label('Duplicate Post')
                //     ->form([
                //         Forms\Components\TextInput::make('title')
                //             ->label('New Title')
                //             ->default(fn() => "Copy of {$this->record->title}")
                //             ->required()
                //             ->maxLength(255)
                //             ->live(debounce: 500)
                //             ->afterStateUpdated(function (string $state, Set $set) {
                //                 $set('slug', Str::slug($state));
                //             }),

                //         Forms\Components\TextInput::make('slug')
                //             ->label('New Slug')
                //             ->default(fn() => Str::slug("Copy of {$this->record->title}"))
                //             ->required()
                //             ->maxLength(255)
                //             ->unique('blog_posts', 'slug'),

                //         Forms\Components\Toggle::make('copy_media')
                //             ->label('Copy Images')
                //             ->helperText('Include featured and gallery images')
                //             ->default(true),

                //         Forms\Components\Toggle::make('copy_tags')
                //             ->label('Copy Tags')
                //             ->default(true),

                //         Forms\Components\Toggle::make('edit_after')
                //             ->label('Edit after duplication')
                //             ->default(true),
                //     ])
                //     ->action(function (array $data): ?Redirector {
                //         $newPost = $this->record->replicate();
                //         $newPost->title = $data['title'];
                //         $newPost->slug = $data['slug'];
                //         $newPost->published_at = null;
                //         $newPost->status = PostStatus::DRAFT;
                //         $newPost->view_count = 0;
                //         $newPost->comments_count = 0;
                //         $newPost->save();

                //         // Copy tags if requested
                //         if ($data['copy_tags']) {
                //             $newPost->syncTags($this->record->tags->pluck('name')->toArray());
                //         }

                //         // Copy media if requested
                //         if ($data['copy_media']) {
                //             foreach ($this->record->getMedia('featured') as $media) {
                //                 $media->copy($newPost, 'featured');
                //             }

                //             foreach ($this->record->getMedia('gallery') as $media) {
                //                 $media->copy($newPost, 'gallery');
                //             }
                //         }

                //         Notification::make()
                //             ->title('Post duplicated successfully')
                //             ->success()
                //             ->send();

                //         if ($data['edit_after']) {
                //             return $this->redirect(route('filament.admin.resources.blog.posts.edit', ['record' => $newPost->id]));
                //         }

                //         return null;
                //     })
                //     ->icon('heroicon-m-document-duplicate')
                //     ->color('info'),

                // Danger zone actions
                // Actions\DeleteAction::make()
                //     ->label('Move to Trash')
                //     ->successNotificationTitle('Post moved to trash'),

                // Actions\ForceDeleteAction::make()
                //     ->label('Permanent Delete')
                //     ->modalDescription('This action cannot be undone. This will permanently delete the post from the server.'),

                // Actions\RestoreAction::make()
                //     ->label('Restore from Trash')
                //     ->successNotificationTitle('Post restored from trash'),
            ])
            ->label('Post Actions')
            ->icon('heroicon-m-cog-6-tooth')
            ->color('primary'), // Make dropdown wider
        ];
    }

    // Add view actions as a separate action group, Unused function right now
    // protected function getActions(): array
    // {
    //     return [
    //         Actions\ActionGroup::make([
    //             Actions\ViewAction::make()
    //                 ->label('View Details'),

    //             Actions\Action::make('view_on_site')
    //                 ->label('View on Website')
    //                 ->url(fn() => $this->record->getUrl())
    //                 ->icon('heroicon-o-globe-alt')
    //                 ->visible(fn() => $this->record->status === PostStatus::PUBLISHED)
    //                 ->openUrlInNewTab(),

    //             Actions\Action::make('preview')
    //                 ->label('Preview Draft')
    //                 ->url(fn() => route('blog.preview', ['id' => $this->record->id, 'token' => hash('sha256', $this->record->id . config('app.key'))]))
    //                 ->icon('heroicon-o-eye')
    //                 ->openUrlInNewTab(),
    //         ])
    //             ->label('View Post')
    //             ->icon('heroicon-m-eye')
    //             ->color('gray'),
    //     ];
    // }

    public function getFormActions(): array
    {
        return [
            // ...parent::getFormActions(),
            parent::getSaveFormAction()
                ->label('Save Changes')
                ->color('success')
                ->icon('heroicon-o-check-circle'),
            Actions\Action::make('create')
                ->label('Create Another')
                ->color('primary')
                ->icon('heroicon-o-plus-circle')
                ->authorize('create', static::getModel())
                ->url(fn (): string => static::getResource()::getUrl('create')),
            Actions\Action::make('list')
                ->label('Back')
                ->color('gray')
                ->icon('heroicon-o-chevron-left')
                // ->alpineClickHandler('document.referrer ? window.history.back() : (window.location.href = ' . \Illuminate\Support\Js::from($this->previousUrl ?? static::getResource()::getUrl('index')) . ')'),
                ->url(fn (): string => static::getResource()::getUrl('index', ['page' => $this->page, 'activeTab' => $this->activeTab, 'tableFilters' => $this->tableFilters, 'tableSearch' => $this->tableSearch])),
            Actions\DeleteAction::make()->label('Trash')->icon('heroicon-o-trash'),
            Actions\ReplicateAction::make()
                ->label('Replicate Record')
                ->color('info')
                ->icon('heroicon-o-document-duplicate')
                ->mutateRecordDataUsing(function (array $data): array {
                    $data['title'] = "Copy of {$this->record->title}";
                    $data['slug'] = Str::slug("Copy of {$this->record->title}");
                    $data['copy_media'] = true;
                    $data['copy_tags'] = true;

                    return $data;
                })
                ->form([
                    Forms\Components\TextInput::make('title')
                        ->label('New Title')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Enter post title')
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn($state, Forms\Set $set) => $set('slug', Str::slug($state))),

                    Forms\Components\TextInput::make('slug')
                        ->label('New Slug')
                        ->disabled()
                        ->dehydrated()
                        ->required()
                        ->maxLength(255)
                        ->unique(Post::class, 'slug', ignoreRecord: true),

                    Forms\Components\Toggle::make('copy_media')
                        ->label('Copy Images')
                        ->helperText('Include featured and gallery images'),

                    Forms\Components\Toggle::make('copy_tags')
                        ->label('Copy Tags')
                        ->helperText('Include all tags from the original post'),
                ])
                ->beforeReplicaSaved(function (Post $replica, array $data): void {
                    $replica->is_featured = false;
                    $replica->published_at = null;
                    $replica->status = PostStatus::DRAFT;
                    $replica->view_count = 0;
                    $replica->comments_count = 0;
                })
                ->after(function (Post $replica, array $data): void {
                    if ($data['copy_tags']) {
                        $replica->syncTags($this->record->tags->pluck('name')->toArray());
                    }

                    // Copy media if requested
                    if ($data['copy_media']) {
                        foreach ($this->record->getMedia('featured') as $media) {
                            $media->copy($replica, 'featured');
                        }

                        foreach ($this->record->getMedia('gallery') as $media) {
                            $media->copy($replica, 'gallery');
                        }
                    }
                })
                ->successRedirectUrl(fn (Post $replica): string => route('filament.admin.resources.blog.posts.edit', ['record' => $replica]))
                ->successNotification(
                    Notification::make()
                            ->success()
                            ->title('Post replicated')
                            ->body('The post has been replicated successfully.'),
                ),
            // Actions\Action::make('view_on_site')
            //     ->color('warning')
            //     ->label('Show Live Post')
            //     ->url(fn () => $this->record->getUrl())
            //     ->icon('heroicon-o-globe-alt')
            //     ->openUrlInNewTab()
            //     ->visible(fn () => $this->record->is_published),
        ];
    }

    // protected function getRedirectUrl(): ?string
    // {
    //     return $this->getResource()::getUrl('index');
    // }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Post updated')
            ->body('Changes have been saved successfully.');
    }

    protected function beforeSave(): void
    {
        // If the post is being published for the first time
        if (
            $this->record->isDirty('status') &&
            $this->record->status === PostStatus::PUBLISHED &&
            $this->record->published_at === null
        ) {
            $this->record->published_at = now();
            $this->record->last_published_at = now();
        }

        if (
            $this->record->isDirty('status') &&
            $this->record->status === PostStatus::PENDING &&
            $this->record->scheduled_at === null
        ) {
            $this->record->scheduled_at = now()->addDay()->startOfHour();
        }
    }
}
