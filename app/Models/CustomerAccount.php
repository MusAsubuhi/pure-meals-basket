<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerAccount extends Model
{
    //use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'account_number',
        'total_credit',
        'total_debit',
        'balance',
    ];

    protected static function booted(): void
    {
        static::creating(function ($account) {
            if (empty($account->account_number)) {
                $account->account_number = 'CUST-' . str_pad((string) ($account->customer_id ?? $account->id), 6, '0', STR_PAD_LEFT);
            }
        });
    }

    protected $casts = [
        'total_credit' => 'decimal:2',
        'total_debit' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function transactions()
    {
        return $this->hasMany(CustomerTransaction::class);
    }
}