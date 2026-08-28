<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Project;
use App\Models\staff;
use App\Models\Contact;
use Illuminate\Support\Facades\Hash;

new class extends Component
{
    use WithPagination;

    public $userId;
    public $name;
    public $email;
    public $password;
    public $password_confirmation;
    public $role = 'staff';
    public $project_id = '';   // optional: attach the new staff/client to a project
    public $designation = 'Staff';

    public $search = '';
    public $showModal = false;

    public $roleOptions = ['admin', 'manager', 'staff', 'client'];
    public $generatedPassword = null;
    public $generatedEmail = null;

    // Canonical designation roster (docs/rbac-spec.md) — a free-text field
    // here risks a typo silently falling through hasPermission() as "no
    // permission" (the exact failure mode that spec warns about), since
    // permissions are matched by exact Role name = staff.designation.
    public $designationOptions = [
        'Staff',
        'CEO', 'COO',
        'HR & Admin Manager',
        'Account Manager', 'Business Development Manager', 'Sales Executive',
        'Project Manager', 'Project Coordinator',
        'Creative Director', 'UI/UX Designer', 'Graphic Designer', 'Designer',
        'Tech Lead', 'Developer', 'Developer Intern',
        'QA Engineer', 'DevOps Engineer',
        'AI/ML Engineer', 'AI/ML Intern', 'Data Analyst',
        'Marketing Manager', 'SEO Specialist', 'Social Media Manager',
        'Content Writer', 'Performance Marketing Specialist',
        'Finance Manager', 'Accountant', 'Finance Executive',
        'Intern',
    ];

    protected function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . ($this->userId ?: 'NULL') . ',id',
            'role' => 'required|in:admin,manager,staff,client',
            'project_id' => 'nullable|exists:projects,id',
        ];

        // Password is optional even on create now — save() auto-generates
        // one when left blank, so admin doesn't have to invent one.
        $rules['password'] = 'nullable|string|min:8|confirmed';

        return $rules;
    }

    /** Fills the password fields with a random one, shown in the clear so
     * admin can see/copy it before saving (same idea as the client-portal
     * "temporary password, shown once" flow). */
    public function generatePassword()
    {
        $pwd = \Illuminate\Support\Str::password(12, symbols: false);
        $this->password = $pwd;
        $this->password_confirmation = $pwd;
    }

    protected function baseQuery()
    {
        $query = User::query();

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderBy('name');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function openAddModal()
    {
        $this->resetForm();
        $this->generatedPassword = null;
        $this->generatedEmail = null;
        $this->showModal = true;
    }

    public function dismissGeneratedCredentials()
    {
        $this->generatedPassword = null;
        $this->generatedEmail = null;
    }

    public function resetForm()
    {
        $this->userId = null;
        $this->name = null;
        $this->email = null;
        $this->password = null;
        $this->password_confirmation = null;
        $this->role = 'staff';
        $this->project_id = '';
        $this->designation = 'Staff';
        $this->resetErrorBag();
    }

    public function save()
    {
        if (!auth()->user()->hasPermission('Settings', $this->userId ? 'Edit' : 'Create')) {
            session()->flash('error', "You don't have permission to " . ($this->userId ? 'edit' : 'create') . ' users.');
            return;
        }

        $this->validate();

        $isNew = ! $this->userId;

        if ($this->userId) {
            $user = User::find($this->userId);
        } else {
            $user = new User;
            // Auto-generate credentials when admin didn't set one — the
            // point of this flow is admin creates the account, the system
            // hands back a password to share, not admin inventing one.
            if (empty($this->password)) {
                $this->password = \Illuminate\Support\Str::password(12, symbols: false);
            }
        }

        $user->name = $this->name;
        $user->email = $this->email;
        $user->role = $this->role;

        if (!empty($this->password)) {
            $user->password = Hash::make($this->password);
        }

        $user->save();

        $extra = '';

        // Provision a staff record (+ optional project assignment) for a new staff user.
        if (!$this->userId && $this->role === 'staff') {
            $member = staff::firstOrNew(['email' => $this->email]);
            $member->fill([
                'name' => $this->name,
                'user_id' => $user->id,
                'designation' => $this->designation ?: 'Staff',
                'status' => 'active',
            ]);
            $member->shift_start = $member->shift_start ?: '09:00';
            $member->daily_hours = $member->daily_hours ?: 8;
            $member->joining_date = $member->joining_date ?: now()->toDateString();
            $member->save();

            if ($this->project_id) {
                Project::find($this->project_id)?->staff()->syncWithoutDetaching([$member->id]);
                $extra = ' Added to the selected project team.';
            }
        }

        // Link a new client user to the project's company via a contact.
        if (!$this->userId && $this->role === 'client' && $this->project_id) {
            $project = Project::find($this->project_id);
            if ($project && $project->company_id) {
                $contact = Contact::firstOrNew(['email' => $this->email]);
                $parts = explode(' ', trim($this->name), 2);
                $contact->fill([
                    'first_name' => $parts[0] ?? $this->name,
                    'last_name' => $parts[1] ?? '',
                    'company_id' => $project->company_id,
                    'user_id' => $user->id,
                    'status' => 'active',
                ])->save();
                $extra = ' Linked to ' . ($project->company->company_name ?? 'the project company') . ' as a portal contact.';
            }
        }

        session()->flash('success', 'User saved successfully!' . $extra);

        $this->showModal = false;
        if ($isNew) {
            // Hand the credentials back — this IS "get credentials of their
            // panel generated by admin itself". Shown once; admin copies it
            // to the new hire. Cleared on the next save()/openAddModal().
            $this->generatedEmail = $this->email;
            $this->generatedPassword = $this->password;
        }
        $this->resetForm();
    }

    public function editUser($id)
    {
        $user = User::findOrFail($id);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role ?: 'staff';
        $this->password = null;
        $this->password_confirmation = null;
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function deleteUser($id)
    {
        if (!auth()->user()->hasPermission('Settings', 'Delete')) {
            session()->flash('error', "You don't have permission to delete users.");
            return;
        }

        if ($id == auth()->id()) {
            session()->flash('error', 'You cannot delete your own account.');
            return;
        }

        try {
            User::findOrFail($id)->delete();
            session()->flash('success', 'User deleted successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting user: ' . $e->getMessage());
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
            'users' => $this->baseQuery()->paginate(15),
            'projectOptions' => Project::with('company:id,company_name')->orderByDesc('created_at')->get(['id', 'name', 'company_id']),
        ])->layout('layouts.app');
    }
};
?>

