<?php

namespace App\Models;

use App\Enums\Payment\PaymentMethod;
use App\Enums\Payment\PaymentProvider;
use App\Enums\Payment\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'payments';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'order_id',
        'customer_id',
        'reference',
        'method',
        'provider',
        'status',
        'amount',
        'currency',
        'provider_payment_id',
        'provider_reference',
        'checkout_request_id',
        'paid_at',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'status' => PaymentStatus::class,
        'method' => PaymentMethod::class,
        'provider' => PaymentProvider::class,
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $payment) {
            if (empty($payment->id)) {
                $payment->id = (string) Str::uuid();
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class)->orderByDesc('created_at');
    }

    public function events(): HasMany
    {
        return $this->hasMany(PaymentEvent::class)->orderByDesc('created_at');
    }

    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    public function isProcessing(): bool
    {
        return $this->status->isProcessing();
    }

    public function isSuccess(): bool
    {
        return $this->status->isSuccess();
    }

    public function isFailed(): bool
    {
        return $this->status->isFailed();
    }

    public function isCancelled(): bool
    {
        return $this->status->isCancelled();
    }

    public function isReversed(): bool
    {
        return $this->status->isReversed();
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [
            PaymentStatus::SUCCESS,
            PaymentStatus::FAILED,
            PaymentStatus::CANCELLED,
            PaymentStatus::REVERSED,
        ], true);
    }

    public static function generateReference(): string
    {
        $year = now()->year;
        $latest = static::whereYear('created_at', $year)
            ->orderByDesc('id')
            ->value('reference');

        $sequence = 1;
        if ($latest && preg_match('/PAY-(\d{4})-(\d{4})/', $latest, $matches)) {
            $sequence = (int) $matches[2] + 1;
        }

        return sprintf('PAY-%04d-%04d', $year, $sequence);
    }

    public function logEvent(string $eventType, ?string $description = null, ?int $userId = null, array $metadata = []): PaymentEvent
    {
        return $this->events()->create([
            'event_type' => $eventType,
            'user_id' => $userId,
            'data' => $metadata,
            'created_at' => now(),
        ]);
    }
}
