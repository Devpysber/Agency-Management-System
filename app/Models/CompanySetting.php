<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $fillable = [
        // Business
        'business_name',
        'business_tagline',
        'business_logo',
        'business_favicon',
        'currency',
        'timezone',
        'date_format',

        // Contact
        'contact_email',
        'contact_phone',
        'contact_address',
        'contact_city',
        'contact_country',
        'contact_map_embed',

        // Email
        'mail_from_name',
        'mail_from_address',
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_encryption',

        // Social
        'social_facebook',
        'social_twitter',
        'social_instagram',
        'social_linkedin',
        'social_youtube',

        // Footer
        'footer_text',
        'footer_copyright',
        'footer_links',

        // SEO
        'seo_title',
        'seo_description',
        'seo_keywords',
        'seo_og_image',

        // Other
        'maintenance_mode',
        'google_analytics_id',
        'primary_color',
        'secondary_color',
    ];

    protected $casts = [
        'maintenance_mode' => 'boolean',
        'footer_links' => 'array',
    ];
}
