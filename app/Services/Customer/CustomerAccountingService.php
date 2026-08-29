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
     */
    public function recordOrderCharge(Customer $customer, string $orderReference, float $amount, ?string $notes = null): CustomerTransaction
    {
        return DB::transaction(function () use ($customer, $orderReference, $amount, $notes) {
            $account = $this->account($customer);

            $account->update([
                'total_debit' => $account->total_debit + $amount,
                'balance' => $account->total_credit - ($account->total_debit + $amount),
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
     */
    public function recordPayment(Customer $customer, string $paymentReference, float $amount, ?string $notes = null): CustomerTransaction
    {
        return DB::transaction(function () use ($customer, $paymentReference, $amount, $notes) {
            $account = $this->account($customer);

            $account->update([
                'total_credit' => $account->total_credit + $amount,
                'balance' => ($account->total_credit + $amount) - $account->total_debit,
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
}
