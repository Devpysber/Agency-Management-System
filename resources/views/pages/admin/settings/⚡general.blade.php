<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\CompanySetting;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
    use WithFileUploads;

    public $settings;

    // Business
    public $business_name;
    public $business_tagline;
    public $business_logo;
    public $business_favicon;
    public $currency = 'USD';
    public $timezone = 'UTC';
    public $date_format = 'Y-m-d';

    // Contact
    public $contact_email;
    public $contact_phone;
    public $contact_address;
    public $contact_city;
    public $contact_country;
    public $contact_map_embed;

    // Email
    public $mail_from_name;
    public $mail_from_address;
    public $smtp_host;
    public $smtp_port;
    public $smtp_username;
    public $smtp_password;
    public $smtp_encryption;

    // Social
    public $social_facebook;
    public $social_twitter;
    public $social_instagram;
    public $social_linkedin;
    public $social_youtube;

    // Footer
    public $footer_text;
    public $footer_copyright;
    public $footer_links;

    // SEO
    public $seo_title;
    public $seo_description;
    public $seo_keywords;
    public $seo_og_image;

    // Other
    public $maintenance_mode = false;
    public $google_analytics_id;
    public $primary_color;
    public $secondary_color;

    public function mount()
    {
        $this->settings = CompanySetting::firstOrCreate(['id' => 1]);

        $this->business_name = $this->settings->business_name;
        $this->business_tagline = $this->settings->business_tagline;
        $this->currency = $this->settings->currency ?: 'USD';
        $this->timezone = $this->settings->timezone ?: 'UTC';
        $this->date_format = $this->settings->date_format ?: 'Y-m-d';

        $this->contact_email = $this->settings->contact_email;
        $this->contact_phone = $this->settings->contact_phone;
        $this->contact_address = $this->settings->contact_address;
        $this->contact_city = $this->settings->contact_city;
        $this->contact_country = $this->settings->contact_country;
        $this->contact_map_embed = $this->settings->contact_map_embed;

        $this->mail_from_name = $this->settings->mail_from_name;
        $this->mail_from_address = $this->settings->mail_from_address;
        $this->smtp_host = $this->settings->smtp_host;
        $this->smtp_port = $this->settings->smtp_port;
        $this->smtp_username = $this->settings->smtp_username;
        $this->smtp_password = $this->settings->smtp_password;
        $this->smtp_encryption = $this->settings->smtp_encryption;

        $this->social_facebook = $this->settings->social_facebook;
        $this->social_twitter = $this->settings->social_twitter;
        $this->social_instagram = $this->settings->social_instagram;
        $this->social_linkedin = $this->settings->social_linkedin;
        $this->social_youtube = $this->settings->social_youtube;

        $this->footer_text = $this->settings->footer_text;
        $this->footer_copyright = $this->settings->footer_copyright;
        $this->footer_links = $this->settings->footer_links;

        $this->seo_title = $this->settings->seo_title;
        $this->seo_description = $this->settings->seo_description;
        $this->seo_keywords = $this->settings->seo_keywords;
        $this->seo_og_image = $this->settings->seo_og_image;

        $this->maintenance_mode = (bool) $this->settings->maintenance_mode;
        $this->google_analytics_id = $this->settings->google_analytics_id;
        $this->primary_color = $this->settings->primary_color;
        $this->secondary_color = $this->settings->secondary_color;
    }

    protected function rules()
    {
        return [
            'business_name' => 'nullable|string|max:255',
            'business_tagline' => 'nullable|string|max:255',
            'business_logo' => 'nullable|image|max:2048',
            'business_favicon' => 'nullable|image|max:1024',
            'currency' => 'nullable|string|max:10',
            'timezone' => 'nullable|string|max:100',
            'date_format' => 'nullable|string|max:50',

            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'contact_address' => 'nullable|string|max:255',
            'contact_city' => 'nullable|string|max:255',
            'contact_country' => 'nullable|string|max:255',
            'contact_map_embed' => 'nullable|string',

            'mail_from_name' => 'nullable|string|max:255',
            'mail_from_address' => 'nullable|email|max:255',
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'nullable|integer',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string|max:255',
            'smtp_encryption' => 'nullable|string|max:20',

            'social_facebook' => 'nullable|string|max:255',
            'social_twitter' => 'nullable|string|max:255',
            'social_instagram' => 'nullable|string|max:255',
            'social_linkedin' => 'nullable|string|max:255',
            'social_youtube' => 'nullable|string|max:255',

            'footer_text' => 'nullable|string',
            'footer_copyright' => 'nullable|string|max:255',

            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
            'seo_keywords' => 'nullable|string|max:255',
            'seo_og_image' => 'nullable|image|max:2048',

            'maintenance_mode' => 'boolean',
            'google_analytics_id' => 'nullable|string|max:50',
            'primary_color' => 'nullable|string|max:20',
            'secondary_color' => 'nullable|string|max:20',
        ];
    }

    public function save()
    {
        if (!auth()->user()->hasPermission('Settings', 'Edit')) {
            $this->dispatch('toast', message: "You don't have permission to edit settings.", type: 'error');
            return;
        }

        $this->validate();

        $this->settings->business_name = $this->business_name;
        $this->settings->business_tagline = $this->business_tagline;
        $this->settings->currency = $this->currency ?: 'USD';
        $this->settings->timezone = $this->timezone ?: 'UTC';
        $this->settings->date_format = $this->date_format ?: 'Y-m-d';

        $this->settings->contact_email = $this->contact_email;
        $this->settings->contact_phone = $this->contact_phone;
        $this->settings->contact_address = $this->contact_address;
        $this->settings->contact_city = $this->contact_city;
        $this->settings->contact_country = $this->contact_country;
        $this->settings->contact_map_embed = $this->contact_map_embed;

        $this->settings->mail_from_name = $this->mail_from_name;
        $this->settings->mail_from_address = $this->mail_from_address;
        $this->settings->smtp_host = $this->smtp_host;
        $this->settings->smtp_port = $this->smtp_port;
        $this->settings->smtp_username = $this->smtp_username;
        $this->settings->smtp_password = $this->smtp_password;
        $this->settings->smtp_encryption = $this->smtp_encryption;

        $this->settings->social_facebook = $this->social_facebook;
        $this->settings->social_twitter = $this->social_twitter;
        $this->settings->social_instagram = $this->social_instagram;
        $this->settings->social_linkedin = $this->social_linkedin;
        $this->settings->social_youtube = $this->social_youtube;

        $this->settings->footer_text = $this->footer_text;
        $this->settings->footer_copyright = $this->footer_copyright;
        $this->settings->footer_links = $this->footer_links;

        $this->settings->seo_title = $this->seo_title;
        $this->settings->seo_description = $this->seo_description;
        $this->settings->seo_keywords = $this->seo_keywords;

        $this->settings->maintenance_mode = (bool) $this->maintenance_mode;
        $this->settings->google_analytics_id = $this->google_analytics_id;
        $this->settings->primary_color = $this->primary_color;
        $this->settings->secondary_color = $this->secondary_color;

        if ($this->business_logo) {
            if ($this->settings->business_logo) {
                Storage::disk('public')->delete($this->settings->business_logo);
            }
            $this->settings->business_logo = $this->business_logo->store('settings', 'public');
        }

        if ($this->business_favicon) {
            if ($this->settings->business_favicon) {
                Storage::disk('public')->delete($this->settings->business_favicon);
            }
            $this->settings->business_favicon = $this->business_favicon->store('settings', 'public');
        }

        if ($this->seo_og_image) {
            if ($this->settings->seo_og_image) {
                Storage::disk('public')->delete($this->settings->seo_og_image);
            }
            $this->settings->seo_og_image = $this->seo_og_image->store('settings', 'public');
        }

        $this->settings->save();

        $this->business_logo = null;
        $this->business_favicon = null;
        $this->seo_og_image = null;

        $this->dispatch('toast', message: 'Settings saved.', type: 'success');
    }

    public function render()
    {
        return $this->view()->layout('layouts.app');
    }
};
?>

