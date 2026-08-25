<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerTransaction extends Model
{
    //use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'customer_account_id',
        'invoice_id',
        'currency_id',
        'preferred_currency_id',
        'amount',
        'amount_base',
        'type',
        'description',
        'reference',
        'transaction_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_base' => 'decimal:2',
        'transaction_date' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function account()
    {
        return $this->belongsTo(CustomerAccount::class, 'customer_account_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}