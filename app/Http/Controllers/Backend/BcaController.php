<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\BaseBackendController;
use App\Models\Disbursement;
use Illuminate\Http\Request;
use App\Services\BcaService;
use App\Response\BaseResponse;
use Exception;
use Illuminate\Support\Facades\Log;

class BcaController extends BaseBackendController
{

    public function __construct()
    {
        parent::__construct();
        $this->menu = "Bca";
        $this->route = $this->routes['backend'] . 'bca';
        $this->slug = $this->slugs['backend'] . 'bca';
        $this->view = $this->views['backend'] . 'bca';
        $this->breadcrumb = '<li><a href="' . route($this->route . '.index') . '">' . $this->menu . '</a></li>';
        $this->disbursementService = new BcaService();
        $this->baseResponse = new BaseResponse();
        $this->share();
    }

    public function index()
    {
        try {
            $breadcrumb = $this->breadcrumbs($this->breadcrumb . '<li class="active">' . 'List Bca' . '</li>');
            return view($this->view . '.index', compact('breadcrumb'));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            abort(500);
        }
    }

    public function getDatatable(Request $request)
    {
        try {
            $draw               = $request->input('draw');
            $start              = $request->input('start');
            $length             = $request->input('length');
            $page               = (int)$start > 0 ? ($start / $length) + 1 : 1;
            $limit              = (int)$length > 0 ? $length : 10;
            $columnIndex        = $request->input('order')[0]['column'];              // Column index
            $columnName         = $request->input('columns')[$columnIndex]['data'];   // Column name
            $columnSortOrder    = $request->input('order')[0]['dir'];                 // asc or desc
            $searchValue        = $request->input('search')['value'];                 // Search value
            $disbursementStatus = $request->query('columns')[8]['search']['value'];;
            $externalId         = $request->query('columns')[2]['search']['value'];;
            $createdAt          = $request->query('columns')[9]['search']['value'];;

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

            $items = [];
            foreach ($paginate->items() as $idx => $row) {
                $action = null;
                $items[] = [
                    "id"                  => $row['id'],
                    'po_no'               => $row->order->po_no,
                    'po_date'             => $row->order->po_date,
                    'external_id'         => $row->external_id,
                    'bank_code'           => $row->bank ? $row->bank->name : '',
                    'account_holder_name' => $row->account_holder_name,
                    'account_number'      => $row->account_number,
                    'amount'              => $row->amount,
                    'status'              => $row->status == '' || $row->status == null ? 'NOT_TRANSFER' : $row->status,
                    'created_at'          => date("Y-m-d H:i:s",strtotime($row->created_at)),
                    "action"              => $action,
                ];
            }
            $response = [
                "draw" => (int)$draw,
                "recordsTotal" => (int)$countAll,
                "recordsFiltered" => (int)$paginate->total(),
                "data" => $items
            ];
            return response()->json($response);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            abort(500);
        }
    }
}
