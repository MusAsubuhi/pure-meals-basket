<?php

namespace App\Models\Fulfillment;

use App\Enums\Order\FulfillmentMethod;
use App\Enums\Order\FulfillmentStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Fulfillment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'fulfillments';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'order_id',
        'method',
        'status',
        'scheduled_at',
        'started_at',
        'ready_at',
        'dispatched_at',
        'delivered_at',
        'collected_at',
        'service_started_at',
        'completed_at',
        'delivery_address',
        'delivery_contact_name',
        'delivery_contact_phone',
        'collection_notes',
        'service_location',
        'service_notes',
        'recipient_name',
        'failure_reason',
        'notes',
    ];

    protected $casts = [
        'method' => FulfillmentMethod::class,
        'status' => FulfillmentStatus::class,
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'ready_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'delivered_at' => 'datetime',
        'collected_at' => 'datetime',
        'service_started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $fulfillment) {
            if (empty($fulfillment->id)) {
                $fulfillment->id = (string) Str::uuid();
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(FulfillmentEvent::class)->orderByDesc('id');
    }

    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    public function isPreparing(): bool
    {
        return $this->status->isPreparing();
    }

    public function isReady(): bool
    {
        return $this->status->isReady();
    }

    public function isOutForDelivery(): bool
    {
        return $this->status->isOutForDelivery();
    }

    public function isDelivered(): bool
    {
        return $this->status->isDelivered();
    }

    public function isCollected(): bool
    {
        return $this->status->isCollected();
    }

    public function isServiceInProgress(): bool
    {
        return $this->status->isServiceInProgress();
    }

    public function isCompleted(): bool
    {
        return $this->status->isCompleted();
    }

    public function isDeliveryFailed(): bool
    {
        return $this->status->isDeliveryFailed();
    }

    public function isCancelled(): bool
    {
        return $this->status->isCancelled();
    }

    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }

    public function logEvent(string $eventType, ?string $description = null, ?int $userId = null, array $metadata = []): FulfillmentEvent
    {
        return $this->events()->create([
            'event_type' => $eventType,
            'user_id' => $userId,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }
}
