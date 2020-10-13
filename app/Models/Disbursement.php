<?php


namespace App\Models;


use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;

class Disbursement extends Model
{
    use Uuid;

    public $table = 'disbursements';

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
        'order_id',
        'external_id',
        'bank_id',
        'account_holder_name',
        'account_number',
        'description',
        'amount',
        'status',
        'email_to',
        'email_cc',
        'email_bcc',
        'note',
    ];

    public function disbursementCallback()
    {
        return $this->hasOne(DisbursementCallback::class, 'disbursement_id', 'id');
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
