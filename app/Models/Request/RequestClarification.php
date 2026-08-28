<?php

namespace App\Models\Request;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class RequestClarification extends Model
{
    protected $table = 'request_clarifications';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'request_id',
        'asked_by_user_id',
        'question',
        'response',
        'responded_by_user_id',
        'responded_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $clarification) {
            if (empty($clarification->id)) {
                $clarification->id = (string) Str::uuid();
            }
        });
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function askedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asked_by_user_id');
    }

    public function respondedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by_user_id');
    }

    public function hasBeenAnswered(): bool
    {
        return $this->response !== null && $this->responded_at !== null;
    }
}
