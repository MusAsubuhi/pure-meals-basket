<?php

namespace App\Services\Customer;

use App\Models\Customer;
use App\Models\CustomerAccount;
use App\Models\CustomerTransaction;
use Illuminate\Support\Facades\DB;

class CustomerAccountingService
{
    /**
     * Get or create the customer's financial account.
     */
    public function account(Customer $customer): CustomerAccount
    {
        return CustomerAccount::firstOrCreate(
            ['customer_id' => $customer->id],
            [
                'account_number' => 'CUST-'.str_pad((string) $customer->id, 6, '0', STR_PAD_LEFT),
                'total_credit' => 0.00,
                'total_debit' => 0.00,
                'balance' => 0.00,
            ]
        );
    }

    /**
     * Record an order charge (debit) against the customer's account.
     *
     * Uses a row lock and a single atomic UPDATE so concurrent charges on the
     * same account cannot race each other — the balance is always derived
     * from the current committed row, never from a stale in-memory copy.
     */
    public function recordOrderCharge(Customer $customer, string $orderReference, float $amount, ?string $notes = null): CustomerTransaction
    {
        return DB::transaction(function () use ($customer, $orderReference, $amount, $notes) {
            $account = $this->lockAccount($customer);

            // Query-builder update: Eloquent's decimal casts cannot take raw expressions.
            $account->newQuery()->whereKey($account->getKey())->update([
                'total_debit' => DB::raw('total_debit + '.$this->bindAmount($amount)),
                'balance' => DB::raw('total_credit - (total_debit + '.$this->bindAmount($amount).')'),
            ]);

            $transaction = CustomerTransaction::create([
                'customer_id' => $customer->id,
                'customer_account_id' => $account->id,
                'amount' => $amount,
                'amount_base' => $amount,
                'type' => 'debit',
                'description' => $notes ?? 'Order '.$orderReference,
                'reference' => $orderReference,
                'transaction_date' => now(),
            ]);

            return $transaction;
        });
    }

    /**
     * Record a payment receipt (credit) against the customer's account.
     *
     * Uses a row lock and a single atomic UPDATE so concurrent receipts on the
     * same account cannot race each other.
     */
    public function recordPayment(Customer $customer, string $paymentReference, float $amount, ?string $notes = null): CustomerTransaction
    {
        return DB::transaction(function () use ($customer, $paymentReference, $amount, $notes) {
            $account = $this->lockAccount($customer);

            // Query-builder update: Eloquent's decimal casts cannot take raw expressions.
            $account->newQuery()->whereKey($account->getKey())->update([
                'total_credit' => DB::raw('total_credit + '.$this->bindAmount($amount)),
                'balance' => DB::raw('(total_credit + '.$this->bindAmount($amount).') - total_debit'),
            ]);

            $transaction = CustomerTransaction::create([
                'customer_id' => $customer->id,
                'customer_account_id' => $account->id,
                'amount' => $amount,
                'amount_base' => $amount,
                'type' => 'credit',
                'description' => $notes ?? 'Payment '.$paymentReference,
                'reference' => $paymentReference,
                'transaction_date' => now(),
            ]);

            return $transaction;
        });
    }

    /**
     * Get the customer's current account balance.
     */
    public function balance(Customer $customer): float
    {
        return $this->account($customer)->balance;
    }

    /**
     * Get or create the customer's financial account, locked for update
     * inside an active transaction. The lock serialises concurrent charges
     * / receipts on the same account so balance updates never race.
     */
    protected function lockAccount(Customer $customer): CustomerAccount
    {
        $account = CustomerAccount::where('customer_id', $customer->id)
            ->lockForUpdate()
            ->first();

        if ($account === null) {
            $account = CustomerAccount::create([
                'customer_id' => $customer->id,
                'account_number' => 'CUST-'.str_pad((string) $customer->id, 6, '0', STR_PAD_LEFT),
                'total_credit' => 0.00,
                'total_debit' => 0.00,
                'balance' => 0.00,
            ]);
        }

        return $account;
    }

    /**
     * Format an amount as a bind parameter for a raw SQL expression.
     * Using a bound value keeps the arithmetic server-side and avoids the
     * stale-read problem inherent in load-modify-update cycles.
     */
    protected function bindAmount(float $amount): string
    {
        $rounded = round($amount, 2);

        return (string) $rounded;
    }
}
