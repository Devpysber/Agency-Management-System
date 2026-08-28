<?php

use Livewire\Component;
use App\Models\company;
use App\Models\contact;
use App\Models\staff;

new class extends Component
{
    // Personal Information
    public $first_name;
    public $last_name;
    public $email;
    public $phone;
    public $mobile;
    public $birth_date;
    public $gender;
    public $nationality;

    // Company Information
    public $company_id;
    public $job_title;
    public $department;
    
    // Address
    public $address;
    public $city;
    public $state;
    public $zip_code;
    public $country;
    
    // Additional Information
    public $lead_source;
    public $lead_status = 'new';
    public $notes;
    public $tags;
    
    // Social Media
    public $facebook;
    public $linkedin;
    public $twitter;
    public $instagram;
    
    // Communication Preferences
    public $email_opt_in = true;
    public $sms_opt_in = false;
    public $call_opt_in = true;
    
    // Assignment
    public $assigned_to;
    
    // Status
    public $status = 'active';
    
    public $companies;
    public $staffMembers;

    public function mount()
    {
        $this->companies = Company::all();
        $this->staffMembers = staff::orderBy('name')->get();
    }

   protected $rules = [
        // Personal Information
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'email' => 'required|email|max:255|unique:contacts,email',
        'phone' => 'nullable|string|max:20',
        'mobile' => 'nullable|string|max:20',
        'birth_date' => 'nullable|date|before:today',
        'gender' => 'nullable|string|in:male,female,other,prefer_not_to_say',
        'nationality' => 'nullable|string|max:100',

        // Company Information
        'company_id' => 'nullable|exists:companies,id',
        'job_title' => 'nullable|string|max:255',
        'department' => 'nullable|string|max:255',

        // Address
        'address' => 'nullable|string|max:500',
        'city' => 'nullable|string|max:100',
        'state' => 'nullable|string|max:100',
        'zip_code' => 'nullable|string|max:20',
        'country' => 'nullable|string|max:100',

        // Social Media
        'facebook' => 'nullable|url|max:255',
        'linkedin' => 'nullable|url|max:255',
        'twitter' => 'nullable|url|max:255',
        'instagram' => 'nullable|url|max:255',

        // Lead Information
        'assigned_to' => 'nullable|exists:staff,id',
        'tags' => 'nullable|string',
    ];
    public function save()
    {
        $this->validate();

        $contact = new Contact();
        $contact->first_name = $this->first_name;
        $contact->last_name = $this->last_name;
        $contact->email = $this->email;
        $contact->phone = $this->phone;
        $contact->mobile = $this->mobile;
        $contact->birth_date = $this->birth_date ?: null;
        $contact->gender = $this->gender;
        $contact->nationality = $this->nationality;
        $contact->company_id = $this->company_id ?: null;
        $contact->job_title = $this->job_title;
        $contact->department = $this->department;
        $contact->address = $this->address;
        $contact->city = $this->city;
        $contact->state = $this->state;
        $contact->zip_code = $this->zip_code;
        $contact->country = $this->country;
        $contact->social_media = [
            'facebook' => $this->facebook,
            'linkedin' => $this->linkedin,
            'twitter' => $this->twitter,
            'instagram' => $this->instagram,
        ];
        $contact->lead_source = $this->lead_source;
        $contact->lead_status = $this->lead_status;
        $contact->notes = $this->notes;
        $contact->tags = $this->tags
            ? array_values(array_filter(array_map('trim', explode(',', $this->tags))))
            : [];
        $contact->email_opt_in = $this->email_opt_in;
        $contact->sms_opt_in = $this->sms_opt_in;
        $contact->call_opt_in = $this->call_opt_in;
        $contact->assigned_to = $this->assigned_to ?: null;
        $contact->status = $this->status;
        $contact->save();

        session()->flash('success', 'Contact created successfully!');
        return redirect()->route('contacts.all');
    }

    public function cancel()
    {
        return $this->redirectRoute('contacts.all');
    }
};
?>

