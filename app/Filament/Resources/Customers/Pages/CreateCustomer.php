<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\CustomerAccount;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $userData = $data['user'] ?? null;

        if ($userData) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => $userData['password'] ?? Hash::make('password'),
                'company_id' => $data['company_id'] ?? null,
            ])
            ->assignRole('customer');

            $data['user_id'] = $user->id;
        }

        unset($data['user']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $customer = $this->record;

        // Auto-create the financial account for the customer
        CustomerAccount::firstOrCreate([
            'customer_id' => $customer->id,
        ], [
            'account_number' => 'CUST-' . str_pad($customer->id, 6, '0'),
            'total_credit' => 0.00,
            'total_debit' => 0.00,
            'balance' => 0.00,
        ]);
    }
}
