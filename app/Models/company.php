<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class company extends Model
{
    // 
    protected $fillable = [
        'company_name',
        'company_registration_no',
        'company_email',
        'company_phone',
        'company_address',
        'company_city',
        'company_state',
        'company_zip',
        'company_country',
        'company_website',
        'company_industry',
        'company_size',
        'company_rating',
        'company_founded_date',
        'company_owner',
        'company_tags',
        'company_notes',
        'social_media',
        'status',
        'company_type',
        'company_employee_count',
        'company_description',
        'company_postal_code',
        'company_social',
        'legal_entity_name',
        'gstin',
        'pan',
        'tax_registration_number',
        'billing_address',
        'billing_city',
        'billing_state',
        'billing_zip',
        'billing_country',
    ];

    protected $casts = [
        'social_media' => 'array',
    ];

    public function contacts()
    {
        return $this->hasMany(Contact::class, 'company_id');
    }

    public function deals()
    {
        return $this->hasMany(\App\Models\deal::class, 'company_id');
    }

    public function projects()
    {
        return $this->hasMany(\App\Models\Project::class, 'company_id');
    }

    public function communications()
    {
        return $this->hasMany(\App\Models\Communication::class, 'company_id');
    }
}
