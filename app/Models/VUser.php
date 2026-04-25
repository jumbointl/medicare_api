<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VUser extends Model
{
    protected $table = 'v_user';
    protected $primaryKey = 'id';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'roles' => 'array',
        'doctor' => 'array',
        'clinic' => 'array',
        'clinics' => 'array',
    ];
}