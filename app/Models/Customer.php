<?php

namespace App\Models;

use App\Models\CreditNote;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    //use SoftDeletes;

    protected static function booted(): void
    {
        
        static::deleting(function (self $customer) {
            if ($customer->user) {
                $customer->user->delete();
            }
        });
    }

    protected $fillable = [
        'user_id',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country',
        'phone',
        'tax_number',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
        
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Resolve the customer's display name from the related user.
     */
    public function getNameAttribute(): string
    {
        return $this->user?->name ?? '';
    }

    

    public function account()
    {
        return $this->hasOne(CustomerAccount::class);
    }

    public function transactions()
    {
        return $this->hasMany(CustomerTransaction::class);
    }

    public function creditNotes()
    {
        return $this->hasMany(CreditNote::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

}
