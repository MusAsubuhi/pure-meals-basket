<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PaymentAttempt extends Model
{
    protected $table = 'payment_attempts';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'payment_id',
        'attempt_reference',
        'provider',
        'status',
        'request_payload',
        'response_payload',
        'provider_reference',
        'checkout_request_id',
        'initiated_at',
        'completed_at',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'initiated_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $attempt) {
            if (empty($attempt->id)) {
                $attempt->id = (string) Str::uuid();
            }
        });
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