<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>General Settings</h1>
                <p>Manage your company's business, contact, email, social, footer, SEO and other settings.</p>
            </div>
            <div class="header-actions">
                <button type="button" wire:click="save" class="btn btn-primary">
                    <i class="fas fa-save" wire:loading.remove wire:target="save"></i>
                    <i class="fas fa-spinner fa-spin" wire:loading wire:target="save"></i>
                    Save Settings
                </button>
            </div>
        </div>

        <!-- Flash Messages -->
        @if (session()->has('success'))
            <div class="alert-flash alert-flash-success">
                <i class="fas fa-check-circle"></i>
                {{ session('success') }}
                <button class="alert-flash-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="alert-flash alert-flash-error">
                <i class="fas fa-exclamation-circle"></i>
                {{ session('error') }}
                <button class="alert-flash-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        @endif

        <div class="card gs-card" x-data="{ tab: 'business' }">
            <div class="card-header bg-white">
                <ul class="nav nav-tabs card-header-tabs" id="generalSettingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link" :class="{ active: tab==='business' }" @click="tab='business'">
                            <i class="fas fa-building me-1"></i> Business
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link" :class="{ active: tab==='contact' }" @click="tab='contact'">
                            <i class="fas fa-address-card me-1"></i> Contact
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link" :class="{ active: tab==='email' }" @click="tab='email'">
                            <i class="fas fa-envelope me-1"></i> Email
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link" :class="{ active: tab==='social' }" @click="tab='social'">
                            <i class="fas fa-share-alt me-1"></i> Social
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link" :class="{ active: tab==='footer' }" @click="tab='footer'">
                            <i class="fas fa-shoe-prints me-1"></i> Footer
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link" :class="{ active: tab==='seo' }" @click="tab='seo'">
                            <i class="fas fa-search me-1"></i> SEO
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link" :class="{ active: tab==='other' }" @click="tab='other'">
                            <i class="fas fa-sliders-h me-1"></i> Other
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <form wire:submit.prevent="save">
                    <div class="tab-content" id="generalSettingsTabsContent">

                        <!-- Business -->
                        <div x-show="tab==='business'" x-cloak>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Business Name</label>
                                    <input type="text" class="form-control @error('business_name') is-invalid @enderror" wire:model="business_name" placeholder="e.g. Agency ERP Demo">
                                    @error('business_name') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Business Tagline</label>
                                    <input type="text" class="form-control @error('business_tagline') is-invalid @enderror" wire:model="business_tagline" placeholder="Short tagline / slogan">
                                    @error('business_tagline') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Business Logo</label>
                                    @if ($settings->business_logo && !$business_logo)
                                        <div class="mb-2">
                                            <img src="{{ asset('storage/' . $settings->business_logo) }}" class="rounded border" style="max-height:60px;" alt="Logo">
                                        </div>
                                    @endif
                                    <input type="file" class="form-control @error('business_logo') is-invalid @enderror" wire:model="business_logo">
                                    @error('business_logo') <span class="text-danger">{{ $message }}</span> @enderror
                                    @if ($business_logo)
                                        <img src="{{ $business_logo->temporaryUrl() }}" class="rounded border mt-2" style="max-height:60px;" alt="Preview">
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Business Favicon</label>
                                    @if ($settings->business_favicon && !$business_favicon)
                                        <div class="mb-2">
                                            <img src="{{ asset('storage/' . $settings->business_favicon) }}" class="rounded border" style="max-height:40px;" alt="Favicon">
                                        </div>
                                    @endif
                                    <input type="file" class="form-control @error('business_favicon') is-invalid @enderror" wire:model="business_favicon">
                                    @error('business_favicon') <span class="text-danger">{{ $message }}</span> @enderror
                                    @if ($business_favicon)
                                        <img src="{{ $business_favicon->temporaryUrl() }}" class="rounded border mt-2" style="max-height:40px;" alt="Preview">
                                    @endif
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-medium">Currency</label>
                                    <select class="form-select @error('currency') is-invalid @enderror" wire:model="currency">
                                        @foreach (['USD','EUR','GBP','INR','AUD','CAD','JPY','CNY','ZAR','AED'] as $cur)
                                            <option value="{{ $cur }}">{{ $cur }}</option>
                                        @endforeach
                                    </select>
                                    @error('currency') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-medium">Timezone</label>
                                    <select class="form-select @error('timezone') is-invalid @enderror" wire:model="timezone">
                                        @foreach (\DateTimeZone::listIdentifiers() as $tz)
                                            <option value="{{ $tz }}">{{ $tz }}</option>
                                        @endforeach
                                    </select>
                                    @error('timezone') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-medium">Date Format</label>
                                    <select class="form-select @error('date_format') is-invalid @enderror" wire:model="date_format">
                                        <option value="Y-m-d">YYYY-MM-DD ({{ date('Y-m-d') }})</option>
                                        <option value="d-m-Y">DD-MM-YYYY ({{ date('d-m-Y') }})</option>
                                        <option value="m/d/Y">MM/DD/YYYY ({{ date('m/d/Y') }})</option>
                                        <option value="d/m/Y">DD/MM/YYYY ({{ date('d/m/Y') }})</option>
                                        <option value="F j, Y">Month D, YYYY ({{ date('F j, Y') }})</option>
                                    </select>
                                    @error('date_format') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Contact -->
                        <div x-show="tab==='contact'" x-cloak>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Contact Email</label>
                                    <input type="email" class="form-control @error('contact_email') is-invalid @enderror" wire:model="contact_email" placeholder="hello@example.com">
                                    @error('contact_email') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Contact Phone</label>
                                    <input type="text" class="form-control @error('contact_phone') is-invalid @enderror" wire:model="contact_phone" placeholder="+1 (555) 123-4567">
                                    @error('contact_phone') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Address</label>
                                    <input type="text" class="form-control @error('contact_address') is-invalid @enderror" wire:model="contact_address" placeholder="Street address">
                                    @error('contact_address') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-medium">City / State</label>
                                    <input type="text" class="form-control @error('contact_city') is-invalid @enderror" wire:model="contact_city" placeholder="City, State">
                                    @error('contact_city') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-medium">Country</label>
                                    <input type="text" class="form-control @error('contact_country') is-invalid @enderror" wire:model="contact_country" placeholder="Country">
                                    @error('contact_country') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-medium">Map Embed (URL or embed code)</label>
                                    <textarea class="form-control @error('contact_map_embed') is-invalid @enderror" wire:model="contact_map_embed" rows="3" placeholder="Google Maps embed URL or iframe code"></textarea>
                                    @error('contact_map_embed') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Email -->
                        <div x-show="tab==='email'" x-cloak>
                            <small class="text-muted d-block mb-3">
                                <i class="fas fa-info-circle me-1"></i>
                                SMTP fields below are stored for reference only — they are not wired into the application's mail configuration or used to send actual email.
                            </small>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">From Name</label>
                                    <input type="text" class="form-control @error('mail_from_name') is-invalid @enderror" wire:model="mail_from_name" placeholder="Agency ERP Demo">
                                    @error('mail_from_name') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">From Address</label>
                                    <input type="email" class="form-control @error('mail_from_address') is-invalid @enderror" wire:model="mail_from_address" placeholder="no-reply@example.com">
                                    @error('mail_from_address') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">SMTP Host</label>
                                    <input type="text" class="form-control @error('smtp_host') is-invalid @enderror" wire:model="smtp_host" placeholder="smtp.mailtrap.io">
                                    @error('smtp_host') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">SMTP Port</label>
                                    <input type="number" class="form-control @error('smtp_port') is-invalid @enderror" wire:model="smtp_port" placeholder="587">
                                    @error('smtp_port') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-medium">SMTP Username</label>
                                    <input type="text" class="form-control @error('smtp_username') is-invalid @enderror" wire:model="smtp_username">
                                    @error('smtp_username') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-medium">SMTP Password</label>
                                    <input type="password" class="form-control @error('smtp_password') is-invalid @enderror" wire:model="smtp_password">
                                    @error('smtp_password') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-medium">SMTP Encryption</label>
                                    <select class="form-select @error('smtp_encryption') is-invalid @enderror" wire:model="smtp_encryption">
                                        <option value="">None</option>
                                        <option value="tls">TLS</option>
                                        <option value="ssl">SSL</option>
                                    </select>
                                    @error('smtp_encryption') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Social -->
                        <div x-show="tab==='social'" x-cloak>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-medium"><i class="fab fa-facebook text-muted me-1"></i> Facebook URL</label>
                                    <input type="text" class="form-control @error('social_facebook') is-invalid @enderror" wire:model="social_facebook" placeholder="https://facebook.com/...">
                                    @error('social_facebook') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium"><i class="fab fa-twitter text-muted me-1"></i> Twitter / X URL</label>
                                    <input type="text" class="form-control @error('social_twitter') is-invalid @enderror" wire:model="social_twitter" placeholder="https://twitter.com/...">
                                    @error('social_twitter') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium"><i class="fab fa-instagram text-muted me-1"></i> Instagram URL</label>
                                    <input type="text" class="form-control @error('social_instagram') is-invalid @enderror" wire:model="social_instagram" placeholder="https://instagram.com/...">
                                    @error('social_instagram') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium"><i class="fab fa-linkedin text-muted me-1"></i> LinkedIn URL</label>
                                    <input type="text" class="form-control @error('social_linkedin') is-invalid @enderror" wire:model="social_linkedin" placeholder="https://linkedin.com/company/...">
                                    @error('social_linkedin') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium"><i class="fab fa-youtube text-muted me-1"></i> YouTube URL</label>
                                    <input type="text" class="form-control @error('social_youtube') is-invalid @enderror" wire:model="social_youtube" placeholder="https://youtube.com/@...">
                                    @error('social_youtube') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div x-show="tab==='footer'" x-cloak>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-medium">Footer Text</label>
                                    <textarea class="form-control @error('footer_text') is-invalid @enderror" wire:model="footer_text" rows="3" placeholder="Short description shown in the footer"></textarea>
                                    @error('footer_text') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Copyright Text</label>
                                    <input type="text" class="form-control @error('footer_copyright') is-invalid @enderror" wire:model="footer_copyright" placeholder="© {{ date('Y') }} Your Company. All rights reserved.">
                                    @error('footer_copyright') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-12">
                                    <small class="text-muted">Footer links are stored as JSON and managed programmatically; they are not editable from this form.</small>
                                </div>
                            </div>
                        </div>

                        <!-- SEO -->
                        <div x-show="tab==='seo'" x-cloak>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">SEO Title</label>
                                    <input type="text" class="form-control @error('seo_title') is-invalid @enderror" wire:model="seo_title" placeholder="Default meta title">
                                    @error('seo_title') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">SEO Keywords</label>
                                    <input type="text" class="form-control @error('seo_keywords') is-invalid @enderror" wire:model="seo_keywords" placeholder="comma, separated, keywords">
                                    @error('seo_keywords') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-medium">SEO Description</label>
                                    <textarea class="form-control @error('seo_description') is-invalid @enderror" wire:model="seo_description" rows="3" placeholder="Default meta description"></textarea>
                                    @error('seo_description') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Default OG Image</label>
                                    @if ($settings->seo_og_image && !$seo_og_image)
                                        <div class="mb-2">
                                            <img src="{{ asset('storage/' . $settings->seo_og_image) }}" class="rounded border" style="max-height:80px;" alt="OG Image">
                                        </div>
                                    @endif
                                    <input type="file" class="form-control @error('seo_og_image') is-invalid @enderror" wire:model="seo_og_image">
                                    @error('seo_og_image') <span class="text-danger">{{ $message }}</span> @enderror
                                    @if ($seo_og_image)
                                        <img src="{{ $seo_og_image->temporaryUrl() }}" class="rounded border mt-2" style="max-height:80px;" alt="Preview">
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Other -->
                        <div x-show="tab==='other'" x-cloak>
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="maintenance_mode" wire:model="maintenance_mode">
                                        <label class="form-check-label fw-medium" for="maintenance_mode">
                                            Maintenance Mode
                                        </label>
                                        <div><small class="text-muted">Flag only — toggling this does not actually put the site into maintenance mode.</small></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Google Analytics ID</label>
                                    <input type="text" class="form-control @error('google_analytics_id') is-invalid @enderror" wire:model="google_analytics_id" placeholder="G-XXXXXXXXXX">
                                    @error('google_analytics_id') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-medium">Primary Color</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" wire:model="primary_color" title="Primary color">
                                        <input type="text" class="form-control @error('primary_color') is-invalid @enderror" wire:model="primary_color" placeholder="#4F46E5">
                                    </div>
                                    @error('primary_color') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-medium">Secondary Color</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" wire:model="secondary_color" title="Secondary color">
                                        <input type="text" class="form-control @error('secondary_color') is-invalid @enderror" wire:model="secondary_color" placeholder="#818CF8">
                                    </div>
                                    @error('secondary_color') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save" wire:loading.remove wire:target="save"></i>
                            <i class="fas fa-spinner fa-spin" wire:loading wire:target="save"></i>
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
