<?php

namespace App\Models\Request;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestClarification extends Model
{
    protected $table = 'request_clarifications';

    protected $fillable = [
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
