<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'is_system_role',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_system_role' => 'boolean',
    ];

    /**
     * Get the permissions for the role.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->withTimestamps();
    }

    /**
     * Get the users with this role.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }

    /**
     * Check if the role has a specific permission.
     *
     * @param string|Permission $permission
     * @return bool
     */
    public function hasPermission(string|Permission $permission): bool
    {
        if ($permission instanceof Permission) {
            $permission = $permission->name;
        }

        return $this->permissions()->where('name', $permission)->exists();
    }

    /**
     * Check if the role has any of the given permissions.
     *
     * @param array $permissions
     * @return bool
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return $this->permissions()->whereIn('name', $permissions)->exists();
    }

    /**
     * Check if the role has all of the given permissions.
     *
     * @param array $permissions
     * @return bool
     */
    public function hasAllPermissions(array $permissions): bool
    {
        $count = $this->permissions()->whereIn('name', $permissions)->count();
        return $count === count($permissions);
    }

    /**
     * Grant a permission to the role.
     *
     * @param string|Permission $permission
     * @return void
     */
    public function grantPermission(string|Permission $permission): void
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }

        if (!$this->hasPermission($permission)) {
            $this->permissions()->attach($permission->id);
        }
    }

    /**
     * Revoke a permission from the role.
     *
     * @param string|Permission $permission
     * @return void
     */
    public function revokePermission(string|Permission $permission): void
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->first();
            if (!$permission) {
                return;
            }
        }

        $this->permissions()->detach($permission->id);
    }

    /**
     * Sync permissions for the role.
     *
     * @param array $permissions Array of permission names or IDs
     * @return void
     */
    public function syncPermissions(array $permissions): void
    {
        // If permissions are names, convert to IDs
        if (!empty($permissions) && is_string($permissions[0])) {
            $permissionModels = Permission::whereIn('name', $permissions)->get();
            $permissions = $permissionModels->pluck('id')->toArray();
        }

        $this->permissions()->sync($permissions);
    }

    /**
     * Get all permission names for this role.
     *
     * @return array
     */
    public function getPermissionNames(): array
    {
        return $this->permissions()->pluck('name')->toArray();
    }

    /**
     * Check if this role can be deleted (not a system role).
     *
     * @return bool
     */
    public function canDelete(): bool
    {
        return !$this->is_system_role;
    }
}
