<?php

namespace App\Models;

use App\Notifications\PasswordReset;
use App\Traits\HasRolesAndPermissions;
use App\Traits\Uuid;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

/**
 * @method static findOrFail($id)
 */
class User extends Authenticatable
{
    use Notifiable, HasApiTokens, Uuid, HasRolesAndPermissions;

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
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'first_name',
        'last_name',
        'username',
        'phone_number',
        'email',
        'active',
        'avatar',
        'password',
        'created_at',
        'updated_at'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'active' => 'boolean',
        'email_verified_at' => 'datetime',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public $table = 'users';

    /**
     * [OVERRIDE].
     *
     * @param string $token
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new PasswordReset($token));
    }

    public static function sql()
    {
        return self::select('users.id as id',
            'users.first_name',
            'users.last_name',
            'users.username',
            'users.email',
            'users.active',
            'users.avatar',
            'users.password',
            'users.created_at',
            'users.updated_at'
        )->with('roles');
    }

    public function image()
    {
        return $this->belongsTo(Image::class, 'avatar', 'id');
    }

    public function findForPassport($identifier)
    {
        return $this
            ->where('active', '=', 1)
            ->where('email', $identifier)
            ->first();
    }


    public function user_branch()
    {
        return $this->hasOne(UserBranch::class);
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, UserBranch::class, 'user_id', 'branch_id');
    }


    /**
     * @return boolean
     */
    public function isActivated()
    {
        if ($this->active == 1) {
            return true;
        } else {
            return false;
        }
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_role');
    }
}
