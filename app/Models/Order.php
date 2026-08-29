<?php

namespace App\Models;

use App\Enums\Order\FulfillmentMethod;
use App\Enums\Order\OrderStatus;
use App\Enums\Order\PaymentStatus;
use App\Models\Fulfillment\Fulfillment;
use App\Models\Request\Request;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'orders';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'request_id',
        'quotation_id',
        'reference',
        'status',
        'payment_status',
        'fulfillment_method',
        'event_date',
        'event_time',
        'location',
        'delivery_address',
        'delivery_notes',
        'customer_name',
        'customer_phone',
        'customer_email',
        'subtotal',
        'discount',
        'total',
        'delivery_fee',
        'payment_required',
        'amount_paid',
        'balance_due',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'payment_status' => PaymentStatus::class,
        'fulfillment_method' => FulfillmentMethod::class,
        'event_date' => 'date',
        'event_time' => 'datetime:H:i',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'payment_required' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance_due' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $order) {
            if (empty($order->id)) {
                $order->id = (string) Str::uuid();
            }
        });
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class)->orderBy('created_at');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('created_at');
    }

    public function fulfillment(): BelongsTo
    {
        return $this->belongsTo(Fulfillment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActionable($query)
    {
        return $query->whereNotIn('status', [
            OrderStatus::COMPLETED,
            OrderStatus::CANCELLED,
        ]);
    }

    public function isDraft(): bool
    {
        return $this->status->isDraft();
    }

    public function isPendingPayment(): bool
    {
        return $this->status->isPendingPayment();
    }

    public function isConfirmed(): bool
    {
        return $this->status->isConfirmed();
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

    public function isCompleted(): bool
    {
        return $this->status->isCompleted();
    }

    public function isCancelled(): bool
    {
        return $this->status->isCancelled();
    }

    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }

    public function canBeCancelled(): bool
    {
        return $this->status->canBeCancelled();
    }

    public function canBeConfirmed(): bool
    {
        return $this->status->canBeConfirmed();
    }

    public function canStartPreparing(): bool
    {
        return $this->status->canStartPreparing();
    }

    public function canMarkReady(): bool
    {
        return $this->status->canMarkReady();
    }

    public function canDispatch(): bool
    {
        return $this->status->canDispatch();
    }

    public function canMarkDelivered(): bool
    {
        return $this->status->canMarkDelivered();
    }

    public function canComplete(): bool
    {
        return $this->status->canComplete();
    }

    public function isUnpaid(): bool
    {
        return $this->payment_status->isUnpaid();
    }

    public function isPartiallyPaid(): bool
    {
        return $this->payment_status->isPartiallyPaid();
    }

    public function isPaid(): bool
    {
        return $this->payment_status->isPaid();
    }

    public static function generateReference(): string
    {
        $year = now()->year;
        $latest = static::whereYear('created_at', $year)
            ->orderByDesc('id')
            ->value('reference');

        $sequence = 1;
        if ($latest && preg_match('/ORD-(\d{4})-(\d{4})/', $latest, $matches)) {
            $sequence = (int) $matches[2] + 1;
        }

        return sprintf('ORD-%04d-%04d', $year, $sequence);
    }

    public function logEvent(string $eventType, ?string $description = null, ?int $userId = null, array $metadata = []): OrderEvent
    {
        return $this->events()->create([
            'event_type' => $eventType,
            'user_id' => $userId,
            'data' => $metadata,
            'created_at' => now(),
        ]);
    }
}
