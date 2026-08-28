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
                        <label class="form-label fw-medium">
                            <i class="fas fa-building me-1 text-muted"></i>
                            Link to Company <small class="text-muted">(optional)</small>
                        </label>
                        <select class="form-select" wire:model="company_id">
                            <option value="">Select Company (optional)</option>
                            @foreach ($companies as $c)
                                <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                            @endforeach
                        </select>
                        @error('company_id')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium">
                            <i class="fas fa-address-card me-1 text-muted"></i>
                            Link to Contact <small class="text-muted">(optional)</small>
                        </label>
                        <select class="form-select" wire:model="contact_id">
                            <option value="">Select Contact (optional)</option>
                            @foreach ($contacts as $contact)
                                <option value="{{ $contact->id }}">{{ $contact->first_name }} {{ $contact->last_name }}</option>
                            @endforeach
                        </select>
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
</div>
