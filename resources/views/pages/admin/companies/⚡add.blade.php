<?php

use Livewire\Component;
use App\Models\company;
use App\Models\Contact;
use App\Models\User;
use App\Models\Project;
use App\Services\Notifier;
use Illuminate\Support\Str;

new class extends Component
{
    //
    public $company_name;
    public $gstin;
    public $pan;
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
    public $status = 'active';
    public $company_type;
    public $company_employee_count;
    public $company_description;
    public $company_postal_code;
    public $company_facebook;
    public $company_twitter;
    public $company_linkedin;
    public $company_instagram;
    public $company_youtube;
    public $company_github;

    // Selected tag chips
    public array $tags = [];
    public array $tagOptions = ['Technology', 'Enterprise', 'B2B', 'B2C', 'SMB', 'Startup', 'Agency', 'Non-Profit', 'Priority'];

    // Optional client portal login for this company
    public bool $create_client_login = false;
    public $client_name;
    public $client_email;
    public $client_project_id;

    protected $rules = [
        'company_name' => 'required|string|max:255',
        'gstin' => 'nullable|string|regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][0-9A-Z]Z[0-9A-Z]$/',
        'pan' => 'nullable|string|regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/',
        'company_registration_no' => 'nullable|string|max:100',
        'company_email' => 'required|email|unique:companies,company_email',
        'company_phone' => 'nullable|string|max:20',
        'company_website' => 'nullable|url|max:255',
        'company_industry' => 'nullable|string|max:100',
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
        'status' => 'nullable|string',
        'company_type' => 'nullable|string|max:100',
        'company_employee_count' => 'nullable|string',
        'company_description' => 'nullable|string',
        'company_postal_code' => 'nullable|string|max:20',
        'company_facebook' => 'nullable|url|max:255',
        'company_twitter' => 'nullable|url|max:255',
        'company_linkedin' => 'nullable|url|max:255',
        'company_instagram' => 'nullable|url|max:255',
        'company_youtube' => 'nullable|url|max:255',
        'company_github' => 'nullable|url|max:255',
        'client_name' => 'required_if:create_client_login,true|nullable|string|max:255',
        'client_email' => 'required_if:create_client_login,true|nullable|email|max:255',
        'client_project_id' => 'nullable|exists:projects,id',
    ];

    protected $messages = [
        'company_name.required' => 'Please enter the company name.',
        'company_email.required' => 'Please enter a company email address.',
        'company_email.email' => 'Please enter a valid email address.',
        'company_email.unique' => 'A company with this email already exists.',
        'gstin.regex' => 'Enter a valid 15-character GSTIN (e.g. 29ABCDE1234F1Z5).',
        'pan.regex' => 'Enter a valid 10-character PAN (e.g. ABCDE1234F).',
        'client_email.required_if' => 'A login email is required to create the client account.',
        'client_name.required_if' => 'A contact name is required to create the client account.',
    ];

    public function updatedGstin($v)
    {
        $this->gstin = strtoupper(trim((string) $v));
    }

    public function updatedPan($v)
    {
        $this->pan = strtoupper(trim((string) $v));
    }
    // form submit ..
    public function form_submit()
    {
        $this->validate();

        if ($this->create_client_login && User::where('email', $this->client_email)->exists()) {
            $this->addError('client_email', 'A user with this email already exists.');
            return;
        }

        $company = new company;
        $company->company_name = $this->company_name;
        $company->gstin = $this->gstin ?: null;
        $company->pan = $this->pan ?: null;
        $company->company_registration_no = $this->company_registration_no;
        $company->company_email = $this->company_email;
        $company->company_phone = $this->company_phone;
        $company->company_address = $this->company_address;
        $company->company_city = $this->company_city;
        $company->company_state = $this->company_state;
        $company->company_zip = $this->company_zip;
        $company->company_country = $this->company_country;
        $company->company_website = $this->company_website;
        $company->company_industry = $this->company_industry;
        $company->company_size = $this->company_size;
        $company->company_rating = $this->company_rating;
        $company->company_founded_date = $this->company_founded_date;
        $company->company_owner = $this->company_owner;
        $company->company_tags = implode(', ', $this->tags);
        $company->company_notes = $this->company_notes;
        $company->status = $this->status ?: 'active';
        $company->company_type = $this->company_type;
        $company->company_employee_count = $this->company_employee_count;
        $company->company_description = $this->company_description;
        $company->company_postal_code = $this->company_postal_code;
        $company->company_social = json_encode(array_filter([
            'facebook' => $this->company_facebook,
            'twitter' => $this->company_twitter,
            'linkedin' => $this->company_linkedin,
            'instagram' => $this->company_instagram,
            'youtube' => $this->company_youtube,
            'github' => $this->company_github,
        ]));
        $company->save();

        $msg = 'Company created successfully';

        if ($this->create_client_login) {
            $password = Str::password(12);
            $user = User::create([
                'name' => $this->client_name,
                'email' => $this->client_email,
                'password' => $password,
                'role' => 'client',
            ]);

            $parts = explode(' ', trim($this->client_name), 2);
            Contact::create([
                'first_name' => $parts[0] ?? $this->client_name,
                'last_name' => $parts[1] ?? '',
                'email' => $this->client_email,
                'company_id' => $company->id,
                'user_id' => $user->id,
                'status' => 'active',
            ]);

            if ($this->client_project_id) {
                Project::where('id', $this->client_project_id)->update(['company_id' => $company->id]);
            }

            Notifier::push($user, 'Welcome to the client portal', [
                'body' => 'Your account for ' . $company->company_name . ' is ready. You can track project progress, payments and documents here.',
                'url' => route('client.dashboard'),
                'icon' => 'fa-door-open',
                'level' => 'success',
            ]);

            $msg .= '. Client login: ' . $this->client_email . ' / ' . $password . ' (share securely).';
        }

        session()->flash('success', $msg);

        return redirect()->route('companies.all');
    }

    public function render()
    {
        return $this->view([
            'staffMembers' => \App\Models\staff::orderBy('name')->get(),
            'projects' => Project::orderBy('name')->get(['id', 'name']),
        ]);
    }
};
?>

