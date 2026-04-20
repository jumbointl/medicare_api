<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BancardPromotionModel extends Model
{
    use HasFactory;

    protected $table = 'bancard_promotions';

    protected $fillable = [
        'code',
        'title',
        'description',
        'additional_data',
        'discount_type',
        'discount_value',
        'card_brand',
        'issuer_name',
        'min_amount',
        'max_amount',
        'starts_at',
        'ends_at',
        'active',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}