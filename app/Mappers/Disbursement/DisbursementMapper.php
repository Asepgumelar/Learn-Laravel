<?php


namespace App\Mappers\Disbursement;


use App\Contracts\Mapper;
use App\Mappers\BaseMapper;

class DisbursementMapper extends BaseMapper implements Mapper
{

    function single($item)
    {
        $result = [];
        $result['order_id'] = $item->order_id;
        $result['external_id'] = $item->external_id;
        $result['bank_code'] = $item->bank_code;
        $result['account_holder_name'] = $item->account_holder_name;
        $result['account_number'] = $item->account_number;
        $result['description'] = $item->description;
        $result['amount'] = $item->amount;
        return $result;
    }

    function list($items)
    {
        $result = [];
        foreach ($items as $item) {
            $result[] = $this->single($item);
        }
        return $result;
    }
}