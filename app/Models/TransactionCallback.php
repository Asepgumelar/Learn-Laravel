<?php


namespace App\Models;


use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;

class TransactionCallback extends Model
{
    use Uuid;

    public $table = 'transaction_callback';

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
        'po_no',
        'order_id',
        'status',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
