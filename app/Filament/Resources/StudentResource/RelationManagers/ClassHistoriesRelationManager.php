<?php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Resources\RelationManagers\RelationManager;
use App\Models\AcademicYear;
use App\Models\SchoolClass;

class StudentClassHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'classHistories';

    protected static ?string $title = 'Riwayat Kelas';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('academic_year_id')
                ->label('Tahun Ajaran')
                ->options(
                    AcademicYear::query()
                        ->orderByDesc('is_active')
                        ->pluck('year', 'id')
                )
                ->required(),

            Forms\Components\Select::make('class_id')
                ->label('Kelas')
                ->options(
                    SchoolClass::query()
                        ->get()
                        ->mapWithKeys(fn ($c) => [
                            $c->id => "{$c->category} - {$c->code}",
                        ])
                )
                ->required(),

            Forms\Components\Toggle::make('is_active')
                ->label('Kelas Aktif')
                ->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('academicYear.year')
                    ->label('Tahun Ajaran'),

                Tables\Columns\TextColumn::make('classRoom.category')
                    ->label('Kategori'),

                Tables\Columns\TextColumn::make('classRoom.code')
                    ->label('Kode Kelas'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['id'] = (string) Str::uuid();

                        if ($data['is_active']) {
                            $this->getOwnerRecord()
                                ->classHistories()
                                ->update(['is_active' => false]);
                        }

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        if ($data['is_active']) {
                            $this->getOwnerRecord()
                                ->classHistories()
                                ->update(['is_active' => false]);
                        }

                        return $data;
                    }),

                Tables\Actions\DeleteAction::make(),
            ]);
    }
}