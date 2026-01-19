<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Filament\Resources\StudentResource\RelationManagers\ClassHistoriesRelationManager;
use App\Filament\Resources\StudentResource\RelationManagers\PaymentHistoryRelationManager;
use App\Models\Student;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Illuminate\Database\Eloquent\Builder;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Akademik';
    protected static ?string $navigationLabel = 'Siswa';
    protected static ?string $pluralLabel = 'Siswa';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (
            $user->isSuperAdmin()
            || $user->hasRole('admin')
            || $user->isKepsek()
            || $user->isBendahara()
        ) {
            return $query;
        }

        if ($user->isGuru()) {
            return $query->whereHas('classHistories', function ($q) use ($user) {
                $q->where('is_active', true)
                  ->whereHas('classRoom', function ($c) use ($user) {
                      $c->where('homeroom_teacher_id', $user->id);
                  });
            });
        }

        return $query->whereRaw('1 = 0');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Data Siswa')
                    ->schema([
                        TextInput::make('nis')
                            ->label('NIS')
                            ->required()
                            ->unique(ignoreRecord: true),

                        TextInput::make('name')
                            ->label('Nama Siswa')
                            ->required()
                            ->maxLength(255),

                        Select::make('gender')
                            ->label('Jenis Kelamin')
                            ->options([
                                'male' => 'Laki-laki',
                                'female' => 'Perempuan',
                            ])
                            ->required(),

                        DatePicker::make('birth_date')
                            ->label('Tanggal Lahir')
                            ->required(),

                        TextInput::make('parent_name')
                            ->label('Nama Orang Tua')
                            ->required(),
                        
                        TextInput::make('parent_contact')
                            ->label('Kontak Orang Tua')
                            ->placeholder('No HP / WA')
                            ->tel(),


                        Select::make('status')
                            ->options([
                                'active' => 'Aktif',
                                'inactive' => 'Nonaktif',
                                'graduated' => 'Lulus',
                            ])
                            ->default('active')
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nis')
                    ->label('NIS')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('activeClass.classRoom.code')
                    ->label('Kelas Aktif')
                    ->badge()
                    ->color('success'),

                BadgeColumn::make('gender')
                    ->label('JK')
                    ->colors([
                        'primary' => 'male',
                        'danger' => 'female',
                    ])
                    ->formatStateUsing(fn ($state) =>
                        $state === 'male' ? 'Laki-laki' : 'Perempuan'
                    ),

                BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'inactive',
                        'gray' => 'graduated',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'active' => 'Aktif',
                        'inactive' => 'Nonaktif',
                        'graduated' => 'Lulus',
                        default => $state,
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Aktif',
                        'inactive' => 'Nonaktif',
                        'graduated' => 'Lulus',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('Data Siswa')
                    ->schema([
                        TextEntry::make('nis')
                            ->label('NIS'),

                        TextEntry::make('name')
                            ->label('Nama Siswa'),

                        TextEntry::make('gender')
                            ->label('Jenis Kelamin')
                            ->formatStateUsing(fn ($state) =>
                                $state === 'male' ? 'Laki-laki' : 'Perempuan'
                            )
                            ->badge()
                            ->color(fn ($state) => $state === 'male' ? 'primary' : 'pink'),

                        TextEntry::make('birth_date')
                            ->label('Tanggal Lahir')
                            ->date('d/m/Y'),

                        TextEntry::make('parent_name')
                            ->label('Nama Orang Tua'),

                        TextEntry::make('parent_contact')
                            ->label('Kontak Orang Tua')
                            ->default('-'),

                        TextEntry::make('status')
                            ->label('Status')
                            ->formatStateUsing(fn ($state) => match ($state) {
                                'active' => 'Aktif',
                                'inactive' => 'Nonaktif',
                                'graduated' => 'Lulus',
                                default => $state,
                            })
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'active' => 'success',
                                'inactive' => 'warning',
                                'graduated' => 'gray',
                                default => 'gray',
                            }),

                        TextEntry::make('activeClass.classRoom.code')
                            ->label('Kelas Aktif')
                            ->badge()
                            ->color('success')
                            ->default('-'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ClassHistoriesRelationManager::class,
            PaymentHistoryRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'view' => Pages\ViewStudent::route('/{record}'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}