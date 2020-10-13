<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuid;
use Illuminate\Database\Eloquent\SoftDeletes;

class Partner extends Model
{
    use Uuid, SoftDeletes;

    public $table = 'partners';

    public $incrementing = false;

    protected $primaryKey = 'id';

    public $keyType = 'string';

    protected $dates = [
        'created_at', 'updated_at'
    ];

    protected $fillable = [
        'id', 'code', 'name', 'address'
    ];

    public function branch_partner()
    {
        return $this->hasOne(BranchPartner::class);
    }

    public function branchs()
    {
        return $this->belongsToMany(Branch::class, 'branch_partners');
    }

}

