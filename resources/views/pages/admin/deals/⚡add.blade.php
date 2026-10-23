<?php

use Livewire\Component;
use App\Models\Company;
use App\Models\Contact;
use App\Models\deal;

new class extends Component
{
    // Basic Information
    public $deal_name;
    public $deal_notes;
    
    // Financial
    public $deal_value;
    public $currency = 'USD';
    
    // Timeline
    public $expected_close_date;
    public $actual_close_date;
    public $deal_stage = 'lead';
    public $probability = 0;
    
    // Status
    public $deal_status = 'active';
    
    // Relationships
    public $contact_id;
    public $company_id;
    
    public $companies;
    public $contacts;

    protected $rules = [
        // Basic Information
        'deal_name' => 'required|string|max:255',
        'deal_notes' => 'nullable|string',
        
        // Financial
        'deal_value' => 'required|numeric|min:0|max:999999999.99',
        'currency' => 'required|string|in:USD,EUR,GBP,PKR',
        'probability' => 'nullable|integer|min:0|max:100',
        
        // Timeline
        'expected_close_date' => 'nullable|date|after_or_equal:today',
        'actual_close_date' => 'nullable|date|after_or_equal:expected_close_date',
        
        // Stage & Status
        'deal_stage' => 'required|string|in:lead,qualified,proposal,negotiation,closed_won,closed_lost',
        'deal_status' => 'required|string|in:active,won,lost,on_hold,cancelled',
        
        // Relationships
        'company_id' => 'nullable|exists:companies,id',
        'contact_id' => 'nullable|exists:contacts,id',
    ];

    protected $messages = [
        'deal_name.required' => 'Please enter a deal name.',
        'deal_value.required' => 'Please enter the deal value.',
        'deal_value.numeric' => 'Deal value must be a number.',
        'currency.in' => 'Please select a valid currency.',
        'expected_close_date.after_or_equal' => 'Expected close date cannot be in the past.',
        'actual_close_date.after_or_equal' => 'Actual close date cannot be before the expected close date.',
        'deal_stage.in' => 'Please select a valid deal stage.',
        'deal_status.in' => 'Please select a valid deal status.',
    ];

    public function mount()
    {
        $this->companies = Company::all();
        $this->contacts = Contact::all();
    }

    public function save()
    {
        $this->validate();
        $deal = new Deal();
        $deal->deal_name = $this->deal_name;
        $deal->deal_notes = $this->deal_notes;
        $deal->deal_value = $this->deal_value;
        $deal->currency = $this->currency;
        $deal->expected_close_date = $this->expected_close_date;
        $deal->actual_close_date = $this->actual_close_date;
        $deal->deal_stage = $this->deal_stage;
        $deal->probability = $this->probability;
        $deal->deal_status = $this->deal_status;
        $deal->contact_id = $this->contact_id;
        $deal->company_id = $this->company_id;
        $deal->created_by = auth()->id();
        $deal->save();
        // Query will go here later
        return redirect()->route('deals.all')->with('success','New Deal Created Succesfully');
    }

    public $showNewCompanyModal = false;
    public $new_company_name;
    public $new_company_email;
    public $new_company_phone;

    public $showNewContactModal = false;
    public $new_contact_first_name;
    public $new_contact_last_name;
    public $new_contact_email;
    public $new_contact_phone;

    public function quickCreateCompany()
    {
        $this->validate([
            'new_company_name' => 'required|string|max:255',
            'new_company_email' => 'nullable|email|max:255',
            'new_company_phone' => 'nullable|string|max:50',
        ]);

        $created = Company::create([
            'company_name' => $this->new_company_name,
            'company_email' => $this->new_company_email ?: null,
            'company_phone' => $this->new_company_phone ?: null,
        ]);

        $this->companies = Company::orderBy('company_name')->get();
        $this->company_id = $created->id;
        $this->reset(['new_company_name', 'new_company_email', 'new_company_phone', 'showNewCompanyModal']);
        session()->flash('company_created', "Company '{$created->company_name}' created and selected!");
    }

    public function quickCreateContact()
    {
        $this->validate([
            'new_contact_first_name' => 'required|string|max:255',
            'new_contact_last_name' => 'nullable|string|max:255',
            'new_contact_email' => 'nullable|email|max:255',
            'new_contact_phone' => 'nullable|string|max:50',
        ]);

        $created = Contact::create([
            'first_name' => $this->new_contact_first_name,
            'last_name' => $this->new_contact_last_name ?: null,
            'email' => $this->new_contact_email ?: null,
            'phone' => $this->new_contact_phone ?: null,
            'company_id' => $this->company_id ?: null,
            'created_by' => auth()->id(),
        ]);

        $this->contacts = Contact::orderBy('first_name')->get();
        $this->contact_id = $created->id;
        $this->reset(['new_contact_first_name', 'new_contact_last_name', 'new_contact_email', 'new_contact_phone', 'showNewContactModal']);
        session()->flash('contact_created', "Contact '{$created->first_name}' created and selected!");
    }

    public function cancel()
    {
        return $this->redirectRoute('deals.all');
    }
};
?>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>Add New Deal</h1>
                <p>Create a new deal in your CRM system.</p>
            </div>
            <div class="header-actions">
                <button type="button" class="btn btn-secondary" wire:click="cancel">
                    <i class="fas fa-arrow-left"></i> Cancel
                </button>
                <button type="submit" form="addDealForm" class="btn btn-primary">
                    <i class="fas fa-save" wire:loading.remove></i> <i class="fas fa-spinner fa-spin" wire:loading></i> Save Deal
                </button>
            </div>
        </div>

        <!-- Deal Form -->
        <div class="card">
            <div class="card-body">
                <form id="addDealForm" wire:submit="save">
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
                                    <div class="col-md-12">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-tag me-1 text-muted"></i>
                                            Deal Name <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control @error('deal_name') is-invalid @enderror" 
                                               wire:model="deal_name" 
                                               placeholder="Enter deal name">
                                        @error('deal_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-align-left me-1 text-muted"></i>
                                            Notes / Description
                                        </label>
                                        <textarea class="form-control @error('deal_notes') is-invalid @enderror" 
                                                  wire:model="deal_notes" 
                                                  rows="3" 
                                                  placeholder="Enter deal notes..."></textarea>
                                        @error('deal_notes')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Financial Information -->
                            <div class="mb-4">
                                <h5 class="fw-semibold mb-3">
                                    <i class="fas fa-dollar-sign text-primary me-2"></i>
                                    Financial Information
                                </h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-money-bill me-1 text-muted"></i>
                                            Deal Value <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control @error('deal_value') is-invalid @enderror" 
                                                   wire:model="deal_value" 
                                                   placeholder="0.00" step="0.01">
                                        </div>
                                        @error('deal_value')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-globe me-1 text-muted"></i>
                                            Currency
                                        </label>
                                        <select class="form-select @error('currency') is-invalid @enderror" wire:model="currency">
                                            <option value="USD">USD - US Dollar</option>
                                            <option value="EUR">EUR - Euro</option>
                                            <option value="GBP">GBP - British Pound</option>
                                            <option value="PKR">PKR - Pakistani Rupee</option>
                                        </select>
                                        @error('currency')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-percentage me-1 text-muted"></i>
                                            Win Probability
                                        </label>
                                        <div class="input-group">
                                            <input type="number" class="form-control @error('probability') is-invalid @enderror" 
                                                   wire:model="probability" 
                                                   placeholder="0" min="0" max="100">
                                            <span class="input-group-text">%</span>
                                        </div>
                                        @error('probability')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Timeline -->
                            <div class="mb-4">
                                <h5 class="fw-semibold mb-3">
                                    <i class="fas fa-calendar-alt text-primary me-2"></i>
                                    Timeline
                                </h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-calendar-check me-1 text-muted"></i>
                                            Expected Close Date
                                        </label>
                                        <input type="date" class="form-control @error('expected_close_date') is-invalid @enderror" 
                                               wire:model="expected_close_date">
                                        @error('expected_close_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-calendar-times me-1 text-muted"></i>
                                            Actual Close Date
                                        </label>
                                        <input type="date" class="form-control @error('actual_close_date') is-invalid @enderror" 
                                               wire:model="actual_close_date">
                                        @error('actual_close_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Related Entities -->
                            <div class="mb-4">
                                <h5 class="fw-semibold mb-3">
                                    <i class="fas fa-link text-primary me-2"></i>
                                    Related Entities
                                </h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="form-label fw-medium mb-0">
                                                <i class="fas fa-building me-1 text-muted"></i>
                                                Company
                                            </label>
                                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" wire:click="$set('showNewCompanyModal', true)">
                                                <i class="fas fa-plus-circle me-1"></i> New Company
                                            </button>
                                        </div>
                                        <select class="form-select @error('company_id') is-invalid @enderror" wire:model="company_id">
                                            <option value="">Select Company</option>
                                            @foreach($companies as $company)
                                                <option value="{{ $company->id }}">{{ $company->company_name }}</option>
                                            @endforeach
                                        </select>
                                        @if(session()->has('company_created'))
                                            <span class="text-success small d-block mt-1"><i class="fas fa-check-circle me-1"></i> {{ session('company_created') }}</span>
                                        @endif
                                        @error('company_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="form-label fw-medium mb-0">
                                                <i class="fas fa-user me-1 text-muted"></i>
                                                Primary Contact
                                            </label>
                                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" wire:click="$set('showNewContactModal', true)">
                                                <i class="fas fa-plus-circle me-1"></i> New Contact
                                            </button>
                                        </div>
                                        <select class="form-select @error('contact_id') is-invalid @enderror" wire:model="contact_id">
                                            <option value="">Select Contact</option>
                                            @foreach($contacts as $contact)
                                                <option value="{{ $contact->id }}">{{ $contact->first_name }} {{ $contact->last_name }}</option>
                                            @endforeach
                                        </select>
                                        @if(session()->has('contact_created'))
                                            <span class="text-success small d-block mt-1"><i class="fas fa-check-circle me-1"></i> {{ session('contact_created') }}</span>
                                        @endif
                                        @error('contact_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-lg-4">
                            <!-- Deal Stage & Status -->
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="fw-semibold mb-0">
                                        <i class="fas fa-chart-line text-primary me-2"></i>
                                        Deal Stage & Status
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-layer-group me-1 text-muted"></i>
                                            Deal Stage
                                        </label>
                                        <select class="form-select @error('deal_stage') is-invalid @enderror" wire:model="deal_stage">
                                            <option value="lead">Lead</option>
                                            <option value="qualified">Qualified</option>
                                            <option value="proposal">Proposal Sent</option>
                                            <option value="negotiation">Negotiation</option>
                                            <option value="closed_won">Closed Won</option>
                                            <option value="closed_lost">Closed Lost</option>
                                        </select>
                                        @error('deal_stage')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="form-label fw-medium">
                                            <i class="fas fa-circle me-1 text-muted"></i>
                                            Deal Status
                                        </label>
                                        <select class="form-select @error('deal_status') is-invalid @enderror" wire:model="deal_status">
                                            <option value="active">🟢 Active</option>
                                            <option value="won">✅ Won</option>
                                            <option value="lost">🔴 Lost</option>
                                            <option value="on_hold">🟡 On Hold</option>
                                            <option value="cancelled">⛔ Cancelled</option>
                                        </select>
                                        @error('deal_status')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
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
                                        <button type="button" class="btn btn-outline-success" onclick="window.location.href='{{ route('companies.add') }}'">
                                            <i class="fas fa-building me-2"></i>
                                            Add Company
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

    <!-- Quick Company Modal -->
    @if($showNewCompanyModal)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5);" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-building text-primary me-2"></i>Quick Add Company</h5>
                    <button type="button" class="btn-close" wire:click="$set('showNewCompanyModal', false)"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Company Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('new_company_name') is-invalid @enderror" wire:model="new_company_name" placeholder="Acme Corp">
                        @error('new_company_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Company Email</label>
                        <input type="email" class="form-control @error('new_company_email') is-invalid @enderror" wire:model="new_company_email" placeholder="contact@acme.com">
                        @error('new_company_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Phone</label>
                        <input type="text" class="form-control" wire:model="new_company_phone" placeholder="+1 (555) 000-0000">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="$set('showNewCompanyModal', false)">Cancel</button>
                    <button type="button" class="btn btn-primary" wire:click="quickCreateCompany">
                        <i class="fas fa-check me-1"></i> Create & Select
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Quick Contact Modal -->
    @if($showNewContactModal)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5);" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user text-primary me-2"></i>Quick Add Contact</h5>
                    <button type="button" class="btn-close" wire:click="$set('showNewContactModal', false)"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('new_contact_first_name') is-invalid @enderror" wire:model="new_contact_first_name" placeholder="Jane">
                            @error('new_contact_first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Last Name</label>
                            <input type="text" class="form-control" wire:model="new_contact_last_name" placeholder="Doe">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Email</label>
                        <input type="email" class="form-control @error('new_contact_email') is-invalid @enderror" wire:model="new_contact_email" placeholder="jane@example.com">
                        @error('new_contact_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Phone</label>
                        <input type="text" class="form-control" wire:model="new_contact_phone" placeholder="+1 (555) 000-0000">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="$set('showNewContactModal', false)">Cancel</button>
                    <button type="button" class="btn btn-primary" wire:click="quickCreateContact">
                        <i class="fas fa-check me-1"></i> Create & Select
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>