<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>Add New Company</h1>
                <p>Create a new company record in your CRM system.</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('companies.all') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Companies
                </a>
                <button type="button" form="companyForm" wire:click="form_submit" class="btn btn-primary">
                    <i class="fas fa-save" wire:loading.remove></i> <i class="fas fa-spinner fa-spin" wire:loading></i> Save Company
                </button>
            </div>
        </div>

        <!-- Company Form -->
        <div class="card">
            <div class="card-body">
                <form id="companyForm" >
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-lg-8">
                            <!-- Basic Information -->
                            <div class="mb-4">
                                <h5 class="fw-semibold mb-3">
                                    <i class="fas fa-building text-primary me-2"></i>
                                    Basic Information
                                </h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-building me-1 text-muted"></i>
                                            Company Name <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" wire:model.live="company_name" placeholder="Enter company name">
                                        @error('company_name')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-hashtag me-1 text-muted"></i>
                                            Company Registration No.
                                        </label>
                                        <input type="text" class="form-control" wire:model="company_registration_no" placeholder="Enter registration number">
                                        @error('company_registration_no')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-receipt me-1 text-muted"></i>
                                            GSTIN
                                        </label>
                                        <input type="text" maxlength="15" class="form-control text-uppercase" wire:model.blur="gstin" placeholder="29ABCDE1234F1Z5">
                                        @error('gstin')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-id-card me-1 text-muted"></i>
                                            PAN
                                        </label>
                                        <input type="text" maxlength="10" class="form-control text-uppercase" wire:model.blur="pan" placeholder="ABCDE1234F">
                                        @error('pan')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-globe me-1 text-muted"></i>
                                            Website
                                        </label>
                                        <input type="url" class="form-control" wire:model="company_website" placeholder="https://example.com">
                                        @error('company_website')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-envelope me-1 text-muted"></i>
                                            Company Email
                                        </label>
                                        <input type="email" class="form-control" wire:model="company_email" placeholder="info@company.com">
                                        @error('company_email')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-phone me-1 text-muted"></i>
                                            Phone Number
                                        </label>
                                        <input type="tel" class="form-control" wire:model="company_phone" placeholder="+1 234 567 8900">
                                        @error('company_phone')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-industry me-1 text-muted"></i>
                                            Industry
                                        </label>
                                        <select class="form-select" wire:model="company_industry">
                                            <option value="">Select Industry</option>
                                            <option>Technology</option>
                                            <option>Healthcare</option>
                                            <option>Finance</option>
                                            <option>Education</option>
                                            <option>Retail</option>
                                            <option>Manufacturing</option>
                                            <option>Real Estate</option>
                                            <option>Transportation</option>
                                            <option>Other</option>
                                        </select>
                                        @error('company_industry')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-tag me-1 text-muted"></i>
                                            Company Type
                                        </label>
                                        <select class="form-select" wire:model="company_type">
                                            <option value="">Select Type</option>
                                            <option>Public Limited</option>
                                            <option>Private Limited</option>
                                            <option>Partnership</option>
                                            <option>Sole Proprietorship</option>
                                            <option>Non-Profit</option>
                                            <option>Government</option>
                                            <option>Other</option>
                                        </select>
                                        @error('company_type')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-users me-1 text-muted"></i>
                                            Employee Count
                                        </label>
                                        <select class="form-select" wire:model="company_employee_count">
                                            <option value="">Select Range</option>
                                            <option>1-10</option>
                                            <option>11-50</option>
                                            <option>51-200</option>
                                            <option>201-500</option>
                                            <option>501-1000</option>
                                            <option>1000+</option>
                                        </select>
                                        @error('company_employee_count')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-align-left me-1 text-muted"></i>
                                            Description
                                        </label>
                                        <textarea class="form-control" wire:model="company_description" rows="3" placeholder="Enter company description..."></textarea>
                                        @error('company_description')
                                            <span class="text-danger">{{ $message }}</span>
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
                                        <input type="text" class="form-control" wire:model="company_address" placeholder="Enter street address">
                                        @error('company_address')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-city me-1 text-muted"></i>
                                            City
                                        </label>
                                        <input type="text" class="form-control" wire:model="company_city" placeholder="Enter city">
                                        @error('company_city')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-map-pin me-1 text-muted"></i>
                                            State/Province
                                        </label>
                                        <input type="text" class="form-control" wire:model="company_state" placeholder="Enter state">
                                        @error('company_state')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-mailbox me-1 text-muted"></i>
                                            Postal Code
                                        </label>
                                        <input type="text" class="form-control" wire:model="company_postal_code" placeholder="Enter postal code">
                                        @error('company_postal_code')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-flag me-1 text-muted"></i>
                                            Country
                                        </label>
                                        <select class="form-select" wire:model="company_country">
                                            <option value="">Select Country</option>
                                            <option>United States</option>
                                            <option>United Kingdom</option>
                                            <option>Canada</option>
                                            <option>Australia</option>
                                            <option>Germany</option>
                                            <option>France</option>
                                            <option>India</option>
                                            <option>Pakistan</option>
                                            <option>Other</option>
                                        </select>
                                        @error('company_country')
                                            <span class="text-danger">{{ $message }}</span>
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
                                            <input type="text" class="form-control" wire:model="company_facebook" placeholder="Facebook URL">
                                        </div>
                                        @error('company_facebook')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium">
                                            <i class="fab fa-twitter text-info me-1"></i>
                                            Twitter/X
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fab fa-twitter"></i></span>
                                            <input type="text" class="form-control" wire:model="company_twitter" placeholder="Twitter URL">
                                        </div>
                                        @error('company_twitter')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium">
                                            <i class="fab fa-linkedin text-primary me-1"></i>
                                            LinkedIn
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fab fa-linkedin"></i></span>
                                            <input type="text" class="form-control" wire:model="company_linkedin" placeholder="LinkedIn URL">
                                        </div>
                                        @error('company_linkedin')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium">
                                            <i class="fab fa-instagram text-danger me-1"></i>
                                            Instagram
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fab fa-instagram"></i></span>
                                            <input type="text" class="form-control" wire:model="company_instagram" placeholder="Instagram URL">
                                        </div>
                                        @error('company_instagram')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium">
                                            <i class="fab fa-youtube text-danger me-1"></i>
                                            YouTube
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fab fa-youtube"></i></span>
                                            <input type="text" class="form-control" wire:model="company_youtube" placeholder="YouTube URL">
                                        </div>
                                        @error('company_youtube')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium">
                                            <i class="fab fa-github text-dark me-1"></i>
                                            GitHub
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fab fa-github"></i></span>
                                            <input type="text" class="form-control" wire:model="company_github" placeholder="GitHub URL">
                                        </div>
                                        @error('company_github')
                                            <span class="text-danger">{{ $message }}</span>
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
                                            <option value="active" selected>🟢 Active</option>
                                            <option value="inactive">🔴 Inactive</option>
                                            <option value="pending">🟡 Pending</option>
                                            <option value="suspended">⚫ Suspended</option>
                                        </select>
                                        @error('status')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-star me-1 text-warning"></i>
                                            Rating
                                        </label>
                                        <select class="form-select" wire:model="company_rating">
                                            <option value="">Select Rating</option>
                                            <option>⭐ 1 Star</option>
                                            <option>⭐⭐ 2 Stars</option>
                                            <option selected>⭐⭐⭐ 3 Stars</option>
                                            <option>⭐⭐⭐⭐ 4 Stars</option>
                                            <option>⭐⭐⭐⭐⭐ 5 Stars</option>
                                        </select>
                                        @error('company_rating')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-calendar-alt me-1 text-muted"></i>
                                            Founded Date
                                        </label>
                                        <input type="date" wire:model="company_founded_date" class="form-control">
                                        @error('company_founded_date')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Assigned Information -->
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="fw-semibold mb-0">
                                        <i class="fas fa-user-tie me-2"></i>
                                        Assignment
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-user me-1 text-muted"></i>
                                            Owner
                                        </label>
                                        <select class="form-select" wire:model="company_owner">
                                            <option value="">Select Owner</option>
                                            @foreach ($staffMembers as $member)
                                                <option value="{{ $member->name }}">{{ $member->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('company_owner')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-tags me-1 text-muted"></i>
                                            Tags
                                        </label>
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach ($tagOptions as $tag)
                                                <label class="badge p-2 border {{ in_array($tag, $tags) ? 'bg-primary' : 'bg-light text-dark' }}" style="cursor:pointer;">
                                                    <input type="checkbox" class="d-none" wire:model.live="tags" value="{{ $tag }}">
                                                    <i class="fas {{ in_array($tag, $tags) ? 'fa-check' : 'fa-plus' }} me-1"></i>{{ $tag }}
                                                </label>
                                            @endforeach
                                        </div>
                                        @if ($tags)
                                            <div class="text-muted small mt-2">Selected: {{ implode(', ', $tags) }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Client Portal Access -->
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="fw-semibold mb-0">
                                        <i class="fas fa-user-lock me-2"></i>
                                        Client Portal Access
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="ccl" wire:model.live="create_client_login">
                                        <label class="form-check-label fw-medium" for="ccl">
                                            Create a login for this client
                                        </label>
                                    </div>
                                    <p class="text-muted small">
                                        Gives the client a portal account to watch project progress, payments and documents.
                                    </p>

                                    @if ($create_client_login)
                                        <div class="mb-2">
                                            <label class="form-label fw-medium">Contact Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" wire:model="client_name" placeholder="e.g. Grace Kim">
                                            @error('client_name') <span class="text-danger small">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label fw-medium">Login Email <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control" wire:model="client_email" placeholder="client@company.com">
                                            @error('client_email') <span class="text-danger small">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="form-label fw-medium">Link a Project (optional)</label>
                                            <select class="form-select" wire:model="client_project_id">
                                                <option value="">None</option>
                                                @foreach ($projects as $project)
                                                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                                                @endforeach
                                            </select>
                                            <span class="text-muted small">Re-assigns the project to this company so the client can see it.</span>
                                        </div>
                                        <p class="text-muted small mt-2 mb-0">
                                            A temporary password is generated and shown after you save.
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>