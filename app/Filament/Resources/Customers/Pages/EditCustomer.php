<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $userData = $data['user'] ?? null;

        if ($userData && $this->record->user) {
            $user = $this->record->user;
            $user->update([
                'name' => $userData['name'],
                'email' => $userData['email'],
            ]);

            if (! empty($userData['password'])) {
                $user->update([
                    'password' => Hash::make($userData['password']),
                ]);
            }
        }

        unset($data['user']);

        return $data;
    }
}
