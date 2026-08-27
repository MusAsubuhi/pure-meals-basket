<?php

namespace App\Models\Request;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestEvent extends Model
{
    protected $table = 'request_events';

    public $timestamps = false; // uses only created_at

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Factory for append-only events.
     */
    public static function record(
        Request $request,
        string $eventType,
        ?string $description = null,
        ?int $userId = null,
        array $metadata = []
    ): self {
        return static::create([
            'request_id' => $request->id,
            'user_id' => $userId,
            'event_type' => $eventType,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }
}
