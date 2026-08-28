<?php

namespace Database\Seeders;

use App\Models\CompanySetting;
use Illuminate\Database\Seeder;

class CompanySettingSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        CompanySetting::updateOrCreate(['id' => 1], [
            // Business
            'business_name' => 'Agency ERP Demo',
            'business_tagline' => 'Creative solutions, delivered.',
            'business_logo' => null,
            'business_favicon' => null,
            'currency' => 'USD',
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',

            // Contact
            'contact_email' => 'hello@agencyerp-demo.com',
            'contact_phone' => '+1 (555) 123-4567',
            'contact_address' => '123 Market Street, Suite 400',
            'contact_city' => 'San Francisco, CA 94103',
            'contact_country' => 'United States',
            'contact_map_embed' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3153.019!2d-122.419!3d37.774',

            // Email
            'mail_from_name' => 'Agency ERP Demo',
            'mail_from_address' => 'no-reply@agencyerp-demo.com',
            'smtp_host' => 'smtp.mailtrap.io',
            'smtp_port' => '2525',
            'smtp_username' => 'demo_user',
            'smtp_password' => null,
            'smtp_encryption' => 'tls',

            // Social
            'social_facebook' => 'https://facebook.com/agencyerpdemo',
            'social_twitter' => 'https://twitter.com/agencyerpdemo',
            'social_instagram' => 'https://instagram.com/agencyerpdemo',
            'social_linkedin' => 'https://linkedin.com/company/agencyerpdemo',
            'social_youtube' => 'https://youtube.com/@agencyerpdemo',

            // Footer
            'footer_text' => 'Agency ERP Demo is a full-service digital agency helping businesses grow through strategy, design, and technology.',
            'footer_copyright' => '© ' . date('Y') . ' Agency ERP Demo. All rights reserved.',
            'footer_links' => [
                ['label' => 'Privacy Policy', 'url' => '/privacy-policy'],
                ['label' => 'Terms of Service', 'url' => '/terms'],
            ],

            // SEO
            'seo_title' => 'Agency ERP Demo - Creative Digital Agency',
            'seo_description' => 'Agency ERP Demo helps brands grow with strategy, design, and technology solutions tailored to their goals.',
            'seo_keywords' => 'digital agency, web design, marketing, branding',
            'seo_og_image' => null,

            // Other
            'maintenance_mode' => false,
            'google_analytics_id' => 'G-DEMO123456',
            'primary_color' => '#4F46E5',
            'secondary_color' => '#818CF8',
        ]);
    }
}
