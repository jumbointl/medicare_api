<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTypeModel extends Model
{
    protected $table = 'payment_types';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'active',
        'opd', 
        'video', 
        'emergency'
    ];
    protected $casts = [
        'active' => 'boolean',
        'opd' => 'boolean',
        'video' => 'boolean',
        'emergency' => 'boolean',
    ];
}