<?php

use Livewire\Component;
use Illuminate\Support\Facades\Hash;

new class extends Component
{
    // Account (users table) — read-only
    public string $name = '';
    public string $email = '';

    // Contact (contacts table) — read-only
    public string $phone = '';
    public string $mobile = '';
    public string $job_title = '';
    public string $address = '';
    public string $city = '';
    public string $state = '';
    public string $zip_code = '';
    public string $country = '';

    // Company — business & billing details (companies table) — read-only
    public string $legal_entity_name = '';
    public string $company_email = '';
    public string $company_phone = '';
    public string $company_website = '';
    public string $company_registration_no = '';
    public string $company_industry = '';
    public string $gstin = '';
    public string $pan = '';
    public string $tax_registration_number = '';
    public string $billing_address = '';
    public string $billing_city = '';
    public string $billing_state = '';
    public string $billing_zip = '';
    public string $billing_country = '';

    // Password change — the one thing the client may change themselves
    public string $current_password = '';
    public string $new_password = '';
    public string $new_password_confirmation = '';

    public $company;
    public $hasContact = false;

    private array $companyFields = [
        'legal_entity_name', 'company_email', 'company_phone', 'company_website',
        'company_registration_no', 'company_industry', 'gstin', 'pan',
        'tax_registration_number', 'billing_address', 'billing_city',
        'billing_state', 'billing_zip', 'billing_country',
    ];

    public function mount(): void
    {
        $user = auth()->user();
        $contact = $user->contact;

        $this->name = (string) $user->name;
        $this->email = (string) $user->email;
        $this->company = $contact?->company;
        $this->hasContact = (bool) $contact;

        if ($contact) {
            foreach (['phone', 'mobile', 'job_title', 'address', 'city', 'state', 'zip_code', 'country'] as $f) {
                $this->{$f} = (string) ($contact->{$f} ?? '');
            }
        }

        if ($this->company) {
            foreach ($this->companyFields as $f) {
                $this->{$f} = (string) ($this->company->{$f} ?? '');
            }
        }
    }

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($this->current_password, auth()->user()->password)) {
            $this->addError('current_password', 'Current password is incorrect.');
            return;
        }

        auth()->user()->update(['password' => $this->new_password]); // 'hashed' cast hashes it

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        $this->dispatch('cp-toast', message: 'Password changed', type: 'success');
    }

    public function render()
    {
        return $this->view()->layout('layouts.client');
    }
};
?>

@php
    $initials = collect(explode(' ', trim($name)))->filter()->take(2)
        ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    $row = fn ($label, $value) => ['label' => $label, 'value' => $value];

    $accountRows = array_filter([
        $row('Full Name', $name),
        $row('Email', $email),
        $hasContact ? $row('Phone', $phone) : null,
        $hasContact ? $row('Mobile', $mobile) : null,
        $hasContact ? $row('Job Title', $job_title) : null,
        $hasContact ? $row('Address', $address) : null,
        $hasContact ? $row('City', $city) : null,
        $hasContact ? $row('State / Region', $state) : null,
        $hasContact ? $row('Postal Code', $zip_code) : null,
        $hasContact ? $row('Country', $country) : null,
    ]);

    $companyRows = $company ? [
        $row('Legal Entity Name', $legal_entity_name),
        $row('GSTIN', $gstin),
        $row('PAN', $pan),
        $row('Tax Registration No.', $tax_registration_number),
        $row('Company Registration No.', $company_registration_no),
        $row('Company Email', $company_email),
        $row('Company Phone', $company_phone),
        $row('Website', $company_website),
        $row('Industry', $company_industry),
        $row('Billing Address', $billing_address),
        $row('Billing City', $billing_city),
        $row('Billing State / Region', $billing_state),
        $row('Billing Postal Code', $billing_zip),
        $row('Billing Country', $billing_country),
    ] : [];
@endphp

