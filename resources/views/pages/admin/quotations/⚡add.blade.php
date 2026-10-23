<?php

use Livewire\Component;
use App\Models\Quotation;
use App\Models\company;
use App\Models\Contact;

new class extends Component
{
    public $company_id = '';
    public $contact_id = '';
    public $name = '';
    public $email = '';
    public $phone = '';
    public $service_interest = '';
    public $message = '';

    protected function rules()
    {
        return [
            'company_id' => 'nullable|exists:companies,id',
            'contact_id' => 'nullable|exists:contacts,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'service_interest' => 'nullable|string|max:255',
            'message' => 'nullable|string',
        ];
    }

    public function form_submit()
    {
        $this->validate();

        $quotation = Quotation::create([
            'company_id' => $this->company_id ?: null,
            'contact_id' => $this->contact_id ?: null,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
            'service_interest' => $this->service_interest ?: null,
            'message' => $this->message ?: null,
            'status' => 'pending',
            'created_by' => auth()->id(),
        ]);

        session()->flash('success', 'Quotation inquiry created successfully');

        return redirect()->route('quotations.show', $quotation->id);
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

        $created = company::create([
            'company_name' => $this->new_company_name,
            'company_email' => $this->new_company_email ?: null,
            'company_phone' => $this->new_company_phone ?: null,
        ]);

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

        $this->contact_id = $created->id;
        $this->reset(['new_contact_first_name', 'new_contact_last_name', 'new_contact_email', 'new_contact_phone', 'showNewContactModal']);
        session()->flash('contact_created', "Contact '{$created->first_name}' created and selected!");
    }

    public function render()
    {
        return $this->view([
            'companies' => company::orderBy('company_name')->get(),
            'contacts' => Contact::orderBy('first_name')->get(),
        ]);
    }
};
?>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>New Quotation</h1>
                <p>Log a new quotation inquiry from a prospect or client.</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('quotations.all') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Quotations
                </a>
                <button type="button" wire:click="form_submit" class="btn btn-primary">
                    <i class="fas fa-save" wire:loading.remove></i> <i class="fas fa-spinner fa-spin" wire:loading></i> Save Quotation
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

        <!-- Quotation Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle me-2"></i>Inquiry Details</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-medium">
                            <i class="fas fa-user me-1 text-muted"></i>
                            Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" wire:model="name" placeholder="Full name">
                        @error('name')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium">
                            <i class="fas fa-envelope me-1 text-muted"></i>
                            Email <span class="text-danger">*</span>
                        </label>
                        <input type="email" class="form-control" wire:model="email" placeholder="email@example.com">
                        @error('email')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-medium">
                            <i class="fas fa-phone me-1 text-muted"></i>
                            Phone
                        </label>
                        <input type="text" class="form-control" wire:model="phone" placeholder="Phone number">
                        @error('phone')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium">
                            <i class="fas fa-briefcase me-1 text-muted"></i>
                            Service Interest
                        </label>
                        <input type="text" class="form-control" wire:model="service_interest" placeholder="e.g. Web Design, SEO, Branding...">
                        @error('service_interest')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-medium mb-0">
                                <i class="fas fa-building me-1 text-muted"></i>
                                Link to Company <small class="text-muted">(optional)</small>
                            </label>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" wire:click="$set('showNewCompanyModal', true)">
                                <i class="fas fa-plus-circle me-1"></i> New
                            </button>
                        </div>
                        <select class="form-select" wire:model="company_id">
                            <option value="">Select Company (optional)</option>
                            @foreach ($companies as $c)
                                <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                            @endforeach
                        </select>
                        @if(session()->has('company_created'))
                            <span class="text-success small d-block mt-1"><i class="fas fa-check-circle me-1"></i> {{ session('company_created') }}</span>
                        @endif
                        @error('company_id')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-medium mb-0">
                                <i class="fas fa-address-card me-1 text-muted"></i>
                                Link to Contact <small class="text-muted">(optional)</small>
                            </label>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" wire:click="$set('showNewContactModal', true)">
                                <i class="fas fa-plus-circle me-1"></i> New
                            </button>
                        </div>
                        <select class="form-select" wire:model="contact_id">
                            <option value="">Select Contact (optional)</option>
                            @foreach ($contacts as $contact)
                                <option value="{{ $contact->id }}">{{ $contact->first_name }} {{ $contact->last_name }}</option>
                            @endforeach
                        </select>
                        @if(session()->has('contact_created'))
                            <span class="text-success small d-block mt-1"><i class="fas fa-check-circle me-1"></i> {{ session('contact_created') }}</span>
                        @endif
                        @error('contact_id')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-medium">
                            <i class="fas fa-align-left me-1 text-muted"></i>
                            Message
                        </label>
                        <textarea class="form-control" wire:model="message" rows="4" placeholder="Inquiry details / requirements..."></textarea>
                        @error('message')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
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
