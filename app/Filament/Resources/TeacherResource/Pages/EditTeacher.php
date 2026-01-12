<?php

namespace App\Filament\Resources\TeacherResource\Pages;

use App\Filament\Resources\TeacherResource;
use App\Models\SchoolClass;
use Filament\Resources\Pages\EditRecord;

class EditTeacher extends EditRecord
{
    protected static string $resource = TeacherResource::class;

    protected function afterSave(): void
    {
        $data = $this->form->getState();
        $user = $this->record;

        if (isset($data['homeroom_classes'])) {

            // Lepas kelas yang tidak dipilih
            SchoolClass::where('homeroom_teacher_id', $user->id)
                ->whereNotIn('id', $data['homeroom_classes'])
                ->update(['homeroom_teacher_id' => null]);

            // Assign kelas baru
            SchoolClass::whereIn('id', $data['homeroom_classes'])
                ->update(['homeroom_teacher_id' => $user->id]);
        }
    }
}
