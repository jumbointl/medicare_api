<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentWebhookModel extends Model
{
    use HasFactory;

    protected $table = 'payment_webhooks';

    protected $fillable = [
        'provider',
        'provider_reference',
        'event_type',
        'payload_json',
        'processed',
        'processed_at',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'processed' => 'boolean',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [];
}