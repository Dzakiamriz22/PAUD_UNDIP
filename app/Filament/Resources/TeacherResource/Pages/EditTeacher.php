<?php

namespace App\Filament\Resources\TeacherResource\Pages;

use App\Filament\Resources\TeacherResource;
use Filament\Resources\Pages\EditRecord;
use App\Models\SchoolClass;

class EditTeacher extends EditRecord
{
    protected static string $resource = TeacherResource::class;

    protected function afterSave(): void
    {
        $roles = $this->data['roles'];
        $this->record->syncRoles($roles);

        \App\Models\SchoolClass::where('homeroom_teacher_id', $this->record->id)
            ->update(['homeroom_teacher_id' => null]);

        if (in_array('guru', $roles)) {
            \App\Models\SchoolClass::whereIn('id', $this->data['homeroom_classes'] ?? [])
                ->update([
                    'homeroom_teacher_id' => $this->record->id,
                ]);
        }
    }
}