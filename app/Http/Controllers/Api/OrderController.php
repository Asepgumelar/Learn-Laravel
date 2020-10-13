<?php

namespace App\Http\Controllers\Api;

use App\Libraries\Api\ResponseLibrary;
use App\Libraries\WaveCellLibrary;
use App\Http\Controllers\Controller;
use App\Mappers\Order\OrderMapper;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Partner;
use App\Models\TransactionCallback;
use App\Models\User;
use App\Response\BaseResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    private $baseResponse;
    private $orderMapper;

    public function __construct()
    {
        parent::__construct();
        $this->responseLib = new ResponseLibrary();
        $this->baseResponse = new BaseResponse();
        $this->orderMapper = new OrderMapper();
    }

    public function store(Request $request)
    {
        $inputAll = $request->all();
        DB::transaction(function () use ($inputAll) {
            DB::table('data_logs')->insert([
                'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'log_text' => json_encode($inputAll),
                'log_type' => 'input',
                'module' => 'order-api',
                'method' => 'store',
                'created_at' => now()
            ]);
        });
        try {
            DB::beginTransaction();
            $contractStatus = $request->contract_status ? $request->contract_status : 'ERP';
            $adminpsa_fee = 450000;
            $data = Order::query()->create([
                'branch_id' => $request->branch_id,
                'branch_name' => $request->branch_name,
                'agreement_no' => $request->agreement_no,
                'po_no' => $request->po_no,
                'po_date' => $request->po_date,
                'contract_status' => $contractStatus,
                'asset_code' => $request->asset_code,
                'chassis_number' => $request->chassis_number,
                'machine_number' => $request->machine_number,
                'license_plate' => $request->license_plate,
                'owner_asset' => $request->owner_asset,
                'manufacturing_year' => $request->manufacturing_year,
                'bpkb_no' => $request->bpkb_no,
                'asset_color' => $request->asset_color,
                'total_otr' => $request->total_otr,
                'down_payment' => $request->down_payment,
                'admin_fee' => $request->admin_fee,
                'fiducia_fee' => $request->fiducia_fee,
                'stamp_fee' => $request->stamp_fee,
                'product_offering_fee' => $request->product_offering_fee,
                'customer_name' => $request->customer_name,
                'customer_address' => $request->customer_address,
                'customer_rt' => $request->customer_rt,
                'customer_rw' => $request->customer_rw,
                'customer_city' => $request->customer_city,
                'customer_phone_number' => $request->customer_phone_number,
                'customer_account_bank' => $request->customer_account_bank,
                'customer_account_number' => $request->customer_account_number,
                'cashback' => $request->cashback,
                'adminpsa_fee' => $adminpsa_fee,
                'stnk_date' => $request->stnk_date,
                'stnk_fee' => $request->stnk_fee
            ]);
            DB::commit();


            $partner = Partner::where('code', $data->branch_id)->first();

            $phoneNumber = User::select('phone_number')->whereHas('branches', function ($query) use ($partner) {
                $query->whereIn('branch_id', $partner->branchs()->get()->pluck('id')->toArray());
            })->whereHas('roles', function ($query) {
                $query->where('slug', 'cabang');
            })->get();


            if(env('APP_ENV') == "production"){
                foreach ($phoneNumber as $p)
                {
                    if($p->phone_number != null)
                    {
                        $this->SMS($request->po_no,$p->phone_number);
                    }
                }
            }
            return response($this->baseResponse->single($data, $this->orderMapper), JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            DB::transaction(function () use ($e) {
                DB::table('data_logs')->insert([
                    'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                    'log_text' => json_encode($e->getMessage()),
                    'log_type' => 'error',
                    'module' => 'order-api',
                    'method' => 'store',
                    'created_at' => now()
                ]);
            });
            return response($this->baseResponse->error($e), JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function callbackSendInvoice(Request $request)
    {

        $inputAll = $request->all();
        DB::transaction(function () use ($inputAll) {
            DB::table('data_logs')->insert([
                'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'log_text' => json_encode($inputAll),
                'log_type' => 'input',
                'module' => 'order-api',
                'method' => 'callbackSendInvoice',
                'created_at' => now()
            ]);
        });

        try {
            DB::beginTransaction();
            $validator = Validator::make($inputAll, [
                'po_no' => 'required',
                'status' => 'in:completed,pending,failed',
            ]);

            if ($validator->fails()) {
                return response($this->baseResponse->validationFail($validator->errors()->all()), JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }
            $data = Order::query()->where('po_no', '=', $request->po_no)->first();
            if (!$data) {
                throw new \Exception("Order with po no " . $request->po_no . ' is not found');
            }
            TransactionCallback::query()->create([
                'po_no' => $request->po_no,
                'order_id' => $data->id,
                'status' => $request->status,
            ]);
            if (strtolower($request->status) == 'completed') {
                $data->order_status = 'done';
                $data->save();
            }
            DB::commit();


            return response(array('message' => 'Callback has successfully send'), JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            DB::transaction(function () use ($e) {
                DB::table('data_logs')->insert([
                    'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                    'log_text' => json_encode($e->getMessage()),
                    'log_type' => 'error',
                    'module' => 'order-api',
                    'method' => 'callbackSendInvoice',
                    'created_at' => now()
                ]);
            });
            return response(array('message' => 'Callback has failed send', 'error' => $e->getMessage()),
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function SMS($PO,$No)
    {
        $waveCellLib = new WaveCellLibrary();

        $appName = env('APP_NAME', 'PSA');
        $contentSms = "$appName - PO Baru $PO telah Masuk";
        $waveCellLib->sendSingleSms($No, $contentSms);


        /*if ($response['code'] !== 200) {
            return response($this->responseLib
                ->customFailResponse(400, $response['errors']), 400);
        } else {
            return $this->responseLib->createResponse(
                $response['code'], array(), 'Notification send Succesfull');
        }*/
    }

}
