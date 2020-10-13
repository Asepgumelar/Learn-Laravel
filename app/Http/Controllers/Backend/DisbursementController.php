<?php


namespace App\Http\Controllers\Backend;


use App\Http\Controllers\BaseBackendController;
use App\Models\Bank;
use App\Models\Disbursement;
use App\Models\Order;
use App\Response\BaseResponse;
use App\Services\DisbursementService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DisbursementController extends BaseBackendController
{
    private $disbursementService;
    private $baseResponse;

    public function __construct()
    {
        parent::__construct();
        $this->menu = 'Disbursements';
        $this->route = $this->routes['backend'] . 'disbursements';
        $this->slug = $this->slugs['backend'] . 'disbursements';
        $this->view = $this->views['backend'] . 'disbursement';
        $this->breadcrumb = '<li><a href="' . route($this->route . '.index') . '">' . $this->menu . '</a></li>';
        $this->disbursementService = new DisbursementService();
        $this->baseResponse = new BaseResponse();
        # share parameters
        $this->share();
    }

    public function index()
    {
        try {
            $breadcrumb = $this->breadcrumbs($this->breadcrumb . '<li class="active">' . 'List Disbursement' . '</li>');
            return view($this->view . '.index', compact('breadcrumb'));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            abort(500);
        }
    }

    /**
     * jQuery datatable responses.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function getDatatable(Request $request)
    {
        try {
            $draw = $request->input('draw');
            $start = $request->input('start');
            $length = $request->input('length');
            $page = (int)$start > 0 ? ($start / $length) + 1 : 1;
            $limit = (int)$length > 0 ? $length : 10;
            $columnIndex = $request->input('order')[0]['column']; // Column index
            $columnName = $request->input('columns')[$columnIndex]['data']; // Column name
            $columnSortOrder = $request->input('order')[0]['dir']; // asc or desc
            $searchValue = $request->input('search')['value']; // Search value
            $disbursementStatus = $request->query('columns')[8]['search']['value'];;
            $externalId = $request->query('columns')[2]['search']['value'];;
            $createdAt = $request->query('columns')[9]['search']['value'];;

            $conditions = '1 = 1';
            if (!empty($searchValue)) {
                $conditions .= " AND external_id LIKE '%" . trim($searchValue) . "%'";
                $conditions .= " OR order_id LIKE '%" . trim($searchValue) . "%'";
            }

            $countAll = Disbursement::query()->count();
            $paginate = Disbursement::query();
            if ($disbursementStatus) {
                $paginate = $paginate->where('status', 'LIKE', "%$disbursementStatus%");
            }

            if ($externalId) {
                $paginate = $paginate->where('external_id', 'ILIKE', "%$externalId%");
            }

            if ($createdAt) {
                $explodeCreatedAt = explode(',', $createdAt);
                $paginate = $paginate->whereBetween('created_at', [$explodeCreatedAt[0], $explodeCreatedAt[1]]);
            }

            $paginate = $paginate
                ->whereRaw($conditions)
                ->orderBy($columnName, $columnSortOrder)
                ->paginate($limit, ["*"], 'page', $page);
            $items = array();
            foreach ($paginate->items() as $idx => $row) {
                $action = null;
                $routeDetail = route("backend::disbursements.show", $row['id']);
                $routeDisburse = route("backend::disbursements.showDisburse", $row['id']);
                $action .= '<a href="' . $routeDetail . '" style="margin:10px" class="text-light-blue" data-toggle="tooltip" data-placement="bottom" title="Detail"><i class="fa fa-eye"></i></a>';
                if ($row->status == '' || $row->status == null || $row->status == 'FAILED') {
                    $action .= '<a href="' . $routeDisburse . '" style="margin:10px" class="text-yellow"  data-toggle="tooltip" data-placement="bottom" title="Disburse"><i class="fa fa-edit"></i></a>';
                }
                $items[] = array(
                    "id" => $row['id'],
                    'po_no' => $row->order->po_no,
                    'po_date' => $row->order->po_date,
                    'external_id' => $row->external_id,
                    'bank_code' => $row->bank ? $row->bank->name : '',
                    'account_holder_name' => $row->account_holder_name,
                    'account_number' => $row->account_number,
                    'description' => $row->description,
                    'amount' => $row->amount,
                    'status' => $row->status == '' || $row->status == null ? 'NOT_TRANSFER' : $row->status,
                    'email_to' => $row->email_to,
                    'email_cc' => $row->email_cc,
                    'email_bcc' => $row->email_bcc,
                    'created_at' => date("Y-m-d H:i:s",strtotime($row->created_at)),
                    "action" => $action,
                );
            }
            $response = array(
                "draw" => (int)$draw,
                "recordsTotal" => (int)$countAll,
                "recordsFiltered" => (int)$paginate->total(),
                "data" => $items
            );
            return response()->json($response);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            abort(500);
        }
    }

    public function show($id)
    {
        try {
            $breadcrumb = $this->breadcrumbs($this->breadcrumb . '<li class="active">' . 'Detail Disbursement' . '</li>');
            $data = Disbursement::query()->findOrFail($id);


        } catch (\Exception $e) {
            Log::error($e->getMessage());
            abort(404);
        }
        return view($this->view . '.form.show', compact('breadcrumb', 'data'));
    }

    public function showDisburse($id)
    {

        try {
            $breadcrumb = $this->breadcrumbs($this->breadcrumb . '<li class="active">' . 'Disbursement' . '</li>');
            $data = Disbursement::query()->findOrFail($id);
            $banks = Bank::all();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            abort(404);
        }
        return view($this->view . '.form.disburse', compact('breadcrumb', 'data', 'banks'));
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($request->all(), [
                'order_id' => 'required',
                'po_no' => 'required',
                'account_holder_name' => 'required',
                'account_number' => 'required',
                'amount' => 'required',
            ]);

            if ($validator->fails()) {
                return response($this->baseResponse->validationFail($validator->errors()->all()), JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }
            $check = Disbursement::query()->where('order_id', '=', $request->order_id)
                ->count();
            if ($check > 0) {
                return response(array('message' => 'Data order has been created for disbursement type Customer process'),
                    JsonResponse::HTTP_BAD_REQUEST);
            }
            Disbursement::query()->create([
                'order_id' => $request->order_id,
                'external_id' => 'INV'.date('y').date('m').str_pad($request->po_no,16,'CS',STR_PAD_LEFT),
                'bank_id' => null,
                'account_holder_name' => $request->account_holder_name,
                'account_number' => $request->account_number,
                'description' => null,
                'amount' => $request->amount,
                'status' => null,
            ]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            abort(500);
        }
        return response(array("message" => "Create data disbursement success"),
            JsonResponse::HTTP_CREATED);
    }

    public function disburse(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $data = Disbursement::query()->findOrFail($id);
            if ($data->status == 'FAILED') {
                $validator = Validator::make($request->all(), [
                    'status_disbursement' => 'required',
                    'note' => 'required',
                ]);
                if ($validator->fails()) {
                    return redirect()
                        ->route($this->route . '.disburse', $id)
                        ->withErrors($validator)
                        ->withInput();
                }

                if ($request->status_disbursement == 'PENDING') {
                    return redirect()->route($this->route . '.index')->with('error', 'Cannot update status to PENDING
                    when disbursement process is FAILED');
                }
                $data->note = $request->note;
                $data->status = $request->status_disbursement;
                $data->save();
                DB::commit();
                return redirect()->route($this->route . '.index')->with('status', 'Update status disbursements success');
            }

            $validator = Validator::make($request->all(), [
                'bank_id' => 'required',
                'description' => 'required',
            ]);

            if ($validator->fails()) {
                return redirect()
                    ->route($this->route . '.disburse', $id)
                    ->withErrors($validator)
                    ->withInput();
            }

            $bank = Bank::query()->where('code', '=', $request->bank_id)->first();
            if (!$bank) {
                throw new Exception("Bank not found", 500);
            }
            $params = [
                'external_id' => $data->external_id,
                'bank_code' => $bank->code,
                'account_holder_name' => $data->account_holder_name,
                'account_number' => $data->account_number,
                'description' => $request->description,
                'amount' => $data->amount
            ];

            $createDisbursementReq = $this->disbursementService->createDisbursement($params);
            if ($createDisbursementReq->code != 200) {
                return redirect()->route($this->route . '.index')->with('error', 'Error when create disbursement, '
                    . $createDisbursementReq->body['message']);
            }
            $data->bank_id = $bank->id;
            $data->status = $createDisbursementReq->body['status'];
            $data->description = $request->description;
            $data->save();

            //update flag order is disburse
            $order = Order::query()->findOrFail($data->order_id);

            $order->is_disbursement = true;

            $order->update();

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            abort(500);
        }
        return redirect()->route($this->route . '.index')->with('status', 'Data has been disburse please check status until success');
    }

    public function getBanks(Request $request)
    {
        $items = [];
        try {
            $banks = Bank::query()->where('name', 'ILIKE', '%' . $request->input("term", "") . '%')->get();
            $items = array();
            foreach ($banks as $bank) {
                $items[] = array(
                    "id" => $bank->code,
                    "text" => $bank->name
                );
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            abort(404);
        }
        return response()->json($items);
    }
}
