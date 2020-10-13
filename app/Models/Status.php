<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuid;

class Status extends Model
{
    use Uuid;

    public $table = 'status';

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
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'description'
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class,
            RoleStatus::class,
            'status_id',
            'role_id'
        );
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class,
            OrderStatus::class,
            'status_id',
            'order_id'

        );
    }
}
