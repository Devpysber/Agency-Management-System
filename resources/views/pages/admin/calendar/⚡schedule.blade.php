<?php

use Livewire\Component;
use App\Models\CalendarEvent;
use App\Models\staff;
use App\Models\Project;
use App\Models\Contact;
use Illuminate\Support\Carbon;

new class extends Component
{
    public string $month;              // 'Y-m' — grid month in view
    public ?string $selectedDate = null;

    // ---- event form (add/edit modal), same fields as calendar.events ----
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

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
        $this->selectedDate = now()->toDateString();
    }

    public function prevMonth(): void
    {
        $this->month = Carbon::parse($this->month . '-01')->subMonthNoOverflow()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->month = Carbon::parse($this->month . '-01')->addMonthNoOverflow()->format('Y-m');
    }

    public function goToday(): void
    {
        $this->month = now()->format('Y-m');
        $this->selectedDate = now()->toDateString();
    }

    public function selectDay(string $date): void
    {
        $this->selectedDate = $date;
    }

    /**
     * Only admin/CEO (one merged identity) or a Project Manager may touch the
     * calendar at all; and a Project Manager may only touch an event THEY
     * created — never one made by someone else. Admin always overrides
     * (system owner, same convention used everywhere else this session).
     */
    public function canManage(CalendarEvent $event): bool
    {
        $user = auth()->user();
        if ($user->role === 'admin') {
            return true;
        }
        return \App\Support\EditGate::allows() && $event->created_by === $user->id;
    }

    public function markCompleted($id)
    {
        $event = CalendarEvent::findOrFail($id);
        abort_unless($this->canManage($event), 403);
        $event->markAsCompleted();
        session()->flash('success', 'Event marked as completed!');
    }

    public function markCancelled($id)
    {
        $event = CalendarEvent::findOrFail($id);
        abort_unless($this->canManage($event), 403);
        $event->markAsCancelled();
        session()->flash('success', 'Event cancelled.');
    }

    public function openAddModal(?string $date = null): void
    {
        $this->resetForm();
        $this->start_at = ($date ?: $this->selectedDate ?: now()->toDateString()) . 'T09:00';
        $this->showModal = true;
    }

    public function resetForm(): void
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

    public function save(): void
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
        if (! $this->eventId) {
            $event->created_by = auth()->id();
        }
        $event->save();

        $this->selectedDate = Carbon::parse($event->start_at)->toDateString();
        $this->month = Carbon::parse($event->start_at)->format('Y-m');

        session()->flash('success', $event->fresh()->communication_id
            ? 'Event saved. Participants have been notified with the details.'
            : 'Event saved successfully!');
        $this->showModal = false;
        $this->resetForm();
    }

    public function edit($id): void
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

    public function delete($id): void
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

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function render()
    {
        $user = auth()->user();

        // Same company-wide-vs-own-only rule as calendar.events / dashboards —
        // "only show tasks and other things if assigned based on role or
        // designations". Admin/CEO always sees everyone's calendar — "proper
        // calendar view for admin to show all types of user".
        $seesAll = $user->role === 'admin'
            || $user->hasPermission('Projects', 'Assign') || $user->hasPermission('Tasks', 'Assign')
            || $user->hasPermission('Reports', 'View');
        $myStaffId = $seesAll ? null : staff::where('user_id', $user->id)->value('id');

        $monthStart = Carbon::parse($this->month . '-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $gridStart = $monthStart->copy()->startOfWeek(Carbon::SUNDAY);
        $gridEnd = $monthEnd->copy()->endOfWeek(Carbon::SUNDAY);

        $scope = function ($q) use ($seesAll, $myStaffId) {
            if (! $seesAll) {
                $q->where(function ($x) use ($myStaffId) {
                    $x->where('assigned_to', $myStaffId)->orWhere('notify_all', true);
                });
            }
        };

        $monthEvents = CalendarEvent::with('assignedTo')
            ->whereBetween('start_at', [$gridStart->copy()->startOfDay(), $gridEnd->copy()->endOfDay()])
            ->tap($scope)
            ->orderBy('start_at')
            ->get()
            ->groupBy(fn ($e) => $e->start_at->toDateString());

        $cells = [];
        $cursor = $gridStart->copy();
        while ($cursor->lte($gridEnd)) {
            $key = $cursor->toDateString();
            $cells[] = [
                'date' => $key,
                'day' => $cursor->day,
                'inMonth' => $cursor->month === $monthStart->month,
                'isToday' => $cursor->isToday(),
                'isWeekend' => $cursor->isWeekend(),
                'events' => $monthEvents->get($key, collect()),
            ];
            $cursor->addDay();
        }

        $dayEvents = $this->selectedDate ? $monthEvents->get($this->selectedDate, collect()) : collect();

        return $this->view([
            'cells' => $cells,
            'monthLabel' => $monthStart->format('F Y'),
            'dayEvents' => $dayEvents,
            'staffMembers' => staff::orderBy('name')->get(),
            'projects' => Project::orderBy('name')->get(['id', 'name']),
            'contacts' => Contact::with('company:id,company_name')->orderBy('first_name')->get(),
            'overdueCount' => CalendarEvent::overdue()->tap($scope)->count(),
            'todayCount' => CalendarEvent::today()->tap($scope)->count(),
            'upcomingCount' => CalendarEvent::upcoming()->tap($scope)->count(),
            'seesAll' => $seesAll,
        ])->layout('layouts.app');
    }
};
?>
<div wire:poll.30s>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header a-reveal">
            <div>
                <h1>Calendar</h1>
                <p>{{ $seesAll ? 'Company-wide calendar — every team member\'s events, in one grid.' : 'Your calendar — click a day to view or add an event.' }}</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('calendar.events') }}" class="btn btn-secondary">
                    <i class="fas fa-list"></i> List View
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

        <!-- Stats Summary -->
        <div class="row g-3 mb-4 a-stagger">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon red"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="stat-info"><h3>Overdue</h3><p class="stat-number">{{ $overdueCount }}</p></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-calendar-day"></i></div>
                    <div class="stat-info"><h3>Today</h3><p class="stat-number">{{ $todayCount }}</p></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-info"><h3>Upcoming</h3><p class="stat-number">{{ $upcomingCount }}</p></div>
                </div>
            </div>
        </div>

        <style>
            .cal-wrap { overflow-x:auto; -webkit-overflow-scrolling:touch; }
            .cal-grid { display:grid; grid-template-columns:repeat(7, minmax(38px, 1fr)); gap:4px; min-width:280px; }
            .cal-head { text-align:center; font-size:11px; font-weight:700; letter-spacing:.04em; text-transform:uppercase; color:var(--cp-muted,#8a8f98); padding:4px 0 8px; white-space:nowrap; }
            .cal-cell { min-height:104px; border:1px solid var(--cp-border,#e5e7eb); border-radius:10px; padding:6px; cursor:pointer; transition:box-shadow .15s ease, border-color .15s ease, transform .15s ease; background:var(--cp-surface,#fff); display:flex; flex-direction:column; min-width:0; }
            .cal-cell:hover { border-color:#4f46e5; box-shadow:0 4px 14px rgba(79,70,229,.12); transform:translateY(-1px); }
            .cal-cell.out { opacity:.42; }
            .cal-cell.today { border-color:#4f46e5; box-shadow:0 0 0 2px rgba(79,70,229,.18) inset; }
            .cal-cell.sel { background:#f5f5ff; border-color:#4f46e5; }
            .cal-daynum { font-size:12.5px; font-weight:700; margin-bottom:4px; display:flex; justify-content:space-between; align-items:center; }
            .cal-daynum .today-dot { width:6px; height:6px; border-radius:50%; background:#4f46e5; display:inline-block; flex-shrink:0; }
            .cal-pill { font-size:10.5px; padding:2px 6px; border-radius:6px; margin-bottom:3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color:#fff; font-weight:600; }
            .cal-more { font-size:10px; color:var(--cp-muted,#8a8f98); font-weight:600; }
            .cal-month-head { flex-wrap:wrap; gap:8px; }
            @media (max-width: 991px) {
                .cal-grid { grid-template-columns:repeat(7, minmax(34px, 1fr)); }
                .cal-cell { min-height:88px; padding:4px; }
            }
            @media (max-width: 575px) {
                .cal-grid { grid-template-columns:repeat(7, minmax(30px, 1fr)); gap:3px; min-width:260px; }
                .cal-cell { min-height:52px; border-radius:7px; padding:3px; }
                .cal-cell.out { display:none; }
                .cal-head { font-size:9px; padding:2px 0 6px; }
                .cal-daynum { font-size:11px; margin-bottom:0; }
                .cal-pill { display:none; }
                .cal-more { font-size:9px; }
            }
        </style>

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card a-reveal">
                    <div class="card-header d-flex align-items-center justify-content-between cal-month-head">
                        <h3 class="card-title mb-0">{{ $monthLabel }}</h3>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-secondary" wire:click="prevMonth"><i class="fas fa-chevron-left"></i></button>
                            <button class="btn btn-outline-secondary" wire:click="goToday">Today</button>
                            <button class="btn btn-outline-secondary" wire:click="nextMonth"><i class="fas fa-chevron-right"></i></button>
                        </div>
                    </div>
                    <div class="card-body">
                      <div class="cal-wrap">
                        <div class="cal-grid mb-1">
                            @foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d)
                                <div class="cal-head">{{ $d }}</div>
                            @endforeach
                        </div>
                        <div class="cal-grid">
                            @foreach ($cells as $cell)
                                <div class="cal-cell {{ ! $cell['inMonth'] ? 'out' : '' }} {{ $cell['isToday'] ? 'today' : '' }} {{ $selectedDate === $cell['date'] ? 'sel' : '' }}"
                                     wire:click="selectDay('{{ $cell['date'] }}')" wire:key="cal-{{ $cell['date'] }}">
                                    <div class="cal-daynum">
                                        <span>{{ $cell['day'] }}</span>
                                        @if ($cell['isToday']) <span class="today-dot"></span> @endif
                                    </div>
                                    @foreach ($cell['events']->take(3) as $ev)
                                        <div class="cal-pill {{ $ev->type_badge['class'] }}"
                                             @if ($this->canManage($ev))
                                                 @click.stop="$wire.edit({{ $ev->id }})"
                                             @endif
                                             title="{{ $ev->title }}{{ $this->canManage($ev) ? '' : ' (view only — created by someone else)' }}">
                                            {{ $ev->all_day ? '' : $ev->start_at->format('H:i') . ' ' }}{{ $ev->title }}
                                        </div>
                                    @endforeach
                                    @if ($cell['events']->count() > 3)
                                        <span class="cal-more">+{{ $cell['events']->count() - 3 }} more</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                      </div>
                    </div>
                </div>
            </div>

            <!-- Selected day panel -->
            <div class="col-lg-4">
                <div class="card a-reveal">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h3 class="card-title mb-0">
                            {{ $selectedDate ? \Carbon\Carbon::parse($selectedDate)->format('D, M j') : 'Pick a day' }}
                        </h3>
                        @if ($selectedDate && \App\Support\EditGate::allows())
                            <button class="btn btn-sm btn-primary" wire:click="openAddModal('{{ $selectedDate }}')">
                                <i class="fas fa-plus"></i>
                            </button>
                        @endif
                    </div>
                    <div class="card-body" style="max-height:520px; overflow-y:auto;">
                        @forelse ($dayEvents as $event)
                            <div class="d-flex align-items-start gap-2 mb-3 pb-3" style="border-bottom:1px solid #f1f2f4;">
                                <span class="badge {{ $event->type_badge['class'] }} mt-1"><i class="fas {{ $event->type_badge['icon'] }}"></i></span>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold {{ $event->isOverdue() ? 'text-danger' : '' }}">{{ $event->title }}</div>
                                    <small class="text-muted d-block">
                                        {{ $event->all_day ? 'All day' : $event->start_at->format('H:i') }}
                                        @if($event->location) · <i class="fas fa-location-dot"></i> {{ $event->location }} @endif
                                    </small>
                                    <small class="text-muted d-block">
                                        {{ $event->assignedTo->name ?? ($event->notify_all ? 'Everyone' : 'Unassigned') }}
                                        · <span class="badge {{ $event->status_badge['class'] }}">{{ ucfirst($event->status) }}</span>
                                    </small>
                                    @if ($event->status === 'scheduled' && $this->canManage($event))
                                        <div class="btn-group btn-group-sm mt-2">
                                            <button class="btn btn-outline-secondary" wire:click="edit({{ $event->id }})"><i class="fas fa-edit"></i></button>
                                            <button class="btn btn-outline-success" wire:click="markCompleted({{ $event->id }})"><i class="fas fa-check"></i></button>
                                            <button class="btn btn-outline-danger" wire:click="markCancelled({{ $event->id }})" wire:confirm="Cancel this event?"><i class="fas fa-times"></i></button>
                                        </div>
                                    @elseif ($event->status === 'scheduled')
                                        <small class="text-muted d-block mt-1"><i class="fas fa-lock"></i> Only the creator (or CEO) can update this event.</small>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-muted small text-center py-4 mb-0">
                                <i class="fas fa-calendar-alt fa-2x d-block mb-2"></i>
                                No events {{ $selectedDate ? 'on this day' : '' }}.
                            </p>
                        @endforelse
                    </div>
                </div>
            </div>
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
                    <button class="btn btn-secondary" wire:click="closeModal"><i class="fas fa-times"></i> Cancel</button>
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
