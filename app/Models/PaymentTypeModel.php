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
    ];
}