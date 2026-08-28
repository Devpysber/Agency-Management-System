<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class deal extends Model
{
    //
    protected $fillable = [
        'deal_name',
        'deal_notes',
        'deal_value',
        'currency',
        'expected_close_date',
        'actual_close_date',
        'deal_stage',
        'probability',
        'deal_status',
        'contact_id',
        'company_id',
        'assigned_to',
        'created_by',
    ];
    public function company(){
        return $this->belongsTo(Company::class);
    }
    public function contact(){
        return $this->belongsTo(Contact::class);
    }
    public function createdBy(){
        return $this->belongsTo(User::class, 'created_by');
    }
    public function assignedTo(){
        return $this->belongsTo(staff::class, 'assigned_to');
    }
}