<div>
    <style>
        .cp-kv { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 2px 28px; }
        .cp-kv .kv { padding: 11px 0; border-bottom: 1px solid var(--cp-border, #eef0f3); min-width: 0; }
        .cp-kv .kv.span-2 { grid-column: 1 / -1; }
        .cp-kv .kv-l { font-size: 11px; text-transform: uppercase; letter-spacing: .04em; color: var(--cp-text-faint, #9ca3af); }
        .cp-kv .kv-v { font-size: 14px; font-weight: 600; color: var(--cp-text, #1f2937); margin-top: 3px; word-break: break-word; }
        .cp-kv .kv-v.is-empty { font-weight: 400; color: var(--cp-text-faint, #b3b8c2); }
        @media (max-width: 640px) { .cp-kv { grid-template-columns: 1fr; } }
        .cp-locked-note { display: flex; align-items: center; gap: 8px; font-size: 12.5px;
            color: var(--cp-text-faint, #6b7280); margin-bottom: 14px; }
    </style>

    <div class="cp-page-head">
        <div>
            <h1>My Profile</h1>
            <p>Your details are maintained by your account manager. Contact them for any change.</p>
        </div>
    </div>

    <div class="cp-card" style="margin-bottom:20px;">
        <div class="cp-card-body">
            <div class="cp-identity">
                <div class="cp-avatar">{{ $initials ?: 'C' }}</div>
                <div>
                    <h2>{{ $name }}</h2>
                    <span>{{ $email }}@if ($company) · {{ $company->company_name }} @endif</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Account + contact details (read-only) --}}
    <div class="cp-card" style="margin-bottom:20px;">
        <div class="cp-card-head"><h3><i class="fas fa-id-card"></i> Account Details</h3></div>
        <div class="cp-card-body">
            <div class="cp-locked-note"><i class="fas fa-lock"></i> Read-only — set up from the information you provided.</div>
            <div class="cp-kv">
                @foreach ($accountRows as $r)
                    <div class="kv {{ in_array($r['label'], ['Job Title', 'Address']) ? 'span-2' : '' }}">
                        <div class="kv-l">{{ $r['label'] }}</div>
                        <div class="kv-v {{ $r['value'] === '' ? 'is-empty' : '' }}">{{ $r['value'] !== '' ? $r['value'] : '—' }}</div>
                    </div>
                @endforeach
                @unless ($hasContact)
                    <div class="kv span-2"><span class="cp-help">No contact record is linked to your account.</span></div>
                @endunless
            </div>
        </div>
    </div>

    {{-- Company: business & billing (read-only) --}}
    @if ($company)
        <div class="cp-card" style="margin-bottom:20px;">
            <div class="cp-card-head">
                <h3><i class="fas fa-building"></i> Business &amp; Billing Details</h3>
                <span class="cp-help">Applies to {{ $company->company_name }}</span>
            </div>
            <div class="cp-card-body">
                <div class="cp-locked-note"><i class="fas fa-lock"></i> Read-only — managed by your account manager.</div>
                <div class="cp-kv">
                    @foreach ($companyRows as $r)
                        <div class="kv {{ in_array($r['label'], ['Legal Entity Name', 'Billing Address']) ? 'span-2' : '' }}">
                            <div class="kv-l">{{ $r['label'] }}</div>
                            <div class="kv-v {{ $r['value'] === '' ? 'is-empty' : '' }}">{{ $r['value'] !== '' ? $r['value'] : '—' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Password — client-changeable --}}
    <div class="cp-card">
        <div class="cp-card-head"><h3><i class="fas fa-key"></i> Change Password</h3></div>
        <div class="cp-card-body">
            <form wire:submit="updatePassword">
                <div class="cp-form-grid">
                    <div class="cp-form-row span-2">
                        <label class="cp-label">Current Password</label>
                        <input type="password" class="cp-input @error('current_password') is-invalid @enderror" wire:model="current_password" autocomplete="current-password">
                        @error('current_password') <span class="cp-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="cp-form-row">
                        <label class="cp-label">New Password</label>
                        <input type="password" class="cp-input @error('new_password') is-invalid @enderror" wire:model="new_password" autocomplete="new-password">
                        @error('new_password') <span class="cp-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="cp-form-row">
                        <label class="cp-label">Confirm New Password</label>
                        <input type="password" class="cp-input" wire:model="new_password_confirmation" autocomplete="new-password">
                    </div>
                    <div class="cp-form-row span-2">
                        <span class="cp-help">At least 8 characters.</span>
                    </div>
                </div>
                <div class="cp-form-foot">
                    <button type="submit" class="cp-btn cp-btn-primary" wire:loading.attr="disabled" wire:target="updatePassword">
                        <i class="fas fa-key"></i> Update password
                    </button>
                    <span class="cp-help" wire:loading wire:target="updatePassword">Updating…</span>
                </div>
            </form>
        </div>
    </div>
</div>
