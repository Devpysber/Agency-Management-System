<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Contact;
use App\Models\staff;
use App\Support\EditGate;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $lead_status = '';
    public $status = '';
    public $assigned_to = '';
    public $sortBy = 'newest';
    public $selected = [];
    public $selectAll = false;

    public function updatedSearch() { $this->resetPage(); }
    public function updatedLeadStatus() { $this->resetPage(); }
    public function updatedStatus() { $this->resetPage(); }
    public function updatedAssignedTo() { $this->resetPage(); }
    public function updatedSortBy() { $this->resetPage(); }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selected = $this->baseQuery()->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->lead_status = '';
        $this->status = '';
        $this->assigned_to = '';
        $this->sortBy = 'newest';
        $this->selected = [];
        $this->selectAll = false;
        $this->resetPage();
    }

    protected function baseQuery()
    {
        $query = Contact::query();

        // Non-manager staff/interns only see contacts assigned to them.
        $user = auth()->user();
        if ($user && $user->role !== 'admin' && !EditGate::allows()) {
            $myStaffId = staff::where('user_id', $user->id)->value('id');
            $query->where('assigned_to', $myStaffId);
        }

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('first_name', 'like', '%' . $this->search . '%')
                  ->orWhere('last_name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('phone', 'like', '%' . $this->search . '%');
            });
        }

        if (!empty($this->lead_status)) {
            $query->where('lead_status', $this->lead_status);
        }

        if (!empty($this->status)) {
            $query->where('status', $this->status);
        }

        if (!empty($this->assigned_to)) {
            $query->where('assigned_to', $this->assigned_to);
        }

        return $query;
    }

    public function delete($id)
    {
        if (!auth()->user()->hasPermission('Contacts', 'Delete')) {
            session()->flash('error', "You don't have permission to delete contacts.");
            return;
        }

        try {
            Contact::findOrFail($id)->delete();
            $this->selected = array_diff($this->selected, [(string) $id]);
            session()->flash('success', 'Contact deleted successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting contact: ' . $e->getMessage());
        }
    }

    public function deleteSelected()
    {
        if (!auth()->user()->hasPermission('Contacts', 'Delete')) {
            session()->flash('error', "You don't have permission to delete contacts.");
            return;
        }

        if (empty($this->selected)) {
            session()->flash('warning', 'Please select at least one contact to delete.');
            return;
        }

        Contact::whereIn('id', $this->selected)->delete();
        session()->flash('success', count($this->selected) . ' contact(s) deleted successfully!');
        $this->selected = [];
        $this->selectAll = false;
    }

    public function export()
    {
        $contacts = $this->baseQuery()->with(['company', 'assignedTo'])->orderBy('created_at', 'desc')->get();

        $filename = 'contacts-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($contacts) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['First Name', 'Last Name', 'Email', 'Phone', 'Company', 'Job Title', 'Lead Status', 'Status', 'Assigned To', 'Created At'], ',', '"', '\\');
            foreach ($contacts as $c) {
                fputcsv($out, [
                    $c->first_name,
                    $c->last_name,
                    $c->email,
                    $c->phone,
                    $c->company->company_name ?? '',
                    $c->job_title,
                    $c->lead_status,
                    $c->status,
                    $c->assignedTo->name ?? '',
                    optional($c->created_at)->format('Y-m-d H:i'),
                ], ',', '"', '\\');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $query = $this->baseQuery()->with(['company', 'assignedTo']);

        switch ($this->sortBy) {
            case 'name_asc':
                $query->orderBy('first_name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('first_name', 'desc');
                break;
            case 'lead_score':
                $query->orderByDesc('lead_score');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        $contacts = $query->paginate(15);

        return $this->view([
            'contacts' => $contacts,
            'staffMembers' => staff::orderBy('name')->get(),
            'totalCount' => Contact::count(),
            'activeCount' => Contact::where('status', 'active')->count(),
            'newLeadsCount' => Contact::where('lead_status', 'new')->count(),
            'customerCount' => Contact::where('lead_status', 'customer')->count(),
        ])->layout('layouts.app');
    }
};
?>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>All Contacts</h1>
                <p>Manage and view all contacts in your CRM system.</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('contacts.groups') }}" class="btn btn-secondary">
                    <i class="fas fa-layer-group"></i> Groups
                </a>
                <button class="btn btn-secondary" wire:click="export">
                    <i class="fas fa-file-export"></i> Export
                </button>
                @if ((auth()->user()->role === 'admin' || auth()->user()->hasPermission('Contacts', 'Edit')))
                    <a href="{{ route('contacts.import') }}" class="btn btn-secondary">
                        <i class="fas fa-file-import"></i> Import
                    </a>
                    <a href="{{ route('contacts.add') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Contact
                    </a>
                @endif
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
        @if (session()->has('warning'))
            <div class="alert-flash alert-flash-warning">
                <i class="fas fa-exclamation-triangle"></i>
                {{ session('warning') }}
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

        <!-- Filters & Search -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-medium">
                            <i class="fas fa-search me-1 text-muted"></i>
                            Search
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control"
                                   wire:model.live.debounce.400ms="search"
                                   placeholder="Name, email, phone...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-medium">Lead Status</label>
                        <select class="form-select" wire:model.live="lead_status">
                            <option value="">All Status</option>
                            <option value="new">🟢 New</option>
                            <option value="contacted">🟡 Contacted</option>
                            <option value="qualified">🔵 Qualified</option>
                            <option value="lost">🔴 Lost</option>
                            <option value="customer">✅ Customer</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-medium">Status</label>
                        <select class="form-select" wire:model.live="status">
                            <option value="">All Status</option>
                            <option value="active">🟢 Active</option>
                            <option value="inactive">🔴 Inactive</option>
                            <option value="blocked">⚫ Blocked</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-medium">Assigned To</label>
                        <select class="form-select" wire:model.live="assigned_to">
                            <option value="">Everyone</option>
                            @foreach ($staffMembers as $member)
                                <option value="{{ $member->id }}">{{ $member->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-medium">Sort By</label>
                        <select class="form-select" wire:model.live="sortBy">
                            <option value="newest">Newest First</option>
                            <option value="oldest">Oldest First</option>
                            <option value="name_asc">Name A-Z</option>
                            <option value="name_desc">Name Z-A</option>
                            <option value="lead_score">Lead Score</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button class="btn btn-secondary w-100" wire:click="resetFilters" title="Reset Filters">
                            <i class="fas fa-undo"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Summary -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Contacts</h3>
                        <p class="stat-number">{{ $totalCount }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Active</h3>
                        <p class="stat-number">{{ $activeCount }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3>New Leads</h3>
                        <p class="stat-number">{{ $newLeadsCount }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Customers</h3>
                        <p class="stat-number">{{ $customerCount }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Actions -->
        @if(count($selected) > 0)
            <div class="alert alert-info d-flex justify-content-between align-items-center">
                <span>
                    <i class="fas fa-check-circle me-2"></i>
                    {{ count($selected) }} contact(s) selected
                </span>
                <div>
                    <button class="btn btn-danger btn-sm" wire:click="deleteSelected" wire:confirm="Are you sure you want to delete the selected contacts?">
                        <i class="fas fa-trash"></i> Delete Selected
                    </button>
                    <button class="btn btn-secondary btn-sm" wire:click="$set('selected', [])">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>
        @endif

        <!-- Contacts Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-address-book me-2"></i>
                    Contacts List
                </h3>
                <span class="badge bg-primary">{{ $contacts->total() }} Contacts</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" class="form-check-input" wire:model.live="selectAll">
                                </th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Company</th>
                                <th>Assigned To</th>
                                <th>Lead Status</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th style="width: 130px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($contacts as $contact)
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input"
                                           value="{{ $contact->id }}"
                                           wire:model.live="selected">
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="https://ui-avatars.com/api/?name={{ urlencode($contact->first_name . ' ' . $contact->last_name) }}&background=4F46E5&color=fff"
                                             class="rounded-circle me-2" width="32" height="32">
                                        <a href="{{ route('contacts.show', $contact->id) }}" class="fw-semibold text-decoration-none">
                                            {{ $contact->first_name }} {{ $contact->last_name }}
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    @if($contact->email)
                                        <a href="mailto:{{ $contact->email }}" class="text-primary">{{ $contact->email }}</a>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>{{ $contact->phone ?? 'N/A' }}</td>
                                <td>
                                    @if($contact->company)
                                        <a href="{{ route('companies.show', $contact->company->id) }}" class="badge bg-secondary text-decoration-none">
                                            {{ $contact->company->company_name }}
                                        </a>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($contact->assignedTo)
                                        {{ $contact->assignedTo->name }}
                                    @else
                                        <span class="text-muted">Unassigned</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'new' => 'bg-primary',
                                            'contacted' => 'bg-warning text-dark',
                                            'qualified' => 'bg-info',
                                            'lost' => 'bg-danger',
                                            'customer' => 'bg-success'
                                        ];
                                        $statusIcons = [
                                            'new' => '🟢',
                                            'contacted' => '🟡',
                                            'qualified' => '🔵',
                                            'lost' => '🔴',
                                            'customer' => '✅'
                                        ];
                                    @endphp
                                    <span class="badge {{ $statusColors[$contact->lead_status] ?? 'bg-secondary' }}">
                                        {{ $statusIcons[$contact->lead_status] ?? '' }}
                                        {{ $contact->lead_status ? ucfirst($contact->lead_status) : 'N/A' }}
                                    </span>
                                </td>
                                <td>
                                    @if($contact->status == 'active')
                                        <span class="badge bg-success">
                                            <i class="fas fa-circle me-1" style="font-size: 8px;"></i>
                                            Active
                                        </span>
                                    @elseif($contact->status == 'inactive')
                                        <span class="badge bg-danger">
                                            <i class="fas fa-circle me-1" style="font-size: 8px;"></i>
                                            Inactive
                                        </span>
                                    @else
                                        <span class="badge bg-dark">
                                            <i class="fas fa-circle me-1" style="font-size: 8px;"></i>
                                            Blocked
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted" title="{{ $contact->created_at->format('M d, Y h:i A') }}">
                                        {{ $contact->created_at->diffForHumans() }}
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('contacts.show',$contact->id) }}" class="btn btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if ((auth()->user()->role === 'admin' || auth()->user()->hasPermission('Contacts', 'Edit')))
                                            <a href="{{ route('contacts.edit',$contact->id) }}" class="btn btn-outline-secondary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-outline-danger"
                                                    wire:click="delete({{ $contact->id }})"
                                                    wire:confirm="Are you sure you want to delete this contact?">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <i class="fas fa-address-book fa-3x text-muted mb-3 d-block"></i>
                                    <h5 class="text-muted">No contacts found</h5>
                                    <p class="text-muted">Try adjusting your search or filter criteria.</p>
                                    <a href="{{ route('contacts.add') }}" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Add New Contact
                                    </a>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted">
                            Showing {{ $contacts->firstItem() ?? 0 }}-{{ $contacts->lastItem() ?? 0 }} of {{ $contacts->total() }}
                            @if($search || $lead_status || $status || $assigned_to)
                                <span class="text-muted">(filtered)</span>
                            @endif
                        </span>
                    </div>
                    @if($contacts->hasPages())
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item {{ $contacts->onFirstPage() ? 'disabled' : '' }}">
                                <button class="page-link" wire:click="previousPage" @if($contacts->onFirstPage()) disabled @endif>
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                            </li>
                            @for ($page = max(1, $contacts->currentPage() - 2); $page <= min($contacts->lastPage(), $contacts->currentPage() + 2); $page++)
                                <li class="page-item {{ $page == $contacts->currentPage() ? 'active' : '' }}">
                                    <button class="page-link" wire:click="gotoPage({{ $page }})">{{ $page }}</button>
                                </li>
                            @endfor
                            <li class="page-item {{ !$contacts->hasMorePages() ? 'disabled' : '' }}">
                                <button class="page-link" wire:click="nextPage" @if(!$contacts->hasMorePages()) disabled @endif>
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </li>
                        </ul>
                    </nav>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
