<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'event_type',
        'rating',
        'experience',
        'status',
        'is_featured',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_featured' => 'boolean',
    ];

    /**
     * Whether this feedback has been approved for public display (testimonials).
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}