<?php

namespace App\Models\Fulfillment;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class FulfillmentEvent extends Model
{
    use HasFactory;

    protected $table = 'fulfillment_events';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'fulfillment_id',
        'user_id',
        'event_type',
        'description',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $event) {
            if (empty($event->id)) {
                $event->id = (string) Str::uuid();
            }
        });
    }

    public function fulfillment(): BelongsTo
    {
        return $this->belongsTo(Fulfillment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
