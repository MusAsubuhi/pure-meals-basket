<?php

namespace App\Filament\Resources\Customers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        // Eager load relationships
        $table->modifyQueryUsing(fn ($query) => $query->with(['account']));

        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Customer Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon('heroicon-o-user')
                    ->description(fn ($record): string => $record->user->email)
                    ->wrap(),

                TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable()
                    ->icon('heroicon-o-phone')
                    ->copyable()
                    ->copyMessage('Phone copied!')
                    ->copyMessageDuration(1500),

                TextColumn::make('tax_number')
                    ->label('Tax Number')
                    ->icon('heroicon-o-document-text')
                    ->searchable(),

                TextColumn::make('account.balance')
                    ->label('Balance')
                    ->formatStateUsing(function ($record) {
                        $balance = $record->account?->balance ?? 0;
                        $formattedBalance = number_format($balance, 2, '.', ',');

                        return 'Ksh'.' '.$formattedBalance;
                    })
                    ->default(0)
                    ->sortable(query: function ($query, string $direction) {
                        $query->leftJoin('customer_accounts', 'customers.id', '=', 'customer_accounts.customer_id')
                            ->orderBy('customer_accounts.balance', $direction);
                    })
                    ->searchable()
                    ->alignRight()
                    ->color(function ($record) {
                        $balance = $record->account?->balance ?? 0;

                        return $balance >= 0 ? 'success' : 'danger';
                    }),

                IconColumn::make('status')
                    ->label('Status')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
