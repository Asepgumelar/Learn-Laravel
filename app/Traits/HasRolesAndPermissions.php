<?php


namespace App\Traits;


use App\Models\Role;
use App\Models\UserRole;

trait HasRolesAndPermissions
{

    public function user_role()
    {
        return $this->hasOne(UserRole::class);
    }

    /**
     * @return mixed
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, UserRole::class, 'user_id', 'role_id');
    }


    /**
     * Checking User has one or more role??
     *
     */
    public function hasAnyRole()
    {
        $data = $this->roles->first();
        if ($data === null) {
            return false;
        }
        return true;
    }

    /**
     * Check if user has role.
     *
     * <code>
     * $roles = auth()->user()->hasRole(['Owner', 'Administrator']);
     * dd($roles);
     * </code>
     *
     * @param $roles
     * @param string|null $slug
     * @return bool
     */
    public function hasRole($roles, string $slug = null)
    {
        if (is_string($roles) && $roles === '*') {
            return true;
        }

        if (is_string($roles) && false !== strpos($roles, '|')) {
            $roles = $this->convertPipeToArray($roles);
        }

        if (is_string($roles)) {
            return $this->roles->contains('slug', $roles);
        }

        if (is_array($roles)) {
            foreach ($roles as $role) {
                if ($this->hasRole($role, $slug)) {
                    return true;
                }
            }
            return false;
        }

        return $roles->intersect($slug ? $this->roles->where('slug', $slug) : $this->roles)->isNotEmpty();
    }

    public function hasAnyRoles(...$roles)
    {
        return $this->hasRole($roles);
    }

    protected function convertPipeToArray(string $pipeString)
    {
        $pipeString = trim($pipeString);

        if (strlen($pipeString) <= 2) {
            return $pipeString;
        }

        $quoteCharacter = substr($pipeString, 0, 1);
        $endCharacter = substr($quoteCharacter, -1, 1);

        if ($quoteCharacter !== $endCharacter) {
            return explode('|', $pipeString);
        }

        if (!in_array($quoteCharacter, ["'", '"'])) {
            return explode('|', $pipeString);
        }

        return explode('|', trim($pipeString, $quoteCharacter));
    }

    public function hasPermission($permission)
    {
        foreach ($this->user_role->role->permissions as $rolePermission) {
            if ($rolePermission->guard_name == $permission) {
                return true;
            }
        }
        return false;
    }
}