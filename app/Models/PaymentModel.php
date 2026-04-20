<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentModel extends Model
{
    use HasFactory;

    protected $table = 'payments';

    protected $fillable = [
        'id_user',
        'id_appointment',
        'id_lab_booking',
        'id_payment_type',
        'name_payment_type',
        'provider',
        'payment_method_code',
        'provider_reference',
        'provider_process_id',
        'provider_operation_id',
        'id_payment_status',
        'currency_code',
        'gateway_description',
        'authorization_code',
        'ticket_number',
        'response_code',
        'response_message',
        'account_type',
        'card_last_numbers',
        'bin',
        'merchant_code',
        'confirmed_at',
        'canceled_at',
        'expires_at',
        'request_json',
        'response_json',
        'webhook_json',
        'webhook_received_at',
        'is_webhook_confirmed',
        'amount',
        'identification',
    ];

    protected $casts = [
        'request_json' => 'array',
        'response_json' => 'array',
        'webhook_json' => 'array',
        'confirmed_at' => 'datetime',
        'canceled_at' => 'datetime',
        'expires_at' => 'datetime',
        'webhook_received_at' => 'datetime',
        'is_webhook_confirmed' => 'boolean',
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [];

    public function appointment()
    {
        return $this->belongsTo(AppointmentModel::class, 'id_appointment');
    }
}