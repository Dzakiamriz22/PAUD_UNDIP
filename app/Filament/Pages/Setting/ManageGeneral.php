<?php

namespace App\Filament\Pages\Setting;

use App\Services\FileService;
use App\Settings\GeneralSettings;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;
use Filament\Support\Facades\FilamentView;
use Illuminate\Contracts\Support\Htmlable;
use Riodwanto\FilamentAceEditor\AceEditor;
use Filament\Notifications\Actions\Action;

use function Filament\Support\is_app_url;

class ManageGeneral extends SettingsPage
{
    use HasPageShield;
    protected static string $settings = GeneralSettings::class;

    // protected static ?int $navigationSort = 99;
    protected static ?string $navigationIcon = 'fluentui-settings-20-o';

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public string $themePath = '';

    public string $twConfigPath = '';

    public function mount(): void
    {
        $this->themePath = resource_path('css/filament/admin/theme.css');
        $this->twConfigPath = resource_path('css/filament/admin/tailwind.config.js');

        $this->fillForm();
    }

    protected function fillForm(): void
    {
        $settings = app(static::getSettings());

        $data = $this->mutateFormDataBeforeFill($settings->toArray());

        $fileService = new FileService;

        $data['theme-editor'] = $fileService->readfile($this->themePath);

        $data['tw-config-editor'] = $fileService->readfile($this->twConfigPath);

        $this->form->fill($data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Site')
                    ->label(fn() => __('page.general_settings.sections.site'))
                    ->description(fn() => __('page.general_settings.sections.site.description'))
                    ->icon('fluentui-web-asset-24-o')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Grid::make()->schema([
                            Forms\Components\TextInput::make('brand_name')
                                ->label(fn() => __('page.general_settings.fields.brand_name'))
                                ->required(),

                            Forms\Components\Toggle::make('search_engine_indexing')
                                ->label('Search Engine Indexing')
                                ->helperText('When disabled, search engines will be instructed not to index the site')
                                ->default(true),

                            Forms\Components\TextInput::make('application_code')
                                ->label('Application Code')
                                ->required(),

                            Forms\Components\TextInput::make('application_owner')
                                ->label('Application Owner')
                                ->required(),
                        ]),
                    ]),

                Forms\Components\Section::make('Branding')
                    ->label('Branding & Visuals')
                    ->description('Customize the visual identity of your application')
                    ->icon('heroicon-o-photo')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Grid::make()->schema([
                            Forms\Components\TextInput::make('brand_logoHeight')
                                ->label(fn() => __('page.general_settings.fields.brand_logoHeight'))
                                ->numeric()
                                ->suffix('px')
                                ->required()
                                ->helperText('Best size is 50px'),
                        ])->columnSpan(3),