<div>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>Add New Contact</h1>
                <p>Create a new contact record in your CRM system.</p>
            </div>
            <div class="header-actions">
                <button type="button" class="btn btn-secondary" wire:click="cancel">
                    <i class="fas fa-arrow-left"></i> Cancel
                </button>
                <button type="submit" form="addContactForm" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Contact
                </button>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="card">
            <div class="card-body">
                <form id="addContactForm" wire:submit="save">
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-lg-8">
                            <!-- Personal Information -->
                            <div class="mb-4">
                                <h5 class="fw-semibold mb-3">
                                    <i class="fas fa-user text-primary me-2"></i>
                                    Personal Information
                                </h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-user me-1 text-muted"></i>
                                            First Name <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control @error('first_name') is-invalid @enderror" 
                                               wire:model="first_name" 
                                               placeholder="Enter first name">
                                        @error('first_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-user me-1 text-muted"></i>
                                            Last Name <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control @error('last_name') is-invalid @enderror" 
                                               wire:model="last_name" 
                                               placeholder="Enter last name">
                                        @error('last_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-envelope me-1 text-muted"></i>
                                            Email <span class="text-danger">*</span>
                                        </label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                               wire:model="email" 
                                               placeholder="contact@example.com">
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-phone me-1 text-muted"></i>
                                            Phone Number
                                        </label>
                                        <input type="tel" class="form-control @error('phone') is-invalid @enderror" 
                                               wire:model="phone" 
                                               placeholder="+1 234 567 8900">
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-mobile-alt me-1 text-muted"></i>
                                            Mobile Number
                                        </label>
                                        <input type="tel" class="form-control @error('mobile') is-invalid @enderror" 
                                               wire:model="mobile" 
                                               placeholder="+1 234 567 8900">
                                        @error('mobile')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-calendar-alt me-1 text-muted"></i>
                                            Birth Date
                                        </label>
                                        <input type="date" class="form-control @error('birth_date') is-invalid @enderror" 
                                               wire:model="birth_date">
                                        @error('birth_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-venus-mars me-1 text-muted"></i>
                                            Gender
                                        </label>
                                        <select class="form-select @error('gender') is-invalid @enderror" wire:model="gender">
                                            <option value="">Select Gender</option>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                            <option value="other">Other</option>
                                            <option value="prefer_not_to_say">Prefer not to say</option>
                                        </select>
                                        @error('gender')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-flag me-1 text-muted"></i>
                                            Nationality
                                        </label>
                                        <input type="text" class="form-control @error('nationality') is-invalid @enderror" 
                                               wire:model="nationality" 
                                               placeholder="Enter nationality">
                                        @error('nationality')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Company Information -->
                            <div class="mb-4">
                                <h5 class="fw-semibold mb-3">
                                    <i class="fas fa-building text-primary me-2"></i>
                                    Company Information
                                </h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-building me-1 text-muted"></i>
                                            Company
                                        </label>
                                        <select class="form-select @error('company_id') is-invalid @enderror" wire:model="company_id">
                                            <option value="">Select Company</option>
                                            @foreach($companies as $company)
                                                <option value="{{ $company->id }}">{{ $company->company_name }}</option>
                                            @endforeach
                                        </select>
                                        @error('company_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-briefcase me-1 text-muted"></i>
                                            Job Title
                                        </label>
                                        <input type="text" class="form-control @error('job_title') is-invalid @enderror" 
                                               wire:model="job_title" 
                                               placeholder="Enter job title">
                                        @error('job_title')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-users me-1 text-muted"></i>
                                            Department
                                        </label>
                                        <input type="text" class="form-control @error('department') is-invalid @enderror" 
                                               wire:model="department" 
                                               placeholder="Enter department">
                                        @error('department')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Address Information -->
                            <div class="mb-4">
                                <h5 class="fw-semibold mb-3">
                                    <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                    Address Information
                                </h5>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-road me-1 text-muted"></i>
                                            Street Address
                                        </label>
                                        <input type="text" class="form-control @error('address') is-invalid @enderror" 
                                               wire:model="address" 
                                               placeholder="Enter street address">
                                        @error('address')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-city me-1 text-muted"></i>
                                            City
                                        </label>
                                        <input type="text" class="form-control @error('city') is-invalid @enderror" 
                                               wire:model="city" 
                                               placeholder="Enter city">
                                        @error('city')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-map-pin me-1 text-muted"></i>
                                            State/Province
                                        </label>
                                        <input type="text" class="form-control @error('state') is-invalid @enderror" 
                                               wire:model="state" 
                                               placeholder="Enter state">
                                        @error('state')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-mailbox me-1 text-muted"></i>
                                            Postal Code
                                        </label>
                                        <input type="text" class="form-control @error('zip_code') is-invalid @enderror" 
                                               wire:model="zip_code" 
                                               placeholder="Enter postal code">
                                        @error('zip_code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-flag me-1 text-muted"></i>
                                            Country
                                        </label>
                                        <select class="form-select @error('country') is-invalid @enderror" wire:model="country">
                                            <option value="">Select Country</option>
                                            <option value="United States">United States</option>
                                            <option value="United Kingdom">United Kingdom</option>
                                            <option value="Canada">Canada</option>
                                            <option value="Australia">Australia</option>
                                            <option value="Germany">Germany</option>
                                            <option value="France">France</option>
                                            <option value="India">India</option>
                                            <option value="Pakistan">Pakistan</option>
                                            <option value="Other">Other</option>
                                        </select>
                                        @error('country')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Social Media -->
                            <div class="mb-4">
                                <h5 class="fw-semibold mb-3">
                                    <i class="fas fa-share-alt text-primary me-2"></i>
                                    Social Media
                                </h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fab fa-facebook text-primary me-1"></i>
                                            Facebook
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fab fa-facebook"></i></span>
                                            <input type="text" class="form-control @error('facebook') is-invalid @enderror" 
                                                   wire:model="facebook" 
                                                   placeholder="Facebook URL">
                                        </div>
                                        @error('facebook')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fab fa-linkedin text-primary me-1"></i>
                                            LinkedIn
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fab fa-linkedin"></i></span>
                                            <input type="text" class="form-control @error('linkedin') is-invalid @enderror" 
                                                   wire:model="linkedin" 
                                                   placeholder="LinkedIn URL">
                                        </div>
                                        @error('linkedin')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fab fa-twitter text-info me-1"></i>
                                            Twitter/X
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fab fa-twitter"></i></span>
                                            <input type="text" class="form-control @error('twitter') is-invalid @enderror" 
                                                   wire:model="twitter" 
                                                   placeholder="Twitter URL">
                                        </div>
                                        @error('twitter')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fab fa-instagram text-danger me-1"></i>
                                            Instagram
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fab fa-instagram"></i></span>
                                            <input type="text" class="form-control @error('instagram') is-invalid @enderror" 
                                                   wire:model="instagram" 
                                                   placeholder="Instagram URL">
                                        </div>
                                        @error('instagram')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-lg-4">
                            <!-- Lead Information -->
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="fw-semibold mb-0">
                                        <i class="fas fa-star text-warning me-2"></i>
                                        Lead Information
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-tag me-1 text-muted"></i>
                                            Lead Source
                                        </label>
                                        <select class="form-select @error('lead_source') is-invalid @enderror" wire:model="lead_source">
                                            <option value="">Select Source</option>
                                            <option value="website">Website</option>
                                            <option value="referral">Referral</option>
                                            <option value="social_media">Social Media</option>
                                            <option value="email">Email</option>
                                            <option value="phone">Phone Call</option>
                                            <option value="event">Event</option>
                                            <option value="advertisement">Advertisement</option>
                                            <option value="other">Other</option>
                                        </select>
                                        @error('lead_source')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-circle me-1 text-muted"></i>
                                            Lead Status
                                        </label>
                                        <select class="form-select @error('lead_status') is-invalid @enderror" wire:model="lead_status">
                                            <option value="new">🟢 New</option>
                                            <option value="contacted">🟡 Contacted</option>
                                            <option value="qualified">🔵 Qualified</option>
                                            <option value="lost">🔴 Lost</option>
                                            <option value="customer">✅ Customer</option>
                                        </select>
                                        @error('lead_status')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-user-tie me-1 text-muted"></i>
                                            Assign To
                                        </label>
                                        <select class="form-select @error('assigned_to') is-invalid @enderror" wire:model="assigned_to">
                                            <option value="">Unassigned</option>
                                            @foreach ($staffMembers as $member)
                                                <option value="{{ $member->id }}">{{ $member->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('assigned_to')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-tags me-1 text-muted"></i>
                                            Tags (comma separated)
                                        </label>
                                        <input type="text" class="form-control @error('tags') is-invalid @enderror" 
                                               wire:model="tags" 
                                               placeholder="vip, enterprise, priority">
                                        @error('tags')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Communication Preferences -->
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="fw-semibold mb-0">
                                        <i class="fas fa-envelope text-primary me-2"></i>
                                        Communication Preferences
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-check mb-2">
                                        <input type="checkbox" class="form-check-input @error('email_opt_in') is-invalid @enderror" 
                                               wire:model="email_opt_in" id="emailOptIn">
                                        <label class="form-check-label" for="emailOptIn">
                                            <i class="fas fa-envelope me-1"></i> Email Opt-in
                                        </label>
                                        @error('email_opt_in')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="form-check mb-2">
                                        <input type="checkbox" class="form-check-input @error('sms_opt_in') is-invalid @enderror" 
                                               wire:model="sms_opt_in" id="smsOptIn">
                                        <label class="form-check-label" for="smsOptIn">
                                            <i class="fas fa-sms me-1"></i> SMS Opt-in
                                        </label>
                                        @error('sms_opt_in')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input @error('call_opt_in') is-invalid @enderror" 
                                               wire:model="call_opt_in" id="callOptIn">
                                        <label class="form-check-label" for="callOptIn">
                                            <i class="fas fa-phone me-1"></i> Call Opt-in
                                        </label>
                                        @error('call_opt_in')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="fw-semibold mb-0">
                                        <i class="fas fa-circle me-2" style="color: #10B981;"></i>
                                        Contact Status
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-bolt me-1 text-warning"></i>
                                            Status
                                        </label>
                                        <select class="form-select @error('status') is-invalid @enderror" wire:model="status">
                                            <option value="active">🟢 Active</option>
                                            <option value="inactive">🔴 Inactive</option>
                                            <option value="blocked">⚫ Blocked</option>
                                        </select>
                                        @error('status')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-file-alt me-1 text-muted"></i>
                                            Notes
                                        </label>
                                        <textarea class="form-control @error('notes') is-invalid @enderror" 
                                                  wire:model="notes" 
                                                  rows="3" 
                                                  placeholder="Enter notes about this contact..."></textarea>
                                        @error('notes')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>