<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use Uuid, SoftDeletes;

    public $table = 'orders';

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
        'branch_id',
        'branch_name',
        'agreement_no',
        'po_no',
        'po_date',
        'contract_status',
        'asset_code',
        'chassis_number',
        'machine_number',
        'license_plate',
        'owner_asset',
        'manufacturing_year',
        'bpkb_no',
        'asset_color',
        'total_otr',
        'down_payment',
        'admin_fee',
        'fiducia_fee',
        'stamp_fee',
        'product_offering_fee',
        'customer_name',
        'customer_address',
        'customer_rt',
        'customer_rw',
        'customer_city',
        'customer_phone_number',
        'customer_account_bank',
        'customer_account_number',
        'created_at',
        'updated_at',
        'is_disbursement',
        'is_send_invoice',
        'cashback',
        'adminpsa_fee',
        'stnk_date',
        'stnk_fee'
    ];

      public function disbursement()
      {
          return $this->hasOne(Disbursement::class, 'order_id');
      }

    public function partner()
    {
        return $this->belongsTo(Partner::class, 'branch_id', 'code');
    }

    public function transaction_callback(){
        return $this->hasMany(TransactionCallback::class, 'order_id');
    }
}
