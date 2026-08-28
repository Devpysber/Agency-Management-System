<?php

use Livewire\Component;
use App\Models\Company;

new class extends Component
{
    public $companyId;
    public $company;
    public $company_name;
    public $company_registration_no;
    public $company_email;
    public $company_phone;
    public $company_address;
    public $company_city;
    public $company_state;
    public $company_zip;
    public $company_country;
    public $company_website;
    public $company_industry;
    public $company_size;
    public $company_rating;
    public $company_founded_date;
    public $company_owner;
    public $company_tags;
    public $company_notes;
    public $company_type;
    public $company_facebook;
    public $company_twitter;
    public $company_linkedin;
    public $company_instagram;
    public $company_youtube;
    public $company_github;
    public $status;
    public $company_employee_count;
    public $company_description;
    public $company_postal_code;

    public function mount($id)
    {
        $this->company = Company::findOrFail($id);
        $this->companyId = $this->company->id;
        $this->company_name = $this->company->company_name;
        $this->company_registration_no = $this->company->company_registration_no;
        $this->company_email = $this->company->company_email;
        $this->company_phone = $this->company->company_phone;
        $this->company_address = $this->company->company_address;
        $this->company_city = $this->company->company_city;
        $this->company_state = $this->company->company_state;
        $this->company_zip = $this->company->company_zip;
        $this->company_country = $this->company->company_country;
        $this->company_website = $this->company->company_website;
        $this->company_industry = $this->company->company_industry;
        $this->company_size = $this->company->company_size;
        $this->company_rating = $this->company->company_rating;
        $this->company_founded_date = $this->company->company_founded_date;
        $this->company_owner = $this->company->company_owner;
        $this->company_tags = $this->company->company_tags;
        $this->company_notes = $this->company->company_notes;
        $this->company_type = $this->company->company_type;
        $socialMedia = json_decode($this->company->company_social ?? '{}', true) ?: [];
        $this->company_facebook = $socialMedia['facebook'] ?? '';
        $this->company_twitter = $socialMedia['twitter'] ?? '';
        $this->company_linkedin = $socialMedia['linkedin'] ?? '';
        $this->company_instagram = $socialMedia['instagram'] ?? '';
        $this->company_youtube = $socialMedia['youtube'] ?? '';
        $this->company_github = $socialMedia['github'] ?? '';
        $this->status = $this->company->status;
        $this->company_employee_count = $this->company->company_employee_count;
        $this->company_description = $this->company->company_description;
        $this->company_postal_code = $this->company->company_postal_code;
    }

    public function update()
    {
        $this->validate([
            'company_name' => 'required|string|max:255',
            'company_email' => 'required|email|unique:companies,company_email,' . $this->companyId,
            'company_phone' => 'nullable|string|max:20',
            'company_website' => 'nullable|url|max:255',
            'company_registration_no' => 'nullable|string|max:100',
            'company_industry' => 'nullable|string|max:100',
            'company_type' => 'nullable|string|max:100',
            'company_size' => 'nullable|string|max:50',
            'company_rating' => 'nullable|numeric|min:0|max:5',
            'company_founded_date' => 'nullable|date',
            'company_owner' => 'nullable|string|max:255',
            'company_tags' => 'nullable|string',
            'company_notes' => 'nullable|string',
            'company_address' => 'nullable|string|max:255',
            'company_city' => 'nullable|string|max:100',
            'company_state' => 'nullable|string|max:100',
            'company_zip' => 'nullable|string|max:20',
            'company_country' => 'nullable|string|max:100',
            'company_facebook' => 'nullable|url|max:255',
            'company_twitter' => 'nullable|url|max:255',
            'company_linkedin' => 'nullable|url|max:255',
            'company_instagram' => 'nullable|url|max:255',
            'company_youtube' => 'nullable|url|max:255',
            'company_github' => 'nullable|url|max:255',
            'status' => 'nullable|string',
            'company_employee_count' => 'nullable|string',
            'company_description' => 'nullable|string',
            'company_postal_code' => 'nullable|string|max:20',
        ], [
            'company_name.required' => 'Please enter the company name.',
            'company_email.required' => 'Please enter a company email address.',
            'company_email.email' => 'Please enter a valid email address.',
            'company_email.unique' => 'A company with this email already exists.',
        ]);

        try {
            $this->company->update([
                'company_name' => $this->company_name,
                'company_registration_no' => $this->company_registration_no,
                'company_email' => $this->company_email,
                'company_phone' => $this->company_phone,
                'company_address' => $this->company_address,
                'company_city' => $this->company_city,
                'company_state' => $this->company_state,
                'company_zip' => $this->company_zip,
                'company_country' => $this->company_country,
                'company_website' => $this->company_website,
                'company_industry' => $this->company_industry,
                'company_size' => $this->company_size,
                'company_rating' => $this->company_rating,
                'company_founded_date' => $this->company_founded_date,
                'company_owner' => $this->company_owner,
                'company_tags' => $this->company_tags,
                'company_notes' => $this->company_notes,
                'company_type' => $this->company_type,
                'company_social' => json_encode(array_filter([
                    'facebook' => $this->company_facebook,
                    'twitter' => $this->company_twitter,
                    'linkedin' => $this->company_linkedin,
                    'instagram' => $this->company_instagram,
                    'youtube' => $this->company_youtube,
                    'github' => $this->company_github,
                ])),
                'status' => $this->status ?: 'active',
                'company_employee_count' => $this->company_employee_count,
                'company_description' => $this->company_description,
                'company_postal_code' => $this->company_postal_code,
            ]);

            session()->flash('success', 'Company updated successfully!');

            return redirect(route('companies.all'));

        } catch (\Exception $e) {
            session()->flash('error', 'Error updating company: ' . $e->getMessage());
        }
    }

    public function cancel()
    {
        return $this->redirectRoute('companies.show', $this->companyId);
    }

    public function render()
    {
        return $this->view([
            'staffMembers' => \App\Models\staff::orderBy('name')->get(),
        ]);
    }
};
?>

