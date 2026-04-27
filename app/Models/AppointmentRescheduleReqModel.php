<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentRescheduleReqModel extends Model
{
    use HasFactory;

    protected $table = 'appointment_reschedule_req';

    protected $fillable = [
        'appointment_id',
        'status',
        'requested_date',
        'requested_time_slots',
        'notes',
        'reviewed_by_user_id',
        'reviewed_at',
    ];

    protected $casts = [
        'requested_date' => 'date',
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [];

    public function appointment()
    {
        return $this->belongsTo(AppointmentModel::class, 'appointment_id');
    }
}
