<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Menu
 *
 * @author Odenktools Technology
 * @license MIT
 * @copyright (c) 2020, Odenktools Technology.
 *
 * @package App\Models
 *
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Menu newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Menu newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Menu onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Menu query()
 */
class Menu extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    public $table = 'menus';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at', 'updated_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'parent_id',
        'menu_title',
        'slug',
        'url',
        'icon',
        'menu_order',
        'is_active',
        'is_default',
        'description'
    ];

    public static function adminSidebar($withRole = true)
    {
        $defaultMenuRoot = config('psa.DEFAULT_SIDEBAR_ID');
        if ($withRole) {
            return self::select('*')->where('parent_id', $defaultMenuRoot)
                ->with(['children'])
                ->whereHas('roles', function ($query) {
                    $roles = auth()->user()->roles()->pluck('roles.id')->toArray();
                    $query->whereIn('roles.id', $roles);
                })
                ->orderBy('menu_order', 'asc');
        } else {
            return self::select('*')->where('parent_id', $defaultMenuRoot)
                ->with(['children'])
                ->orderBy('menu_order', 'asc');
        }
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id', 'id');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'menu_roles',
            'menu_id', 'role_id')
            ->using('App\Models\MenuRole')
            ->withPivot('is_enabled', 'access');
    }

    public function menu_roles()
    {
        return $this->hasMany(MenuRole::class);
    }

    public function findBySlug(string $roleName, $slugName = null)
    {
        $role = static::where('role_name', $roleName)->where('slug', $slugName)->first();
        return $role;
    }

    /**
     * Get the parent that owns the node.
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Get value of the model parent_id column.
     *
     * @return int
     */
    public function getParentId()
    {
        return $this->getAttribute('parent_id');
    }
}
