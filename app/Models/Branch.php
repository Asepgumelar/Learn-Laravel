<?php


namespace App\Models;


use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use Uuid, SoftDeletes;

    public $table = 'branches';

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
        'name',
        'description',
    ];

    public static function sql()
    {
        return self::select(
            'branches.id',
            'branches.name',
            'branches.description'
        );
    }

    public function user_branch()
    {
        return $this->hasMany(UserBranch::class);
    }

    public function partners()
    {
        return $this->belongsToMany(Partner::class, 'branch_partners');
    }

}
