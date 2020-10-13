<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;

/**
 * Role Model.
 *
 * @package App\Models
 */
class Role extends Model
{
    use Uuid;

    public $table = 'roles';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The "type" of the auto-incrementing ID.
     * @var string
     */
    public $keyType = 'string';

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
        'slug',
        'role_name',
        'description',
        'is_active',
        'is_default'
    ];

    public static function sql()
    {
        return self::select(
            'roles.id',
            'roles.slug',
            'roles.role_name',
            'roles.description',
            'roles.is_active',
            'roles.is_default'
        );
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class,
            RolePermission::class,
            'role_id',
            'permission_id');
    }

    public function status()
    {
        return $this->belongsToMany(Status::class,
            RoleStatus::class,
            'role_id',
            'status_id');
    }

    public function user_role()
    {
        return $this->hasMany(UserRole::class);
    }

    public function findBySlug($slugName)
    {
        $role = static::where('slug', $slugName)->first();
        return $role;
    }
}
