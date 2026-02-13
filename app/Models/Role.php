<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    /**
     * Roles belong to many permissions.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    /**
     * Roles have many users.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Check if this role has a specific permission.
     */
    public function hasPermission(string $permissionName): bool
    {
        return $this->permissions()->where('name', $permissionName)->exists();
    }

    /**
     * Grant a permission to this role.
     */
    public function givePermissionTo(int|string|Permission $permission): self
    {
        $permissionId = is_int($permission) ? $permission : (
            is_string($permission) ? Permission::where('name', $permission)->value('id') : $permission->id
        );

        if ($permissionId) {
            $this->permissions()->syncWithoutDetaching([$permissionId]);
        }

        return $this;
    }

    /**
     * Revoke a permission from this role.
     */
    public function revokePermissionTo(int|string|Permission $permission): self
    {
        $permissionId = is_int($permission) ? $permission : (
            is_string($permission) ? Permission::where('name', $permission)->value('id') : $permission->id
        );

        if ($permissionId) {
            $this->permissions()->detach($permissionId);
        }

        return $this;
    }

    /**
     * Sync permissions for this role.
     */
    public function syncPermissions(array $permissionIds): self
    {
        $this->permissions()->sync($permissionIds);

        return $this;
    }
}
