<?php

namespace App\Models;

use App\Enums\Quotation\QuotationStatus;
use App\Models\Request\Request;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Quotation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'quotations';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'request_id',
        'reference',
        'status',
        'valid_until',
        'sent_at',
        'accepted_at',
        'declined_at',
        'withdrawn_at',
        'expired_at',
        'subtotal',
        'discount',
        'total',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'status' => QuotationStatus::class,
        'valid_until' => 'datetime',
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'declined_at' => 'datetime',
        'withdrawn_at' => 'datetime',
        'expired_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $quotation) {
            if (empty($quotation->id)) {
                $quotation->id = (string) Str::uuid();
            }
        });
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class)->orderBy('id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(QuotationEvent::class)->orderBy('created_at');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActionable($query)
    {
        return $query->where('status', QuotationStatus::SENT);
    }

    public function scopeSent($query)
    {
        return $query->where('status', QuotationStatus::SENT);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            QuotationStatus::DRAFT,
            QuotationStatus::SENT,
        ]);
    }

    public static function generateReference(): string
    {
        $year = now()->year;
        $latest = static::whereYear('created_at', $year)
            ->orderByDesc('id')
            ->value('reference');

        $sequence = 1;
        if ($latest && preg_match('/QUO-(\d{4})-(\d{4})/', $latest, $matches)) {
            $sequence = (int) $matches[2] + 1;
        }

        return sprintf('QUO-%04d-%04d', $year, $sequence);
    }

    public function isDraft(): bool
    {
        return $this->status->isDraft();
    }

    public function isSent(): bool
    {
        return $this->status->isSent();
    }

    public function isAccepted(): bool
    {
        return $this->status->isAccepted();
    }

    public function isDeclined(): bool
    {
        return $this->status->isDeclined();
    }

    public function isWithdrawn(): bool
    {
        return $this->status->isWithdrawn();
    }

    public function isExpired(): bool
    {
        return $this->status->isExpired();
    }

    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }

    public function canBeEdited(): bool
    {
        return $this->status->canBeEdited();
    }

    public function canBeSent(): bool
    {
        return $this->status->canBeSent();
    }

    public function canBeAccepted(): bool
    {
        return $this->status->canBeAccepted();
    }

    public function canBeDeclined(): bool
    {
        return $this->status->canBeDeclined();
    }

    public function canBeWithdrawn(): bool
    {
        return $this->status->canBeWithdrawn();
    }

    public function canBeReplaced(): bool
    {
        return $this->status->canBeReplaced();
    }

    public function hasExpired(): bool
    {
        return $this->isSent() && $this->valid_until !== null && now()->greaterThan($this->valid_until);
    }

    public function order(): HasOne
    {
        return $this->hasOne(Order::class);
    }

    public function logEvent(string $eventType, ?string $description = null, ?int $userId = null, array $metadata = []): QuotationEvent
    {
        return $this->events()->create([
            'event_type' => $eventType,
            'user_id' => $userId,
            'data' => $metadata,
            'created_at' => now(),
        ]);
    }
}
