<?php

namespace App\Filament\Resources\Doctors\Pages;

use App\Filament\Resources\Doctors\DoctorResource;
use App\Models\Doctor;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class EditDoctor extends EditRecord
{
    protected static string $resource = DoctorResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (filled($data['new_password'] ?? null)) {
            $data['password'] = Hash::make($data['new_password']);
        }

        unset($data['new_password'], $data['new_password_confirmation']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update($data);

        $this->form->model($record)->saveRelationships();

        return $record;
    }

    protected function afterSave(): void
    {
        assert($this->record instanceof Doctor);

        $this->syncPrimarySpecialityId($this->record);
    }

    protected function syncPrimarySpecialityId(Doctor $doctor): void
    {
        $doctor->load('specialities');
        $ids = $doctor->specialities->pluck('id')->sort()->values();
        if ($ids->isEmpty()) {
            return;
        }

        $doctor->updateQuietly(['speciality_id' => $ids->first()]);
    }
}
