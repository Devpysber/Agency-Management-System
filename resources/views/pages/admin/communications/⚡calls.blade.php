<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Communication;
use App\Models\Contact;
use App\Models\staff;

new class extends Component
{
    use WithPagination;

    public $commId;
    public $subject;
    public $direction = 'outbound';
    public $notes;
    public $status = 'completed';
    public $duration_minutes;
    public $occurred_at;
    public $contact_id;
    public $staff_id;

    public $search = '';
    public $filterStatus = '';
    public $showModal = false;

    protected $rules = [
        'subject' => 'required|string|max:255',
        'direction' => 'required|in:inbound,outbound',
        'notes' => 'nullable|string',
        'status' => 'required|in:scheduled,completed,cancelled',
        'duration_minutes' => 'nullable|integer|min:0',
        'occurred_at' => 'required|date',
        'contact_id' => 'nullable|exists:contacts,id',
        'staff_id' => 'nullable|exists:staff,id',
    ];

    /** Company-wide communications visibility: oversight roles (CEO/COO/
     * Finance via Reports.View) and BDM (manages the whole sales team's
     * client interactions). Account Manager/Sales Exec/PM see their own
     * logged comms plus anything tied to a company they're actually
     * connected to (client relationship / assigned project). Everyone
     * else (Designer, Marketing Manager, ...) sees only what they logged
     * themselves — "proper communication ... based on designation". */
    protected function scopeCommunicationsVisibility($query)
    {
        $user = auth()->user();
        if ($user->role === 'admin' || $user->hasPermission('Reports', 'View') || $user->hasPermission('Deals', 'Assign')) {
            return; // company-wide
        }

        $myStaffId = staff::where('user_id', $user->id)->value('id');

        $companyIds = collect();
        if ($myStaffId) {
            // Client-relationship companies (Account Manager / Sales Exec: via their contacts).
            $companyIds = $companyIds->merge(
                Contact::where('assigned_to', $myStaffId)->pluck('company_id')
            );
            // Delivery companies (Project Manager / project team: via assigned projects).
            $companyIds = $companyIds->merge(
                \App\Models\Project::whereHas('staff', fn ($q) => $q->where('staff.id', $myStaffId))->pluck('company_id')
            );
        }
        $companyIds = $companyIds->filter()->unique();

        $query->where(function ($q) use ($myStaffId, $companyIds) {
            $q->where('staff_id', $myStaffId)->orWhere('created_by', auth()->id());
            if ($companyIds->isNotEmpty()) {
                $q->orWhereIn('company_id', $companyIds);
            }
        });
    }

    protected function baseQuery()
    {
        $query = Communication::type('call')->with(['contact', 'staff']);
        $this->scopeCommunicationsVisibility($query);

        if (!empty($this->search)) {
            $query->search($this->search);
        }

        if (!empty($this->filterStatus)) {
            $query->byStatus($this->filterStatus);
        }

        return $query->orderBy('occurred_at', 'desc');
    }

    public function updatedSearch() { $this->resetPage(); }
    public function updatedFilterStatus() { $this->resetPage(); }

    public function resetFilters()
    {
        $this->search = '';
        $this->filterStatus = '';
        $this->resetPage();
    }

    public function openAddModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function resetForm()
    {
        $this->commId = null;
        $this->subject = null;
        $this->direction = 'outbound';
        $this->notes = null;
        $this->status = 'completed';
        $this->duration_minutes = null;
        $this->occurred_at = now()->format('Y-m-d\TH:i');
        $this->contact_id = null;
        $this->staff_id = null;
        $this->resetErrorBag();
    }

    /** Own logged communications only, unless admin/manager — Communications
     * has no "team-wide" permission tier yet, so Edit is scoped to creator. */
    public function canManage(Communication $item): bool
    {
        return $item->created_by === auth()->id()
            || auth()->user()->role === 'admin'
            || (auth()->user()->role === 'admin' || auth()->user()->hasPermission('Communications', 'Edit'));
    }

    public function save()
    {
        $this->validate();

        if ($this->commId) {
            $existing = Communication::findOrFail($this->commId);
            abort_unless($this->canManage($existing), 403);
        }

        $item = $this->commId ? Communication::find($this->commId) : new Communication;
        $item->type = 'call';
        $item->subject = $this->subject;
        $item->direction = $this->direction;
        $item->notes = $this->notes;
        $item->status = $this->status;
        $item->duration_minutes = $this->duration_minutes ?: null;
        $item->occurred_at = $this->occurred_at;
        $item->contact_id = $this->contact_id ?: null;
        $item->staff_id = $this->staff_id ?: null;
        if (!$this->commId) {
            $item->created_by = auth()->id();
        }
        $item->save();

        session()->flash('success', 'Call logged successfully!');
        $this->showModal = false;
        $this->resetForm();
    }

    public function edit($id)
    {
        $item = Communication::findOrFail($id);
        abort_unless($this->canManage($item), 403);
        $this->commId = $item->id;
        $this->subject = $item->subject;
        $this->direction = $item->direction;
        $this->notes = $item->notes;
        $this->status = $item->status;
        $this->duration_minutes = $item->duration_minutes;
        $this->occurred_at = optional($item->occurred_at)->format('Y-m-d\TH:i');
        $this->contact_id = $item->contact_id;
        $this->staff_id = $item->staff_id;
        $this->showModal = true;
    }

    public function delete($id)
    {
        $item = Communication::findOrFail($id);
        abort_unless($this->canManage($item), 403);
        try {
            $item->delete();
            session()->flash('success', 'Call deleted successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting call: ' . $e->getMessage());
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function render()
    {
        // Scoped the same as the list — see emails.blade.php's render() for
        // why (was unscoped, could show stats inconsistent with the list).
        $minutesQuery = Communication::type('call');
        $this->scopeCommunicationsVisibility($minutesQuery);
        $scheduledQuery = Communication::type('call')->byStatus('scheduled');
        $this->scopeCommunicationsVisibility($scheduledQuery);

        return $this->view([
            'items' => $this->baseQuery()->paginate(15),
            'contacts' => Contact::orderBy('first_name')->get(),
            'staffMembers' => staff::orderBy('name')->get(),
            'totalMinutes' => $minutesQuery->sum('duration_minutes'),
            'scheduledCount' => $scheduledQuery->count(),
        ])->layout('layouts.app');
    }
};
?>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>Calls</h1>
                <p>Log inbound and outbound calls with contacts and clients.</p>
            </div>
            <div class="header-actions">
                @if ((auth()->user()->role === 'admin' || auth()->user()->hasPermission('Communications', 'Edit')))
                    <button class="btn btn-primary" wire:click="openAddModal">
                        <i class="fas fa-plus"></i> Log Call
                    </button>
                @endif
            </div>
        </div>

        <!-- Flash Messages -->
        @if (session()->has('success'))
            <div class="alert-flash alert-flash-success">
                <i class="fas fa-check-circle"></i>
                {{ session('success') }}
                <button class="alert-flash-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
            </div>
        @endif
        @if (session()->has('error'))
            <div class="alert-flash alert-flash-error">
                <i class="fas fa-exclamation-circle"></i>
                {{ session('error') }}
                <button class="alert-flash-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
            </div>
        @endif

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-phone"></i></div>
                    <div class="stat-info"><h3>Total Calls</h3><p class="stat-number">{{ $items->total() }}</p></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-clock"></i></div>
                    <div class="stat-info"><h3>Total Minutes</h3><p class="stat-number">{{ $totalMinutes ?? 0 }}</p></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fas fa-calendar-clock"></i></div>
                    <div class="stat-info"><h3>Scheduled</h3><p class="stat-number">{{ $scheduledCount }}</p></div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Search</label>
                        <input type="text" class="form-control" wire:model.live="search" placeholder="Search subject or notes...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-medium">Status</label>
                        <select class="form-select" wire:model.live="filterStatus">
                            <option value="">All Status</option>
                            <option value="scheduled">Scheduled</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-secondary w-100" wire:click="resetFilters"><i class="fas fa-undo"></i> Reset</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-phone me-2"></i> Call Log</h3>
                <span class="badge bg-primary">{{ $items->total() }} Calls</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Subject</th>
                                <th>Direction</th>
                                <th>Contact</th>
                                <th>Duration</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th style="width: 130px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $item)
                            <tr>
                                <td>
                                    <h6 class="mb-0 fw-semibold">{{ $item->subject }}</h6>
                                    @if($item->notes)
                                        <small class="text-muted">{{ \Illuminate\Support\Str::limit($item->notes, 40) }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($item->direction === 'inbound')
                                        <span class="badge bg-info"><i class="fas fa-phone-volume"></i> Inbound</span>
                                    @else
                                        <span class="badge bg-primary"><i class="fas fa-phone"></i> Outbound</span>
                                    @endif
                                </td>
                                <td>
                                    @if($item->contact)
                                        <span class="badge bg-secondary">{{ $item->contact->first_name }} {{ $item->contact->last_name }}</span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>{{ $item->duration_minutes ? $item->duration_minutes . ' min' : '—' }}</td>
                                <td><small class="text-muted">{{ $item->occurred_at->format('M d, Y H:i') }}</small></td>
                                <td><span class="badge {{ $item->status_badge['class'] }}">{{ ucfirst($item->status) }}</span></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        @if ((auth()->user()->role === 'admin' || auth()->user()->hasPermission('Communications', 'Edit')) || $this->canManage($item))
                                            <button class="btn btn-outline-secondary" wire:click="edit({{ $item->id }})"><i class="fas fa-edit"></i></button>
                                            <button class="btn btn-outline-danger" wire:click="delete({{ $item->id }})" wire:confirm="Delete this call log?"><i class="fas fa-trash"></i></button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-phone fa-3x text-muted mb-3 d-block"></i>
                                    <h5 class="text-muted">No calls logged</h5>
                                    <p class="text-muted">Log your first call to start tracking correspondence.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($items->hasPages())
            <div class="card-footer">
                <div class="d-flex justify-content-end">
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item {{ $items->onFirstPage() ? 'disabled' : '' }}">
                                <button class="page-link" wire:click="previousPage" @if($items->onFirstPage()) disabled @endif>
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                            </li>
                            @for ($page = max(1, $items->currentPage() - 2); $page <= min($items->lastPage(), $items->currentPage() + 2); $page++)
                                <li class="page-item {{ $page == $items->currentPage() ? 'active' : '' }}">
                                    <button class="page-link" wire:click="gotoPage({{ $page }})">{{ $page }}</button>
                                </li>
                            @endfor
                            <li class="page-item {{ !$items->hasMorePages() ? 'disabled' : '' }}">
                                <button class="page-link" wire:click="nextPage" @if(!$items->hasMorePages()) disabled @endif>
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Add / Edit Modal -->
    @if($showModal)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5);" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-phone text-primary me-2"></i>{{ $commId ? 'Edit Call' : 'Log Call' }}</h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="save">
                        <div class="mb-3">
                            <label class="form-label fw-medium">Subject <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('subject') is-invalid @enderror" wire:model="subject" placeholder="Call subject">
                            @error('subject') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Direction</label>
                                <select class="form-select" wire:model="direction">
                                    <option value="outbound">Outbound</option>
                                    <option value="inbound">Inbound</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Status</label>
                                <select class="form-select" wire:model="status">
                                    <option value="scheduled">Scheduled</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Date <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control @error('occurred_at') is-invalid @enderror" wire:model="occurred_at">
                                @error('occurred_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Duration (minutes)</label>
                                <input type="number" min="0" class="form-control" wire:model="duration_minutes" placeholder="e.g. 15">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Contact</label>
                                <select class="form-select" wire:model="contact_id">
                                    <option value="">None</option>
                                    @foreach ($contacts as $contact)
                                        <option value="{{ $contact->id }}">{{ $contact->first_name }} {{ $contact->last_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Handled By</label>
                                <select class="form-select" wire:model="staff_id">
                                    <option value="">Unassigned</option>
                                    @foreach ($staffMembers as $member)
                                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-medium">Notes</label>
                                <textarea class="form-control" wire:model="notes" rows="3" placeholder="Optional notes"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" wire:click="closeModal"><i class="fas fa-times"></i> Cancel</button>
                    <button class="btn btn-primary" wire:click="save">
                        <i class="fas {{ $commId ? 'fa-save' : 'fa-plus' }}"></i> {{ $commId ? 'Update' : 'Log Call' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
