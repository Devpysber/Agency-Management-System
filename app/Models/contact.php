<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class contact extends Model
{
    use HasFactory;

    protected $table = 'contacts';

    protected $fillable = [
        // Personal Information
        'first_name',
        'last_name',
        'email',
        'phone',
        'mobile',
        
        // Company Information
        'company_id',
        'user_id',
        'job_title',
        'department',
        
        // Address
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        
        // Social Media
        'social_media',
        
        // Additional Information
        'source',
        'lead_source',
        'lead_status',
        'lead_score',
        
        // Communication Preferences
        'email_opt_in',
        'sms_opt_in',
        'call_opt_in',
        
        // Personal Information
        'birth_date',
        'gender',
        'nationality',
        
        // Notes & Tags
        'notes',
        'tags',
        
        // Assignment
        'assigned_to',
        'created_by',
        
        // Status
        'status',
        
        // Timestamps
        'last_contacted_at',
        'email_verified_at',
        'phone_verified_at',
    ];

    protected $casts = [
        'social_media' => 'array',
        'tags' => 'array',
        'birth_date' => 'date',
        'last_contacted_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'email_opt_in' => 'boolean',
        'sms_opt_in' => 'boolean',
        'call_opt_in' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    public function company(){
        return $this->belongsTo(company::class);
    }

    /**
     * The user account this contact logs into the client portal with
     * (nullable — most contacts never get portal access).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(staff::class, 'assigned_to');
    }

    public function deals()
    {
        return $this->hasMany(deal::class, 'contact_id');
    }

    public function communications()
    {
        return $this->hasMany(Communication::class, 'contact_id');
    }

    public function getFullNameAttribute()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

}