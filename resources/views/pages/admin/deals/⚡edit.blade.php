<?php

use Livewire\Component;
use App\Models\Deal;
use App\Models\Company;
use App\Models\Contact;

new class extends Component
{
    public $dealId;
    public $deal;
    
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
        'expected_close_date' => 'nullable|date',
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

    public function mount($id)
    {
        $this->deal = Deal::findOrFail($id);
        $this->dealId = $this->deal->id;
        $this->companies = Company::all();
        $this->contacts = Contact::all();
        
        // Load data into properties
        $this->deal_name = $this->deal->deal_name;
        $this->deal_notes = $this->deal->deal_notes;
        $this->deal_value = $this->deal->deal_value;
        $this->currency = $this->deal->currency;
        $this->expected_close_date = $this->deal->expected_close_date;
        $this->actual_close_date = $this->deal->actual_close_date;
        $this->deal_stage = $this->deal->deal_stage;
        $this->probability = $this->deal->probability;
        $this->deal_status = $this->deal->deal_status;
        $this->contact_id = $this->deal->contact_id;
        $this->company_id = $this->deal->company_id;
    }

    public function update()
    {
        $rules = $this->rules;
        $this->validate($rules);

        $this->deal->update([
            'deal_name' => $this->deal_name,
            'deal_notes' => $this->deal_notes,
            'deal_value' => $this->deal_value,
            'currency' => $this->currency,
            'expected_close_date' => $this->expected_close_date,
            'actual_close_date' => $this->actual_close_date,
            'deal_stage' => $this->deal_stage,
            'probability' => $this->probability,
            'deal_status' => $this->deal_status,
            'contact_id' => $this->contact_id,
            'company_id' => $this->company_id,
        ]);

        session()->flash('success', 'Deal updated successfully!');
        return redirect()->route('deals.all');
    }

    public function cancel()
    {
        return redirect()->route('deals.all');
    }

};
?>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>Edit Deal</h1>
                <p>Update deal information in your CRM system.</p>
            </div>
            <div class="header-actions">
                <button type="button" class="btn btn-secondary" wire:click="cancel">
                    <i class="fas fa-arrow-left"></i> Cancel
                </button>
                <button type="submit" form="editDealForm" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Deal
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

        <!-- Deal Form -->
        <div class="card">
            <div class="card-body">
                <form id="editDealForm" wire:submit="update">
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
                                            <i class="fas fa-user me-1 text-muted"></i>
                                            Primary Contact
                                        </label>
                                        <select class="form-select @error('contact_id') is-invalid @enderror" wire:model="contact_id">
                                            <option value="">Select Contact</option>
                                            @foreach($contacts as $contact)
                                                <option value="{{ $contact->id }}">{{ $contact->first_name }} {{ $contact->last_name }}</option>
                                            @endforeach
                                        </select>
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
</div>