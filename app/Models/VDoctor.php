<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VDoctor extends Model
{
    protected $table = 'v_doctors';
    protected $primaryKey = 'doctor_id';
    public $incrementing = false;
    public $timestamps = false;
}