                        Forms\Components\Grid::make()->schema([
                            Forms\Components\FileUpload::make('brand_logo')
                                ->label(fn() => __('page.general_settings.fields.brand_logo'))
                                ->image()
                                ->maxSize(1024)
                                ->directory('sites')
                                ->visibility('public')
                                ->moveFiles()
                                ->imagePreviewHeight('100')
                                ->imageCropAspectRatio('4:1')
                                // ->imageResizeTargetWidth('300')
                                // ->imageResizeTargetHeight('75')
                                ->helperText('Upload your site logo, Recommended size: 400x100 pixels'),

                            Forms\Components\FileUpload::make('brand_logo_dark')
                                ->label(fn() => __('page.general_settings.fields.brand_logo_dark'))
                                ->image()
                                ->maxSize(1024)
                                ->directory('sites')
                                ->visibility('public')
                                ->moveFiles()
                                ->imagePreviewHeight('100')
                                ->imageCropAspectRatio('4:1')
                                // ->imageResizeTargetWidth('300')
                                // ->imageResizeTargetHeight('75')
                                ->helperText('Upload your site logo dark, Recommended size: 400x100 pixels'),

                            Forms\Components\FileUpload::make('brand_logo_square')
                                ->label(fn() => __('page.general_settings.fields.brand_logo_square'))
                                ->image()
                                ->maxSize(1024)
                                ->directory('sites')
                                ->visibility('public')
                                ->moveFiles()
                                ->imagePreviewHeight('100')
                                ->imageCropAspectRatio('1:1')
                                ->helperText('Upload your site logo, Recommended size: 300x300 pixels'),

                            // Forms\Components\FileUpload::make('stamp_image')
                            //     ->label(fn() => __('page.general_settings.fields.stamp_image'))
                            //     ->image()
                            //     ->maxSize(1024)
                            //     ->directory('sites')
                            //     ->visibility('public')
                            //     ->moveFiles()
                            //     ->imagePreviewHeight('100')
                            //     ->helperText('Upload your site logo, Recommended size: 550x360 pixels'),

                            Forms\Components\FileUpload::make('site_favicon')
                                ->label(fn() => __('page.general_settings.fields.site_favicon'))
                                ->image()
                                ->maxSize(1024)
                                ->directory('sites')
                                ->visibility('public')
                                ->moveFiles()
                                ->acceptedFileTypes(['image/x-icon', 'image/vnd.microsoft.icon', 'image/png', 'image/jpeg'])
                                ->helperText('Supports .ico, .png, and .jpg formats (optional)'),

                            Forms\Components\FileUpload::make('login_cover_image')
                                ->label(fn() => __('page.general_settings.fields.login_cover_image'))
                                ->image()
                                ->maxSize(1024)
                                ->directory('sites')
                                ->visibility('public')
                                ->moveFiles()
                                ->imagePreviewHeight('100')
                                ->helperText('Upload your site logo .jpeg and .jpg, Recommended size: 1620x1080 pixels'),
                        ])->columns(2)->columnSpan(3),
                    ])->columns(3),

                Forms\Components\Section::make('tanda_tangan_pejabat')
                    ->label('Tanda Tangan Pejabat')
                    ->description('Upload tanda tangan pejabat')
                    ->icon('heroicon-o-photo')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Grid::make()->schema([
                            Forms\Components\FileUpload::make('stamp_image_dekan')
                                ->image()
                                ->maxSize(1024)
                                ->directory('sites')
                                ->visibility('public')
                                ->moveFiles()
                                ->imagePreviewHeight('100')
                                ->helperText('Upload tanda tangan dekan, Recommended size: 550x360 pixels'),
                            Forms\Components\FileUpload::make('stamp_image_wd1')
                                ->image()
                                ->maxSize(1024)
                                ->directory('sites')
                                ->visibility('public')
                                ->moveFiles()
                                ->imagePreviewHeight('100')
                                ->helperText('Upload tanda tangan wd1, Recommended size: 550x360 pixels'),
                            Forms\Components\FileUpload::make('stamp_image_wd2')
                                ->image()
                                ->maxSize(1024)
                                ->directory('sites')
                                ->visibility('public')
                                ->moveFiles()
                                ->imagePreviewHeight('100')
                                ->helperText('Upload tanda tangan wd2, Recommended size: 550x360 pixels'),
                            Forms\Components\FileUpload::make('stamp_image_manager')
                                ->image()
                                ->maxSize(1024)
                                ->directory('sites')
                                ->visibility('public')
                                ->moveFiles()
                                ->imagePreviewHeight('100')
                                ->helperText('Upload tanda tangan manager, Recommended size: 550x360 pixels'),
                            Forms\Components\FileUpload::make('stamp_image_spv1')
                                ->image()
                                ->maxSize(1024)
                                ->directory('sites')
                                ->visibility('public')
                                ->moveFiles()
                                ->imagePreviewHeight('100')
                                ->helperText('Upload tanda tangan spv1, Recommended size: 550x360 pixels'),
                            Forms\Components\FileUpload::make('stamp_image_spv2')
                                ->image()
                                ->maxSize(1024)
                                ->directory('sites')
                                ->visibility('public')
                                ->moveFiles()
                                ->imagePreviewHeight('100')
                                ->helperText('Upload tanda tangan spv2, Recommended size: 550x360 pixels'),
                        ])->columns(2)->columnSpan(3)
                    ]),

                // Forms\Components\Section::make('Invoice Settings')
                //     ->label('Invoice Configuration')
                //     ->description('Configure invoice number format and application details')
                //     ->icon('heroicon-o-document-text')
                //     ->collapsible()
                //     ->schema([
                //         Forms\Components\Grid::make()->schema([

                //             Forms\Components\TextInput::make('invoice_number_format')
                //                 ->label('Invoice Number Format')
                //                 ->required()
                //                 ->helperText('Format: {seq}/{owner}/{code}/INV/{month}/{year}')
                //                 ->placeholder('{seq}/{owner}/{code}/INV/{month}/{year}'),
                //         ])->columns(1),

                //         Forms\Components\Placeholder::make('invoice_preview')
                //             ->label('Preview')
                //             ->content(function ($get) {
                //                 $seq = 1;
                //                 $month = now()->format('n');
                //                 $year = now()->format('Y');

                //                 // Mapping bulan ke angka Romawi
                //                 $romanMonths = [
                //                     1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
                //                     7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
                //                 ];

                //                 $romanMonth = $romanMonths[(int) $month];

                //                 $format = $get('invoice_number_format') ?? '{seq}/{owner}/{code}/INV/{month}/{year}';
                //                 $owner = $get('application_owner') ?? 'BP UBIKAR';
                //                 $code = $get('application_code') ?? 'A1.01';

                //                 $preview = str_replace(
                //                     ['{seq}', '{owner}', '{code}', '{month}', '{year}'],
                //                     [$seq, $owner, $code, $romanMonth, $year],
                //                     $format
                //                 );

                //                 return "**Preview:** `{$preview}`";
                //             })
                //             // ->markdown(),
                //     ]),

                Forms\Components\Tabs::make('Tabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Color Palette')
                            ->icon('heroicon-o-swatch')
                            ->schema([
                                Forms\Components\Section::make('Theme Colors')
                                    ->description('Customize your admin panel color scheme')
                                    ->compact()
                                    ->schema([
                                        Forms\Components\ColorPicker::make('site_theme.primary')
                                            ->label(fn() => __('page.general_settings.fields.primary'))
                                            ->helperText('Used for primary buttons and links'),
                                        Forms\Components\ColorPicker::make('site_theme.secondary')
                                            ->label(fn() => __('page.general_settings.fields.secondary'))
                                            ->helperText('Used for secondary elements'),
                                        Forms\Components\ColorPicker::make('site_theme.gray')
                                            ->label(fn() => __('page.general_settings.fields.gray'))
                                            ->helperText('Used for neutral backgrounds and text'),
                                    ])->columns(3),
                                Forms\Components\Section::make('Status Colors')
                                    ->description('Define colors for different states and notifications')
                                    ->compact()
                                    ->schema([
                                        Forms\Components\ColorPicker::make('site_theme.success')
                                            ->label(fn() => __('page.general_settings.fields.success'))
                                            ->helperText('Used for success states and confirmations'),
                                        Forms\Components\ColorPicker::make('site_theme.danger')
                                            ->label(fn() => __('page.general_settings.fields.danger'))
                                            ->helperText('Used for errors and dangerous actions'),
                                        Forms\Components\ColorPicker::make('site_theme.info')
                                            ->label(fn() => __('page.general_settings.fields.info'))
                                            ->helperText('Used for informational notifications'),
                                        Forms\Components\ColorPicker::make('site_theme.warning')
                                            ->label(fn() => __('page.general_settings.fields.warning'))
                                            ->helperText('Used for warnings and cautions'),
                                    ])->columns(2),
                            ]),
                        // Forms\Components\Tabs\Tab::make('Code Editor')
                        //     ->icon('heroicon-o-code-bracket')
                        //     ->schema([
                        //         Forms\Components\Grid::make()->schema([
                        //             AceEditor::make('theme-editor')
                        //                 ->label('theme.css')
                        //                 ->mode('css')
                        //                 ->height('24rem')
                        //                 ->helperText('Edit the CSS theme directly (changes will be applied after saving)'),
                        //             AceEditor::make('tw-config-editor')
                        //                 ->label('tailwind.config.js')
                        //                 ->mode('javascript')
                        //                 ->height('24rem')
                        //                 ->helperText('Edit the Tailwind configuration (changes will be applied after saving)'),
                        //         ])->columns(1)
                        //     ]),
                    ])
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ])
            ->columns(3)
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $data = $this->mutateFormDataBeforeSave($this->form->getState());

            $settings = app(static::getSettings());

            $settings->fill($data);
            $settings->save();

            // $fileService = new FileService;
            // $fileService->writeFile($this->themePath, $data['theme-editor']);
            // $fileService->writeFile($this->twConfigPath, $data['tw-config-editor']);

            Notification::make()
                ->title('Settings updated successfully!')
                ->body('Your changes have been saved, please reload the page to apply them.')
                ->success()
                ->actions([
                    Action::make('reload')
                        ->button()
                        ->url(static::getUrl()),
                ])
                ->send();

            // $this->redirect(static::getUrl(), navigate: FilamentView::hasSpaMode() && is_app_url(static::getUrl()));
        } catch (\Throwable $th) {
            Notification::make()
                ->title('Error saving settings')
                ->body($th->getMessage())
                ->danger()
                ->send();

            throw $th;
        }
    }

    public static function getNavigationGroup(): ?string
    {
        return __("menu.nav_group.settings");
    }

    public static function getNavigationLabel(): string
    {
        return __("page.general_settings.navigationLabel");
    }

    public function getTitle(): string|Htmlable
    {
        return __("page.general_settings.title");
    }

    public function getHeading(): string|Htmlable
    {
        return __("page.general_settings.heading");
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __("page.general_settings.subheading");
    }

    public function getFormActions(): array
    {
        return [
            // ...parent::getFormActions(),
            parent::getSaveFormAction()
                ->label('Save Changes')
                ->color('success')
                ->icon('heroicon-o-check-circle'),
        ];
    }
}
