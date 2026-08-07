<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class staff extends Model
{
    //
    protected $fillable = [
        'image',
        'name',
        'email',
        'whatsapp',
        'designation',
        'joining_date',
        'user_id',
        'salary',
    ];
}
