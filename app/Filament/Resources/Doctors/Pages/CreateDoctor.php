<?php

namespace App\Filament\Resources\Doctors\Pages;

use App\Filament\Resources\Doctors\DoctorResource;
use App\Models\Doctor;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateDoctor extends CreateRecord
{
    protected static string $resource = DoctorResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['password_confirmation']);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
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
