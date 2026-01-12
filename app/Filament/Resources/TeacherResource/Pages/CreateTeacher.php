<?php

namespace App\Filament\Resources\TeacherResource\Pages;

use App\Filament\Resources\TeacherResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateTeacher extends CreateRecord
{
    protected static string $resource = TeacherResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (
            isset($data['roles'])
            && in_array('bendahara', $data['roles'])
            && in_array('kepala_sekolah', $data['roles'])
        ) {
            throw ValidationException::withMessages([
                'roles' => 'Bendahara tidak boleh merangkap Kepala Sekolah.',
            ]);
        }

        $data['id'] = (string) Str::uuid();
        $data['password'] = Hash::make('paudpermataundip');

        return $data;
    }
}