<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentModel extends Model
{
    use HasFactory;

    protected $table = 'appointments';

    protected $fillable = [
        'patient_id',
        'status',
        'date',
        'time_slots',
        'doct_id',
        'clinic_id',
        'dept_id',
        'type',
        'meeting_id',
        'meeting_link',
        'id_payment',
        'payment_status',
        'payment_reference',
        'payment_provider',
        'payment_confirmed_at',
        'current_cancel_req_status',
        'source',
    ];

    protected $casts = [
        'date' => 'date',
        'payment_confirmed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [];

    public function payment()
    {
        return $this->belongsTo(PaymentModel::class, 'id_payment');
    }
}