<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>Edit Company</h1>
                <p>Update company information in your CRM system.</p>
            </div>
            <div class="header-actions">
                <button type="button" class="btn btn-secondary" wire:click="cancel">
                    <i class="fas fa-arrow-left"></i> Cancel
                </button>
                <button type="submit" form="editCompanyForm" class="btn btn-primary">
                    <i class="fas fa-save" wire:loading.remove></i>
                    <i class="fas fa-spinner fa-spin" wire:loading></i>
                    Update Company
                </button>
            </div>
        </div>

        <!-- Company Form -->
        <div class="card">
            <div class="card-body">
                <form id="editCompanyForm" wire:submit="update">
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-lg-8">
                            <!-- Basic Information -->
                            <div class="mb-4">
                                <h5 class="fw-semibold mb-3">
                                    <i class="fas fa-info-circle text-primary me-2"></i>
                                    Basic Information
                                </h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-building me-1 text-muted"></i>
                                            Company Name <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control @error('company_name') is-invalid @enderror" 
                                               wire:model="company_name" placeholder="Enter company name">
                                        @error('company_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-hashtag me-1 text-muted"></i>
                                            Company Registration No.
                                        </label>
                                        <input type="text" class="form-control @error('company_registration_no') is-invalid @enderror" 
                                               wire:model="company_registration_no" placeholder="Enter registration number">
                                        @error('company_registration_no')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-globe me-1 text-muted"></i>
                                            Website
                                        </label>
                                        <input type="url" class="form-control @error('company_website') is-invalid @enderror" 
                                               wire:model="company_website" placeholder="https://example.com">
                                        @error('company_website')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-envelope me-1 text-muted"></i>
                                            Company Email <span class="text-danger">*</span>
                                        </label>
                                        <input type="email" class="form-control @error('company_email') is-invalid @enderror" 
                                               wire:model="company_email" placeholder="info@company.com">
                                        @error('company_email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-phone me-1 text-muted"></i>
                                            Phone Number
                                        </label>
                                        <input type="tel" class="form-control @error('company_phone') is-invalid @enderror" 
                                               wire:model="company_phone" placeholder="+1 234 567 8900">
                                        @error('company_phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-industry me-1 text-muted"></i>
                                            Industry
                                        </label>
                                        <select class="form-select @error('company_industry') is-invalid @enderror" 
                                                wire:model="company_industry">
                                            <option value="">Select Industry</option>
                                            <option value="Technology">Technology</option>
                                            <option value="Healthcare">Healthcare</option>
                                            <option value="Finance">Finance</option>
                                            <option value="Education">Education</option>
                                            <option value="Retail">Retail</option>
                                            <option value="Manufacturing">Manufacturing</option>
                                            <option value="Real Estate">Real Estate</option>
                                            <option value="Transportation">Transportation</option>
                                            <option value="Other">Other</option>
                                        </select>
                                        @error('company_industry')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-tag me-1 text-muted"></i>
                                            Company Type
                                        </label>
                                        <select class="form-select @error('company_type') is-invalid @enderror" 
                                                wire:model="company_type">
                                            <option value="">Select Type</option>
                                            <option value="Public Limited">Public Limited</option>
                                            <option value="Private Limited">Private Limited</option>
                                            <option value="Partnership">Partnership</option>
                                            <option value="Sole Proprietorship">Sole Proprietorship</option>
                                            <option value="Non-Profit">Non-Profit</option>
                                            <option value="Government">Government</option>
                                            <option value="Other">Other</option>
                                        </select>
                                        @error('company_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-users me-1 text-muted"></i>
                                            Employee Count
                                        </label>
                                        <select class="form-select @error('company_size') is-invalid @enderror" 
                                                wire:model="company_size">
                                            <option value="">Select Range</option>
                                            <option value="1-10">1-10</option>
                                            <option value="11-50">11-50</option>
                                            <option value="51-200">51-200</option>
                                            <option value="201-500">201-500</option>
                                            <option value="501-1000">501-1000</option>
                                            <option value="1000+">1000+</option>
                                        </select>
                                        @error('company_size')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-calendar-alt me-1 text-muted"></i>
                                            Founded Date
                                        </label>
                                        <input type="date" class="form-control @error('company_founded_date') is-invalid @enderror" 
                                               wire:model="company_founded_date">
                                        @error('company_founded_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-star me-1 text-muted"></i>
                                            Rating
                                        </label>
                                        <select class="form-select @error('company_rating') is-invalid @enderror" 
                                                wire:model="company_rating">
                                            <option value="0">Select Rating</option>
                                            <option value="1">⭐ 1 Star</option>
                                            <option value="2">⭐⭐ 2 Stars</option>
                                            <option value="3">⭐⭐⭐ 3 Stars</option>
                                            <option value="4">⭐⭐⭐⭐ 4 Stars</option>
                                            <option value="5">⭐⭐⭐⭐⭐ 5 Stars</option>
                                        </select>
                                        @error('company_rating')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-user-tie me-1 text-muted"></i>
                                            Company Owner
                                        </label>
                                        <select class="form-select @error('company_owner') is-invalid @enderror" wire:model="company_owner">
                                            <option value="">Select Owner</option>
                                            @foreach ($staffMembers as $member)
                                                <option value="{{ $member->name }}">{{ $member->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('company_owner')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-tags me-1 text-muted"></i>
                                            Tags (comma separated)
                                        </label>
                                        <input type="text" class="form-control @error('company_tags') is-invalid @enderror" 
                                               wire:model="company_tags" placeholder="Technology, Enterprise, B2B">
                                        @error('company_tags')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-align-left me-1 text-muted"></i>
                                            Description / Notes
                                        </label>
                                        <textarea class="form-control @error('company_notes') is-invalid @enderror" 
                                                  wire:model="company_notes" rows="3" placeholder="Enter company description..."></textarea>
                                        @error('company_notes')
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
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-road me-1 text-muted"></i>
                                            Street Address
                                        </label>
                                        <input type="text" class="form-control @error('company_address') is-invalid @enderror" 
                                               wire:model="company_address" placeholder="Enter street address">
                                        @error('company_address')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-city me-1 text-muted"></i>
                                            City
                                        </label>
                                        <input type="text" class="form-control @error('company_city') is-invalid @enderror" 
                                               wire:model="company_city" placeholder="Enter city">
                                        @error('company_city')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-map-pin me-1 text-muted"></i>
                                            State/Province
                                        </label>
                                        <input type="text" class="form-control @error('company_state') is-invalid @enderror" 
                                               wire:model="company_state" placeholder="Enter state">
                                        @error('company_state')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-mailbox me-1 text-muted"></i>
                                            Postal Code
                                        </label>
                                        <input type="text" class="form-control @error('company_zip') is-invalid @enderror" 
                                               wire:model="company_zip" placeholder="Enter postal code">
                                        @error('company_zip')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-flag me-1 text-muted"></i>
                                            Country
                                        </label>
                                        <select class="form-select @error('company_country') is-invalid @enderror" 
                                                wire:model="company_country">
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
                                        @error('company_country')
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
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium">
                                            <i class="fab fa-facebook text-primary me-1"></i>
                                            Facebook
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fab fa-facebook"></i></span>
                                            <input type="text" class="form-control @error('company_facebook') is-invalid @enderror" wire:model="company_facebook" placeholder="Facebook URL">
                                        </div>
                                        @error('company_facebook')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium">
                                            <i class="fab fa-twitter text-info me-1"></i>
                                            Twitter/X
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fab fa-twitter"></i></span>
                                            <input type="text" class="form-control @error('company_twitter') is-invalid @enderror" wire:model="company_twitter" placeholder="Twitter URL">
                                        </div>
                                        @error('company_twitter')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium">
                                            <i class="fab fa-linkedin text-primary me-1"></i>
                                            LinkedIn
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fab fa-linkedin"></i></span>
                                            <input type="text" class="form-control @error('company_linkedin') is-invalid @enderror" wire:model="company_linkedin" placeholder="LinkedIn URL">
                                        </div>
                                        @error('company_linkedin')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium">
                                            <i class="fab fa-instagram text-danger me-1"></i>
                                            Instagram
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fab fa-instagram"></i></span>
                                            <input type="text" class="form-control @error('company_instagram') is-invalid @enderror" wire:model="company_instagram" placeholder="Instagram URL">
                                        </div>
                                        @error('company_instagram')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium">
                                            <i class="fab fa-youtube text-danger me-1"></i>
                                            YouTube
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fab fa-youtube"></i></span>
                                            <input type="text" class="form-control @error('company_youtube') is-invalid @enderror" wire:model="company_youtube" placeholder="YouTube URL">
                                        </div>
                                        @error('company_youtube')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium">
                                            <i class="fab fa-github text-dark me-1"></i>
                                            GitHub
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fab fa-github"></i></span>
                                            <input type="text" class="form-control @error('company_github') is-invalid @enderror" wire:model="company_github" placeholder="GitHub URL">
                                        </div>
                                        @error('company_github')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column - Additional Info -->
                        <div class="col-lg-4">
                            <!-- Company Status -->
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="fw-semibold mb-0">
                                        <i class="fas fa-circle me-2" style="color: #10B981;"></i>
                                        Company Status
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-bolt me-1 text-warning"></i>
                                            Status
                                        </label>
                                        <select class="form-select" wire:model="status">
                                            <option value="active">🟢 Active</option>
                                            <option value="inactive">🔴 Inactive</option>
                                            <option value="pending">🟡 Pending</option>
                                            <option value="suspended">⚫ Suspended</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-calendar-alt me-1 text-muted"></i>
                                            Last Updated
                                        </label>
                                        <p class="mb-0 text-muted">{{ $company->updated_at ? $company->updated_at->diffForHumans() : 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Actions -->
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="fw-semibold mb-0">
                                        <i class="fas fa-bolt me-2"></i>
                                        Quick Actions
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <button type="button" class="btn btn-outline-primary" onclick="window.location.href='{{ route('contacts.add') }}'">
                                            <i class="fas fa-user-plus me-2"></i>
                                            Add Contact
                                        </button>
                                        <button type="button" class="btn btn-outline-success" onclick="window.location.href='{{ route('deals.add') }}'">
                                            <i class="fas fa-file-invoice me-2"></i>
                                            Create Deal
                                        </button>
                                        <button type="button" class="btn btn-outline-info" onclick="window.location.href='{{ route('calendar.schedule') }}'">
                                            <i class="fas fa-calendar-plus me-2"></i>
                                            Schedule Meeting
                                        </button>
                                        <button type="button" class="btn btn-outline-warning" onclick="window.location.href='{{ route('communications.emails') }}'">
                                            <i class="fas fa-envelope me-2"></i>
                                            Send Email
                                        </button>
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