<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>User Management</h1>
                <p>Manage system users and their roles.</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" wire:click="openAddModal">
                    <i class="fas fa-plus"></i> Add User
                </button>
            </div>
        </div>

        @if ($generatedPassword)
            <div class="card mb-4" style="border:1px solid #a7f3d0;">
                <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-3" style="background:#f0fdf4;">
                    <div>
                        <div class="fw-semibold text-success mb-1"><i class="fas fa-key me-1"></i> Account created — credentials (shown once)</div>
                        <div class="small"><strong>Email:</strong> <code>{{ $generatedEmail }}</code> &nbsp; <strong>Password:</strong> <code>{{ $generatedPassword }}</code></div>
                        <div class="small text-muted mt-1">Copy this now and hand it to them — it won't be shown again here.</div>
                    </div>
                    <button class="btn btn-sm btn-outline-success" wire:click="dismissGeneratedCredentials">
                        <i class="fas fa-check"></i> Got it
                    </button>
                </div>
            </div>
        @endif

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

        <!-- Users Table -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="fw-semibold mb-0">
                        <i class="fas fa-users text-primary me-2"></i>
                        Users List
                    </h5>
                    <span class="badge bg-primary">{{ $users->total() }} Users</span>
                </div>
            </div>
            <div class="card-body">
                <!-- Search -->
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control"
                               wire:model.live="search"
                               placeholder="Search by name or email...">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Created</th>
                                <th style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                            <tr>
                                <td>
                                    <h6 class="mb-0 fw-semibold">{{ $user->name }}</h6>
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    @php
                                        $roleColors = [
                                            'admin' => 'bg-danger',
                                            'manager' => 'bg-warning text-dark',
                                            'staff' => 'bg-secondary',
                                            'client' => 'bg-info',
                                        ];
                                        $roleColor = $roleColors[$user->role] ?? 'bg-secondary';
                                    @endphp
                                    <span class="badge {{ $roleColor }}">
                                        {{ ucfirst($user->role ?: 'staff') }}
                                    </span>
                                </td>
                                <td>{{ $user->created_at?->format('M d, Y') }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-secondary" wire:click="editUser({{ $user->id }})">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-outline-danger"
                                                wire:click="deleteUser({{ $user->id }})"
                                                wire:confirm="Are you sure you want to delete this user?">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <i class="fas fa-users fa-3x text-muted mb-3 d-block"></i>
                                    <h5 class="text-muted">No users found</h5>
                                    <p class="text-muted">Add a new user to get started.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($users->hasPages())
                <div class="d-flex justify-content-end mt-3">
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item {{ $users->onFirstPage() ? 'disabled' : '' }}">
                                <button class="page-link" wire:click="previousPage" @if($users->onFirstPage()) disabled @endif>
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                            </li>
                            @for ($page = max(1, $users->currentPage() - 2); $page <= min($users->lastPage(), $users->currentPage() + 2); $page++)
                                <li class="page-item {{ $page == $users->currentPage() ? 'active' : '' }}">
                                    <button class="page-link" wire:click="gotoPage({{ $page }})">{{ $page }}</button>
                                </li>
                            @endfor
                            <li class="page-item {{ !$users->hasMorePages() ? 'disabled' : '' }}">
                                <button class="page-link" wire:click="nextPage" @if(!$users->hasMorePages()) disabled @endif>
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </li>
                        </ul>
                    </nav>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Add / Edit User Modal -->
    @if($showModal)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5);" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user text-primary me-2"></i>
                        {{ $userId ? 'Edit User' : 'Add User' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="save">
                        <div class="mb-3">
                            <label class="form-label fw-medium">
                                <i class="fas fa-user me-1 text-muted"></i>
                                Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   wire:model="name" placeholder="Enter full name">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">
                                <i class="fas fa-envelope me-1 text-muted"></i>
                                Email <span class="text-danger">*</span>
                            </label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                   wire:model="email" placeholder="Enter email address">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">
                                <i class="fas fa-user-tag me-1 text-muted"></i>
                                Role <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('role') is-invalid @enderror" wire:model.live="role">
                                @foreach ($roleOptions as $option)
                                    <option value="{{ $option }}">{{ ucfirst($option) }}</option>
                                @endforeach
                            </select>
                            @error('role')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if (!$userId && in_array($role, ['staff', 'client']))
                            <div class="mb-3 p-3 rounded" style="background:#f8f9ff;border:1px solid #e5e7ff;">
                                <label class="form-label fw-medium">
                                    <i class="fas fa-diagram-project me-1 text-primary"></i>
                                    Assign to Project <span class="text-muted small">(optional)</span>
                                </label>
                                <select class="form-select" wire:model="project_id">
                                    <option value="">— No project —</option>
                                    @foreach ($projectOptions as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }}{{ $p->company ? ' · ' . $p->company->company_name : '' }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">
                                    @if ($role === 'staff')
                                        Creates a staff record and adds them to the project team (can update progress + use project chat).
                                    @else
                                        Links this client login to the project's company as a portal contact.
                                    @endif
                                </div>
                                @if ($role === 'staff')
                                    <label class="form-label fw-medium mt-2"><i class="fas fa-briefcase me-1 text-muted"></i> Designation</label>
                                    <select class="form-select" wire:model="designation">
                                        @foreach ($designationOptions as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Determines what they can see and do — see Roles &amp; Permissions.</div>
                                @endif
                            </div>
                        @endif
                        <div class="mb-3">
                            <label class="form-label fw-medium">
                                <i class="fas fa-lock me-1 text-muted"></i>
                                Password
                                @if($userId)
                                    <small class="text-muted">(leave blank to keep current password)</small>
                                @else
                                    <small class="text-muted">(leave blank — one is generated for you)</small>
                                @endif
                            </label>
                            <div class="input-group">
                                <input type="text" class="form-control @error('password') is-invalid @enderror"
                                       wire:model="password" placeholder="{{ $userId ? 'New password (optional)' : 'Auto-generated if left blank' }}">
                                <button type="button" class="btn btn-outline-secondary" wire:click="generatePassword">
                                    <i class="fas fa-dice"></i> Generate
                                </button>
                            </div>
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">
                                <i class="fas fa-lock me-1 text-muted"></i>
                                Confirm Password
                            </label>
                            <input type="text" class="form-control"
                                   wire:model="password_confirmation" placeholder="Confirm password">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" wire:click="closeModal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button class="btn btn-primary" wire:click="save">
                        <i class="fas {{ $userId ? 'fa-save' : 'fa-plus' }}"></i>
                        {{ $userId ? 'Update' : 'Add' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
