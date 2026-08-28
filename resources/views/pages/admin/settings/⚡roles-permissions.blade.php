<?php

use Livewire\Component;
use App\Models\Role;

new class extends Component
{
    public $roleId;
    public $name;
    public $description;
    public $permissions = [];

    public $roles;
    public $showModal = false;

    public $modules = [
        'Contacts', 'Companies', 'Deals', 'Projects', 'Tasks', 'Bugs', 'Communications',
        'Staff', 'Attendance', 'Services', 'Products', 'Portfolio', 'Testimonials',
        'Estimates', 'Quotations', 'Finance', 'Pricing', 'Blog', 'Reports', 'Settings',
    ];

    public $actions = ['View', 'Create', 'Edit', 'Delete', 'Approve', 'Assign'];

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
    ];

    public function mount()
    {
        $this->fetchRoles();
    }

    public function fetchRoles()
    {
        $this->roles = Role::orderBy('name')->get();
    }

    public function openAddModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function resetForm()
    {
        $this->roleId = null;
        $this->name = null;
        $this->description = null;
        $this->permissions = $this->emptyPermissions();
        $this->resetErrorBag();
    }

    protected function emptyPermissions()
    {
        $permissions = [];
        foreach ($this->modules as $module) {
            foreach ($this->actions as $action) {
                $permissions[$module][$action] = false;
            }
        }
        return $permissions;
    }

    public function save()
    {
        if (!auth()->user()->hasPermission('Settings', $this->roleId ? 'Edit' : 'Create')) {
            session()->flash('error', "You don't have permission to " . ($this->roleId ? 'edit' : 'create') . ' roles.');
            return;
        }

        $this->validate();

        if ($this->roleId) {
            $role = Role::find($this->roleId);
        } else {
            $role = new Role;
        }

        $role->name = $this->name;
        $role->description = $this->description;
        $role->permissions = $this->permissions;
        $role->save();

        session()->flash('success', 'Role saved successfully!');

        $this->showModal = false;
        $this->resetForm();
        $this->fetchRoles();
    }

    public function edit($id)
    {
        $role = Role::findOrFail($id);
        $this->roleId = $role->id;
        $this->name = $role->name;
        $this->description = $role->description;

        $permissions = $this->emptyPermissions();
        $existing = $role->permissions ?: [];
        foreach ($this->modules as $module) {
            foreach ($this->actions as $action) {
                if (!empty($existing[$module][$action])) {
                    $permissions[$module][$action] = true;
                }
            }
        }
        $this->permissions = $permissions;

        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function delete($id)
    {
        if (!auth()->user()->hasPermission('Settings', 'Delete')) {
            session()->flash('error', "You don't have permission to delete roles.");
            return;
        }

        try {
            Role::findOrFail($id)->delete();
            session()->flash('success', 'Role deleted successfully!');
            $this->fetchRoles();
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting role: ' . $e->getMessage());
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function render()
    {
        return $this->view()->layout('layouts.app');
    }
};
?>

<div>
    <div class="dashboard">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>Roles &amp; Permissions</h1>
                <p>Define roles and manage their permission sets.</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" wire:click="openAddModal">
                    <i class="fas fa-plus"></i> Add Role
                </button>
            </div>
        </div>

        <!-- Reference-only notice -->
        <div class="alert alert-info d-flex align-items-start" role="alert">
            <i class="fas fa-info-circle me-2 mt-1"></i>
            <div>
                <strong>Note:</strong> These permissions are enforced across the system — sidebar links,
                page access, and delete actions are all restricted per role and module below.
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

        <!-- Roles List -->
        <div class="row">
            @forelse ($roles as $role)
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="fw-semibold mb-0">
                                    <i class="fas fa-user-shield text-primary me-2"></i>
                                    {{ $role->name }}
                                </h5>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-secondary" wire:click="edit({{ $role->id }})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-outline-danger"
                                            wire:click="delete({{ $role->id }})"
                                            wire:confirm="Are you sure you want to delete this role?">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-3">{{ $role->description ?: 'No description provided.' }}</p>
                            @php
                                $rolePermissions = $role->permissions ?: [];
                                $activeModules = collect($rolePermissions)
                                    ->filter(fn ($actions) => collect($actions)->contains(true));
                                $letters = ['View' => 'V', 'Create' => 'C', 'Edit' => 'E', 'Delete' => 'D'];
                            @endphp
                            @forelse ($activeModules as $module => $acts)
                                <div class="d-flex align-items-center justify-content-between py-1" style="border-bottom:1px dashed #eee;font-size:12px;">
                                    <span class="fw-medium">{{ $module }}</span>
                                    <span class="d-flex gap-1">
                                        @foreach ($letters as $act => $l)
                                            <span class="badge {{ !empty($acts[$act]) ? 'bg-primary' : 'bg-light text-muted border' }}"
                                                  style="width:20px;" title="{{ $act }}">{{ $l }}</span>
                                        @endforeach
                                    </span>
                                </div>
                            @empty
                                <span class="text-muted small">No permissions set.</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-user-shield fa-3x text-muted mb-3 d-block"></i>
                            <h5 class="text-muted">No roles found</h5>
                            <p class="text-muted">Add a new role to get started.</p>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Add / Edit Role Modal -->
    @if($showModal)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.5);" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-shield text-primary me-2"></i>
                        {{ $roleId ? 'Edit Role' : 'Add Role' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="save">
                        <div class="mb-3">
                            <label class="form-label fw-medium">
                                <i class="fas fa-user-shield me-1 text-muted"></i>
                                Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   wire:model="name" placeholder="Enter role name">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">
                                <i class="fas fa-align-left me-1 text-muted"></i>
                                Description
                            </label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      wire:model="description" rows="2" placeholder="Enter role description"></textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <label class="form-label fw-medium">
                            <i class="fas fa-lock me-1 text-muted"></i>
                            Permissions
                        </label>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Module</th>
                                        @foreach ($actions as $action)
                                            <th class="text-center">{{ $action }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($modules as $module)
                                        <tr>
                                            <td>{{ $module }}</td>
                                            @foreach ($actions as $action)
                                                <td class="text-center">
                                                    <input type="checkbox" class="form-check-input"
                                                           wire:model="permissions.{{ $module }}.{{ $action }}">
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" wire:click="closeModal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button class="btn btn-primary" wire:click="save">
                        <i class="fas {{ $roleId ? 'fa-save' : 'fa-plus' }}"></i>
                        {{ $roleId ? 'Update' : 'Add' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
