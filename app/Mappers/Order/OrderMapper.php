<?php


namespace App\Mappers\Order;


use App\Contracts\Mapper;
use App\Mappers\BaseMapper;

class OrderMapper extends BaseMapper implements Mapper
{

    function single($item)
    {
        $result = [];
        $result['branch_id'] = $item->branch_id;
        $result['branch_name'] = $item->branch_name;
        $result['agreement_no'] = $item->agreement_no;
        $result['po_no'] = $item->po_no;
        $result['po_date'] = $item->po_date;
        $result['contract_status'] = $item->contract_status;
        $result['asset_code'] = $item->asset_code;
        $result['chassis_number'] = $item->chassis_number;
        $result['machine_number'] = $item->machine_number;
        $result['license_plate'] = $item->license_plate;
        $result['owner_asset'] = $item->owner_asset;
        $result['manufacturing_year'] = $item->manufacturing_year;
        $result['bpkb_no'] = $item->bpkb_no;
        $result['asset_color'] = $item->asset_color;
        $result['total_otr'] = $item->total_otr;
        $result['down_payment'] = $item->down_payment;
        $result['admin_fee'] = $item->admin_fee;
        $result['fiducia_fee'] = $item->fiducia_fee;
        $result['stamp_fee'] = $item->stamp_fee;
        $result['product_offering_fee'] = $item->product_offering_fee;
        $result['customer_name'] = $item->customer_name;
        $result['customer_address'] = $item->customer_address;
        $result['customer_rt'] = $item->customer_rt;
        $result['customer_rw'] = $item->customer_rw;
        $result['customer_city'] = $item->customer_city;
        $result['customer_phone_number'] = $item->customer_phone_number;
        $result['customer_account_bank'] = $item->customer_account_bank;
        $result['customer_account_number'] = $item->customer_account_number;
        $result['cashback'] = $item->cashback;
        $result['stnk_date'] = $item->stnk_date;
        $result['stnk_fee'] = $item->stnk_fee;
        return $result;
    }

    function list($items)
    {
        $result = [];
        foreach ($items as $item) {
            $result[] = $this->single($item);
        }
        return $result;
    }}
