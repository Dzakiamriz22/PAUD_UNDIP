<?php

namespace App\Filament\Resources\TeacherResource\Pages;

use App\Filament\Resources\TeacherResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\SchoolClass;

class CreateTeacher extends CreateRecord
{
    protected static string $resource = TeacherResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['username'] = Str::slug(
            $data['firstname'] . ' ' . $data['lastname']
        );

        $data['password'] = Hash::make('password123');

        return $data;
    }

    protected function afterCreate(): void
    {
        $roles = $this->data['roles'];
        $this->record->syncRoles($roles);

        if (in_array('guru', $roles)) {
            \App\Models\SchoolClass::whereIn('id', $this->data['homeroom_classes'] ?? [])
                ->update([
                    'homeroom_teacher_id' => $this->record->id,
                ]);
        }
    }
}