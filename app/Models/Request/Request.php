<?php

namespace App\Models\Request;

use App\Enums\Request\RequestStatus;
use App\Models\Customer;
use App\Models\Request\RequestClarification;
use App\Models\Request\RequestEvent;
use App\Models\Request\RequestItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Request extends Model
{
    use SoftDeletes;

    protected $table = 'requests';

    protected $fillable = [
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
        'event_time' => 'time',
        'submitted_at' => 'datetime',
    ];

    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RequestItem::class)->orderBy('id');
    }

    public function events(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RequestEvent::class)->orderBy('created_at');
    }

    public function clarifications(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RequestClarification::class)->orderBy('created_at');
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
}
