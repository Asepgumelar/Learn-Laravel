<?php


namespace App\Models;


use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;

class DisbursementCallback extends Model
{
    use Uuid;

    public $table = 'disbursement_callback';

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
        'disbursement_id',
        'is_instant',
        'xendit_id',
        'xendit_user_id',
        'external_id',
        'amount',
        'bank_code',
        'account_holder_name',
        'disbursement_description',
        'status',
        'failure_code',
        'email_to',
        'email_cc',
        'email_bcc',
    ];

    public function disbursement(){
        return $this->belongsTo(Disbursement::class);
    }
}