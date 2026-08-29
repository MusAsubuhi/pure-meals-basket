<?php

namespace App\Models\Request;

use App\Enums\Quotation\QuotationStatus;
use App\Enums\Request\RequestStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Quotation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Request extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'requests';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'customer_id',
        'reference',
        'status',
        'event_date',
        'event_time',
        'location',
        'notes',
        'submitted_at',
    ];

    protected $casts = [
        'status' => RequestStatus::class,
        'event_date' => 'date',
        'event_time' => 'datetime:H:i',
        'submitted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $request) {
            if (empty($request->id)) {
                $request->id = (string) Str::uuid();
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RequestItem::class)->orderBy('id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(RequestEvent::class)->orderBy('created_at');
    }

    public function clarifications(): HasMany
    {
        return $this->hasMany(RequestClarification::class)->orderBy('created_at');
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class)->orderByDesc('created_at');
    }

    public function acceptedQuotation(): HasOne
    {
        return $this->hasOne(Quotation::class)->where('status', QuotationStatus::ACCEPTED);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->orderByDesc('created_at');
    }

    /**
     * Generate a unique reference like REQ-2026-0001
     */
    public static function generateReference(): string
    {
        $year = now()->year;
        $latest = static::whereYear('created_at', $year)
            ->orderByDesc('id')
            ->value('reference');

        $sequence = 1;
        if ($latest && preg_match('/REQ-(\d{4})-(\d{4})/', $latest, $matches)) {
            $sequence = (int) $matches[2] + 1;
        }

        return sprintf('REQ-%04d-%04d', $year, $sequence);
    }

    /**
     * Create a log entry for this request's audit trail.
     */
    public function logEvent(string $eventType, ?string $description = null, ?int $userId = null, array $metadata = []): RequestEvent
    {
        return $this->events()->create([
            'event_type' => $eventType,
            'user_id' => $userId,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Check if all items in this request have calculable prices
     * and no item requires PMB review.
     */
    public function isAutoApprovable(): bool
    {
        if ($this->items->isEmpty()) {
            return false;
        }

        return $this->items->every(fn ($item) => $item->isCalculated());
    }
}
