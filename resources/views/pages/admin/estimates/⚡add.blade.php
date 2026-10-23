<?php

use Livewire\Component;
use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\Company;
use App\Models\Contact;

new class extends Component
{
    public $company_id = '';
    public $contact_id = '';
    public $client_name = '';
    public $client_email = '';
    public $issue_date;
    public $valid_until;
    public $status = 'draft';
    public $tax = 0;
    public $notes = '';

    public $items = [
        ['description' => '', 'qty' => 1, 'unit_price' => 0],
    ];

    public function mount()
    {
        $this->issue_date = now()->format('Y-m-d');
    }

    protected function rules()
    {
        return [
            'company_id' => 'nullable|exists:companies,id',
            'contact_id' => 'nullable|exists:contacts,id',
            'client_name' => 'nullable|string|max:255',
            'client_email' => 'nullable|email|max:255',
            'issue_date' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:issue_date',
            'status' => 'required|in:draft,sent,approved,rejected',
            'tax' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ];
    }

    public function addItem()
    {
        $this->items[] = ['description' => '', 'qty' => 1, 'unit_price' => 0];
    }

    public function removeItem($index)
    {
        if (count($this->items) <= 1) {
            return;
        }
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function getSubtotalProperty()
    {
        return collect($this->items)->sum(function ($item) {
            return (float) ($item['qty'] ?? 0) * (float) ($item['unit_price'] ?? 0);
        });
    }

    public function getTotalProperty()
    {
        return $this->subtotal + (float) ($this->tax ?: 0);
    }

    public function form_submit()
    {
        $this->validate();

        $estimate = Estimate::create([
            'estimate_number' => Estimate::generateEstimateNumber(),
            'company_id' => $this->company_id ?: null,
            'contact_id' => $this->contact_id ?: null,
            'client_name' => $this->client_name ?: null,
            'client_email' => $this->client_email ?: null,
            'issue_date' => $this->issue_date ?: null,
            'valid_until' => $this->valid_until ?: null,
            'status' => $this->status ?: 'draft',
            'subtotal' => $this->subtotal,
            'tax' => $this->tax ?: 0,
            'total' => $this->total,
            'notes' => $this->notes,
            'created_by' => auth()->id(),
        ]);

        foreach ($this->items as $item) {
            EstimateItem::create([
                'estimate_id' => $estimate->id,
                'description' => $item['description'],
                'qty' => $item['qty'],
                'unit_price' => $item['unit_price'],
            ]);
        }

        session()->flash('success', 'Estimate created successfully');

        return redirect()->route('estimates.show', $estimate->id);
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
            'companies' => Company::orderBy('company_name')->get(),
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
                <h1>New Estimate</h1>
                <p>Create a new estimate for a client or prospect.</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('estimates.all') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Estimates
                </a>
                <button type="button" wire:click="form_submit" class="btn btn-primary">
                    <i class="fas fa-save" wire:loading.remove></i> <i class="fas fa-spinner fa-spin" wire:loading></i> Save Estimate
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

        <!-- Estimate Header Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle me-2"></i>Estimate Details</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-medium mb-0">
                                <i class="fas fa-building me-1 text-muted"></i>
                                Company
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
                    <div class="col-md-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-medium mb-0">
                                <i class="fas fa-user me-1 text-muted"></i>
                                Contact
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
                    <div class="col-md-4">
                        <label class="form-label fw-medium">
                            <i class="fas fa-circle me-1 text-muted"></i>
                            Status
                        </label>
                        <select class="form-select" wire:model="status">
                            <option value="draft">Draft</option>
                            <option value="sent">Sent</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                        @error('status')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-medium">
                            <i class="fas fa-user-tag me-1 text-muted"></i>
                            Client Name <small class="text-muted">(for unlinked prospects)</small>
                        </label>
                        <input type="text" class="form-control" wire:model="client_name" placeholder="Client / prospect name">
                        @error('client_name')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium">
                            <i class="fas fa-envelope me-1 text-muted"></i>
                            Client Email
                        </label>
                        <input type="email" class="form-control" wire:model="client_email" placeholder="client@example.com">
                        @error('client_email')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-medium">
                            <i class="fas fa-calendar-alt me-1 text-muted"></i>
                            Issue Date
                        </label>
                        <input type="date" class="form-control" wire:model="issue_date">
                        @error('issue_date')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium">
                            <i class="fas fa-calendar-check me-1 text-muted"></i>
                            Valid Until
                        </label>
                        <input type="date" class="form-control" wire:model="valid_until">
                        @error('valid_until')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium">
                            <i class="fas fa-align-left me-1 text-muted"></i>
                            Notes
                        </label>
                        <textarea class="form-control" wire:model="notes" rows="1" placeholder="Additional notes..."></textarea>
                        @error('notes')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Line Items -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0"><i class="fas fa-list me-2"></i>Line Items</h3>
                <button type="button" class="btn btn-sm btn-primary" wire:click="addItem">
                    <i class="fas fa-plus"></i> Add Item
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Description</th>
                                <th style="width: 110px;">Qty</th>
                                <th style="width: 160px;">Unit Price</th>
                                <th style="width: 160px;">Line Total</th>
                                <th style="width: 60px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $index => $item)
                                <tr wire:key="item-{{ $index }}">
                                    <td>
                                        <input type="text" class="form-control" wire:model="items.{{ $index }}.description" placeholder="Item description">
                                        @error("items.{$index}.description")
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </td>
                                    <td>
                                        <input type="number" min="1" class="form-control" wire:model="items.{{ $index }}.qty">
                                        @error("items.{$index}.qty")
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" class="form-control" wire:model="items.{{ $index }}.unit_price">
                                        @error("items.{$index}.unit_price")
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </td>
                                    <td>
                                        <span class="fw-semibold">
                                            ${{ number_format((float)($item['qty'] ?? 0) * (float)($item['unit_price'] ?? 0), 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeItem({{ $index }})" @if(count($items) <= 1) disabled @endif>
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <div class="row justify-content-end">
                    <div class="col-md-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal</span>
                            <span class="fw-semibold">${{ number_format($this->subtotal, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Tax</span>
                            <input type="number" step="0.01" min="0" class="form-control form-control-sm w-50" wire:model.live="tax">
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">Total</span>
                            <span class="fw-bold fs-5">${{ number_format($this->total, 2) }}</span>
                        </div>
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
