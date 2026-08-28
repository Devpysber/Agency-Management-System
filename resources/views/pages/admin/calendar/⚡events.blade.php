<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\CalendarEvent;
use App\Models\Contact;
use App\Models\Project;
use App\Models\staff;

new class extends Component
{
    use WithPagination;

    public $eventId;
    public $title;
    public $description;
    public $event_type = 'meeting';
    public $start_at;
    public $end_at;
    public $all_day = false;
    public $location;
    public $meeting_url;
    public $status = 'scheduled';
    public $assigned_to;
    public $project_id;
    public $contact_id;

    public $search = '';
    public $filterType = '';
    public $filterStatus = '';
    public $showModal = false;

    protected $rules = [
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'event_type' => 'required|in:meeting,call,task,deadline,reminder,other',
        'start_at' => 'required|date',
        'end_at' => 'nullable|date|after_or_equal:start_at',
        'location' => 'nullable|string|max:255',
        'meeting_url' => 'nullable|url|max:500',
        'status' => 'required|in:scheduled,completed,cancelled',
        'assigned_to' => 'nullable|exists:staff,id',
        'project_id' => 'nullable|exists:projects,id',
        'contact_id' => 'nullable|exists:contacts,id',
    ];

    protected function baseQuery()
    {
        $query = CalendarEvent::with('assignedTo');

        // Same visibility rule as calendar.schedule — only show tasks/events
        // if assigned, unless this designation has real company-wide
        // oversight/team authority.
        $user = auth()->user();
        $seesAll = $user->role === 'admin'
            || $user->hasPermission('Projects', 'Assign') || $user->hasPermission('Tasks', 'Assign')
            || $user->hasPermission('Reports', 'View');
        if (! $seesAll) {
            $myStaffId = \App\Models\staff::where('user_id', $user->id)->value('id');
            $query->where(function ($q) use ($myStaffId) {
                $q->where('assigned_to', $myStaffId)->orWhere('notify_all', true);
            });
        }

        if (!empty($this->search)) {
            $query->where('title', 'like', '%' . $this->search . '%');
        }

        if (!empty($this->filterType)) {
            $query->where('event_type', $this->filterType);
        }

        if (!empty($this->filterStatus)) {
            $query->where('status', $this->filterStatus);
        }

        return $query->orderBy('start_at', 'desc');
    }

    /**
     * Only admin/CEO (merged identity) or a Project Manager may touch the
     * calendar at all; a Project Manager may only touch an event THEY
     * created — never one made by someone else. Admin always overrides.
     */
    public function canManage(CalendarEvent $event): bool
    {
        $user = auth()->user();
        if ($user->role === 'admin') {
            return true;
        }
        return \App\Support\EditGate::allows() && $event->created_by === $user->id;
    }

    public function updatedSearch() { $this->resetPage(); }
    public function updatedFilterType() { $this->resetPage(); }
    public function updatedFilterStatus() { $this->resetPage(); }

    public function resetFilters()
    {
        $this->search = '';
        $this->filterType = '';
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
        $this->eventId = null;
        $this->title = null;
        $this->description = null;
        $this->event_type = 'meeting';
        $this->start_at = null;
        $this->end_at = null;
        $this->all_day = false;
        $this->location = null;
        $this->meeting_url = null;
        $this->status = 'scheduled';
        $this->assigned_to = null;
        $this->project_id = null;
        $this->contact_id = null;
        $this->resetErrorBag();
    }

    public function save()
    {
        $notifyAll = $this->assigned_to === 'all';
        if ($notifyAll) {
            $this->assigned_to = null;
        }

        $this->validate();

        $event = $this->eventId ? CalendarEvent::find($this->eventId) : new CalendarEvent;
        if ($this->eventId) {
            abort_unless($this->canManage($event), 403);
        } else {
            abort_unless(\App\Support\EditGate::allows(), 403);
        }
        $event->title = $this->title;
        $event->description = $this->description;
        $event->event_type = $this->event_type;
        $event->start_at = $this->start_at;
        $event->end_at = $this->end_at ?: null;
        $event->all_day = $this->all_day;
        $event->location = $this->location;
        $event->meeting_url = $this->meeting_url ?: null;
        $event->status = $this->status;
        $event->assigned_to = $this->assigned_to ?: null;
        $event->notify_all = $notifyAll;
        $event->project_id = $this->project_id ?: null;
        $event->contact_id = $this->contact_id ?: null;
        if (!$this->eventId) {
            $event->created_by = auth()->id();
        }
        $event->save();

        session()->flash('success', $event->fresh()->communication_id
            ? 'Event saved. Participants have been notified with the details.'
            : 'Event saved successfully!');
        $this->showModal = false;
        $this->resetForm();
    }

    public function edit($id)
    {
        $event = CalendarEvent::findOrFail($id);
        abort_unless($this->canManage($event), 403);
        $this->eventId = $event->id;
        $this->title = $event->title;
        $this->description = $event->description;
        $this->event_type = $event->event_type;
        $this->start_at = optional($event->start_at)->format('Y-m-d\TH:i');
        $this->end_at = optional($event->end_at)->format('Y-m-d\TH:i');
        $this->all_day = $event->all_day;
        $this->location = $event->location;
        $this->meeting_url = $event->meeting_url;
        $this->status = $event->status;
        $this->assigned_to = $event->notify_all ? 'all' : $event->assigned_to;
        $this->project_id = $event->project_id;
        $this->contact_id = $event->contact_id;
        $this->showModal = true;
    }

    public function delete($id)
    {
        $event = CalendarEvent::findOrFail($id);
        abort_unless($this->canManage($event), 403);
        try {
            $event->delete();
            session()->flash('success', 'Event deleted successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting event: ' . $e->getMessage());
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function render()
    {
        return $this->view([
            'events' => $this->baseQuery()->paginate(15),
            'staffMembers' => staff::orderBy('name')->get(),
            'projects' => Project::orderBy('name')->get(['id', 'name']),
            'contacts' => Contact::with('company:id,company_name')->orderBy('first_name')->get(),
        ])->layout('layouts.app');
    }
};
?>
<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>Calendar Events</h1>
                <p>Manage every meeting, call, deadline, and reminder in one place.</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('calendar.schedule') }}" class="btn btn-secondary">
                    <i class="fas fa-calendar-check"></i> Schedule View
                </a>
                @if (\App\Support\EditGate::allows())
                    <button class="btn btn-primary" wire:click="openAddModal">
                        <i class="fas fa-plus"></i> Add Event
                    </button>
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

        @if (session()->has('error'))
            <div class="alert-flash alert-flash-error">
                <i class="fas fa-exclamation-circle"></i>
                {{ session('error') }}
                <button class="alert-flash-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        @endif

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-medium">
                            <i class="fas fa-search me-1 text-muted"></i>
                            Search
                        </label>
                        <input type="text" class="form-control" wire:model.live="search" placeholder="Search events...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium">Type</label>
                        <select class="form-select" wire:model.live="filterType">
                            <option value="">All Types</option>
                            <option value="meeting">Meeting</option>
                            <option value="call">Call</option>
                            <option value="task">Task</option>
                            <option value="deadline">Deadline</option>
                            <option value="reminder">Reminder</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium">Status</label>
                        <select class="form-select" wire:model.live="filterStatus">
                            <option value="">All Status</option>
                            <option value="scheduled">Scheduled</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-secondary w-100" wire:click="resetFilters">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Events Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-calendar-alt me-2"></i>
                    Events List
                </h3>
                <span class="badge bg-primary">{{ $events->total() }} Events</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Start</th>
                                <th>Assigned To</th>
                                <th>Status</th>
                                <th style="width: 130px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($events as $event)
                            <tr>
                                <td>
                                    <h6 class="mb-0 fw-semibold">{{ $event->title }}</h6>
                                    @if($event->location)
                                        <small class="text-muted"><i class="fas fa-location-dot"></i> {{ $event->location }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $event->type_badge['class'] }}">
                                        <i class="fas {{ $event->type_badge['icon'] }} me-1"></i>
                                        {{ ucfirst($event->event_type) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="{{ $event->isOverdue() ? 'text-danger fw-semibold' : '' }}">
                                        {{ $event->start_at->format('M d, Y H:i') }}
                                    </span>
                                </td>
                                <td>
                                    @if($event->assignedTo)
                                        <span class="badge bg-secondary">{{ $event->assignedTo->name }}</span>
                                    @else
                                        <span class="text-muted">Unassigned</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $event->status_badge['class'] }}">
                                        {{ ucfirst($event->status) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        @if ($this->canManage($event))
                                            <button class="btn btn-outline-secondary" wire:click="edit({{ $event->id }})">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-danger"
                                                    wire:click="delete({{ $event->id }})"
                                                    wire:confirm="Are you sure you want to delete this event?">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @elseif (\App\Support\EditGate::allows())
                                            <span class="text-muted small" title="Only the creator (or CEO) can update this event."><i class="fas fa-lock"></i></span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-calendar-alt fa-3x text-muted mb-3 d-block"></i>
                                    <h5 class="text-muted">No events found</h5>
                                    <p class="text-muted">Add a new event to get started.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($events->hasPages())
            <div class="card-footer">
                <div class="d-flex justify-content-end">
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item {{ $events->onFirstPage() ? 'disabled' : '' }}">
                                <button class="page-link" wire:click="previousPage" @if($events->onFirstPage()) disabled @endif>
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                            </li>
                            @for ($page = max(1, $events->currentPage() - 2); $page <= min($events->lastPage(), $events->currentPage() + 2); $page++)
                                <li class="page-item {{ $page == $events->currentPage() ? 'active' : '' }}">
                                    <button class="page-link" wire:click="gotoPage({{ $page }})">{{ $page }}</button>
                                </li>
                            @endfor
                            <li class="page-item {{ !$events->hasMorePages() ? 'disabled' : '' }}">
                                <button class="page-link" wire:click="nextPage" @if(!$events->hasMorePages()) disabled @endif>
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

    <!-- Add / Edit Event Modal -->
    @if($showModal)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5);" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-calendar-alt text-primary me-2"></i>
                        {{ $eventId ? 'Edit Event' : 'Add Event' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="save">
                        <div class="mb-3">
                            <label class="form-label fw-medium">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" wire:model="title" placeholder="Event title">
                            @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">Description</label>
                            <textarea class="form-control" wire:model="description" rows="2" placeholder="Optional details"></textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Type</label>
                                <select class="form-select" wire:model="event_type">
                                    <option value="meeting">Meeting</option>
                                    <option value="call">Call</option>
                                    <option value="task">Task</option>
                                    <option value="deadline">Deadline</option>
                                    <option value="reminder">Reminder</option>
                                    <option value="other">Other</option>
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
                                <label class="form-label fw-medium">Start <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control @error('start_at') is-invalid @enderror" wire:model="start_at">
                                @error('start_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">End</label>
                                <input type="datetime-local" class="form-control @error('end_at') is-invalid @enderror" wire:model="end_at">
                                @error('end_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Location</label>
                                <input type="text" class="form-control" wire:model="location" placeholder="e.g. Zoom, Office 2F">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Meeting link</label>
                                <input type="url" class="form-control @error('meeting_url') is-invalid @enderror" wire:model="meeting_url" placeholder="https://…">
                                @error('meeting_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Assigned To (staff)</label>
                                <select class="form-select" wire:model="assigned_to">
                                    <option value="">Unassigned</option>
                                    <option value="all">👥 All staff (notify everyone)</option>
                                    @foreach ($staffMembers as $member)
                                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Project</label>
                                <select class="form-select" wire:model="project_id">
                                    <option value="">None</option>
                                    @foreach ($projects as $project)
                                        <option value="{{ $project->id }}">{{ $project->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Client contact</label>
                                <select class="form-select" wire:model="contact_id">
                                    <option value="">None</option>
                                    @foreach ($contacts as $contact)
                                        <option value="{{ $contact->id }}">
                                            {{ $contact->full_name }}{{ $contact->company ? ' — ' . $contact->company->company_name : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="all_day" wire:model="all_day">
                                    <label class="form-check-label" for="all_day">All day event</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <p class="text-muted small mb-0">
                                    <i class="fas fa-circle-info me-1"></i>
                                    Assigned staff, the project team and the linked client are notified automatically with the
                                    date, location, meeting link and agenda — and again if you reschedule or cancel.
                                </p>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" wire:click="closeModal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button class="btn btn-primary" wire:click="save">
                        <i class="fas {{ $eventId ? 'fa-save' : 'fa-plus' }}"></i>
                        {{ $eventId ? 'Update' : 'Add' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
