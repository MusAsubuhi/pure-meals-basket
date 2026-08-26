<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ServicePackage extends Model
{
    protected $fillable = [
        'service_category_id',
        'name',
        'slug',
        'description',
        'unit',
        'price',
        'is_addon',
        'image_path',
        'is_quote_only',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_addon' => 'boolean',
        'is_quote_only' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }
}