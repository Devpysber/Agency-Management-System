<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'notifications_seen_at',
        'messages_seen_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The contact record this user logs into the client portal as, if any.
     */
    public function contact()
    {
        return $this->hasOne(Contact::class);
    }

    /**
     * Whether this user can perform $action (View/Create/Edit/Delete) on $module,
     * per the Roles & Permissions matrix. Admins always pass. Modules that
     * aren't part of the matrix (Tasks, Calendar, Communications, Dashboard)
     * are always allowed since they carry no restriction.
     */
    public function hasPermission(string $module, string $action): bool
    {
        if ($this->role === 'admin') {
            return true;
        }

        // A designation (CEO, Project Manager, ...) gets its own permission
        // row when one exists, so staff aren't all collapsed onto the single
        // coarse `staff` role. Designations without a row yet fall back to
        // the account role, same as before.
        $designation = \App\Models\staff::where('user_id', $this->id)->value('designation');

        $role = ($designation ? \App\Models\Role::where('name', $designation)->first() : null)
            ?? \App\Models\Role::where('name', $this->role)->first();

        if (!$role) {
            return false;
        }

        return (bool) ($role->permissions[$module][$action] ?? false);
    }
}
