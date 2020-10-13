<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\BaseBackendController;
use App\Mail\sendMail;
use App\Models\Disbursement;
use App\Models\Order;
use App\Models\Partner;
use App\Response\BaseResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use Illuminate\Support\Facades\Validator;
use Unirest\Request\Body;

class OrderController extends BaseBackendController
{
    private $baseResponse;

    public function __construct()
    {
        parent:: __construct();
        $this->menu = 'Orders';
        $this->route = $this->routes['backend'] . 'orders';
        $this->slug = $this->slugs['backend'] . 'orders';
        $this->view = $this->views['backend'] . 'order';
        $this->breadcrumb = '<li><a href="' . route($this->route . '.index') . '">' . $this->menu . '</a></li>';
        $this->baseResponse = new BaseResponse();

        $this->share();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $breadcrumb = $this->breadcrumbs($this->breadcrumb . '<li class="active">' . 'List Orders' . '</li>');

            $contractStatus = Order::select('contract_status')
                ->distinct()
                ->get();

            $orderStatus = Order::select('order_status')
                ->distinct()
                ->get();

            return view($this->view . '.index',
                compact(
                    'breadcrumb', 'contractStatus', 'orderStatus'
                ));
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            abort(404);
        }
    }

    public function getRoleUser()
    {
        return auth()->user()->user_role->role->slug;
    }

    public function getDatatable(Request $request)
    {
        try {
            $draw = $request->input('draw');
            $start = $request->input('start');
            $length = $request->input('length');
            $page = (int)$start > 0 ? ($start / $length) + 1 : 1;
            $limit = (int)$length > 0 ? $length : 10;
            $columnIndex = $request->input('order')[0]['column'];
            $columnName = $request->input('columns')[$columnIndex]['data'];
            $columnSortOrder = $request->input('order')[0]['dir'];
            $searchValue = $request->input('search')['value'];
            $contractStatus = $request->query('columns')[10]['search']['value'];
            $orderStatus = $request->query('columns')[11]['search']['value'];
            $poDate = $request->query('columns')[2]['search']['value'];
            $noPO = $request->query('columns')[1]['search']['value'];
            $customerName = $request->query('columns')[8]['search']['value'];

            $conditions = '1 = 1';

            if (!empty($searchValue)) {
                $conditions .= " AND po_no LIKE '%" . trim($searchValue) . "%'";
            }

            $countAll = Order::count();

            $paginate = Order::select('*');

            if ($this->getRoleUser() == "cabang") {
                $paginate = $paginate->whereHas('partner.branchs', function ($q) {
                    $q->where('branch_id', auth()->user()->user_branch->branch_id);
                })
                    ->where(function ($q) {
                        $q->where('order_status', 'open');
                    });
            }
            if ($this->getRoleUser() == "ho") {
                $paginate = $paginate->whereHas('partner.branchs', function ($q) {
                    $q->where('branch_id', auth()->user()->user_branch->branch_id);
                })
                    ->where(function ($q) {
                        $q->where('order_status', 'checked')
                            ->orWhere('order_status', 'done')
                            ->orWhere('order_status', 'golive');
                    });
            }

            if ($orderStatus) {
                $paginate = $paginate->where('order_status', 'LIKE', "%$orderStatus%");
            }

            if ($noPO) {
                $paginate = $paginate->where('po_no', 'LIKE', "%$noPO%");
            }

            if ($customerName) {
                $paginate = $paginate->where('customer_name', 'LIKE', "%$customerName%");
            }

            if ($contractStatus) {
                $paginate = $paginate->where('contract_status', 'LIKE', "%$contractStatus%");
            }

            if ($poDate) {
                $explodePoDate = explode(',', $poDate);
                $paginate = $paginate->whereBetween('po_date', [$explodePoDate[0], $explodePoDate[1]]);
            }

            $paginate = $paginate->whereRaw($conditions)
                ->orderBy($columnName, $columnSortOrder)
                ->paginate($limit, ["*"], 'page', $page);
            $items = array();

            foreach ($paginate->items() as $idx => $row) {

                $routeDetail = route("backend::orders.show", $row['id']);
                $action = null;
                $action .= '<a href="' . $routeDetail . '" style="margin:10px" class="text-light-blue" data-toggle="tooltip" data-placement="bottom" title="Detail"><i class="fa fa-eye"></i></a>';

                $items[] = array(
                    "id" => $row['id'],
                    "po_no" => $row->po_no,
                    "po_date" => $row->po_date,
                    "contract_status" => $row->contract_status,
                    "asset_code" => $row->asset_code,
                    "license_plate" => $row->license_plate,
                    "owner_asset" => $row->owner_asset,
                    "branch_name" => $row->branch_name,
                    "asset_color" => $row->asset_color,
                    "customer_name" => $row->customer_name,
                    "customer_address" => $row->customer_address,
                    "customer_rt" => $row->customer_rt,
                    "customer_rw" => $row->customer_rw,
                    "customer_city" => $row->customer_city,
                    "order_status" => $row->order_status,
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

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            abort(500);
        }
    }

    public function show($id)
    {
        try {
            $breadcrumb = $this->breadcrumbs($this->breadcrumb . '<li class="active">' . 'Detail Order' . '</li>');
            $data = Order::findOrFail($id);

            $role = $this->getRoleUser();

            $getStatusDisbursment = Disbursement::where('order_id', $id)->first();

            $dp = $this->terbilang($data->down_payment + $data->admin_fee);
            $amount = $this->terbilang($data->total_otr - $data->down_payment - $data->admin_fee - $data->adminpsa_fee);
            $buktipenerimaan = $this->terbilang($data->total_otr - $data->adminpsa_fee);
            $totalotr = $this->terbilang($data->total_otr - $data->down_payment + $data->cashcback - $data->fiducia_fee - $data->admin_fee);
            $totalpem = $this->terbilang($data->fiducia_fee + $data->stamp_fee);
            $vowels = array("a", "e", "i", "o", "u", "A", "E", "I", "O", "U");
            $initial = str_replace($vowels, "", $data->branch_name);

            PDF::loadView($this->view . '.pdf.suratjalan', compact('data', 'initial'))
                ->setOption('page-width', '320.0')
                ->setOption('page-height', '180.0')
                ->save(storage_path('app/public/pdf/suratjalan_' . $data->id . '.pdf'), true);

            PDF::loadView($this->view . '.pdf.kwitansidp', compact('data', 'dp', 'initial'))
                ->setOption('page-width', '320.0')
                ->setOption('page-height', '180.0')
                ->save(storage_path('app/public/pdf/kwitansidp_' . $data->id . '.pdf'), true);

            PDF::loadView($this->view . '.pdf.kwitansipelunasan', compact('data', 'totalotr', 'initial'))
                ->setOption('page-width', '320.0')
                ->setOption('page-height', '180.0')
                ->save(storage_path('app/public/pdf/kwitansipelunasan_' . $data->id . '.pdf'), true);

            PDF::loadView($this->view . '.pdf.kwitansijualbeli', compact('data', 'buktipenerimaan', 'initial'))
                ->setOption('page-width', '320.0')
                ->setOption('page-height', '180.0')
                ->save(storage_path('app/public/pdf/kwitansijualbeli_' . $data->id . '.pdf'), true);

            PDF::loadView($this->view . '.pdf.buktipembayaran', compact('data', 'amount', 'initial'))
                ->setOption('page-width', '320.0')
                ->setOption('page-height', '180.0')
                ->save(storage_path('app/public/pdf/buktipembayaran_' . $data->id . '.pdf'), true);

            PDF::loadView($this->view . '.pdf.taksasi', compact('data', 'initial'))
                ->setOption('page-width', '320.0')
                ->setOption('page-height', '180.0')
                ->save(storage_path('app/public/pdf/taksasi_' . $data->id . '.pdf'), true);

            PDF::loadView($this->view . '.pdf.kwitansipembayaran', compact('data', 'totalpem', 'initial'))
                ->setOption('page-width', '320.0')
                ->setOption('page-height', '180.0')
                ->save(storage_path('app/public/pdf/kwitansipembayaran_' . $data->id . '.pdf'), true);

            PDF::loadView($this->view . '.pdf.po', compact('data', 'totalpem', 'initial'))
                ->setOption('page-width', '320.0')
                ->setOption('page-height', '200.0')
                ->save(storage_path('app/public/pdf/po_' . $data->id . '.pdf'), true);

            return view($this->view . '.show', compact('breadcrumb', 'data', 'role', 'getStatusDisbursment'));
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            abort(500);
        }
    }

    public function update(Request $request, $id)
    {
        DB:: beginTransaction();
        try {
            $order = Order::findOrFail($id);
            $order->order_status = $request->input('status');

            $order->update();
            DB:: commit();

            return redirect()
                ->route($this->route . '.index')
                ->with('status', 'Status Has Updated');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            abort(500);
        }
    }

    // Function untuk Mengirim email
//    public function send(Request $request)
//    {
//        try {
//            $files = [
//                'pdf1' => $request->pdf1,
//                'pdf2' => $request->pdf2,
//                'pdf3' => $request->pdf3
//            ];
//            Mail::to('user@mail.com')->queue(new sendMail($files));
//            Log::info($files);
//            return response()->json(['code' => 200, 'message' => 'ok'], 200);
//        } catch (\Exception $e) {
//            Log:: error($e->getMessage());
//            Log:: info($files);
//            return response()->json(['code' => 400, 'message' => 'error' . $e->getMessage()], 400);
//        }
//    }

    public function penyebut($nilai)
    {
        $nilai = abs($nilai);
        $huruf = array("", "SATU", "DUA", "TIGA", "EMPAT", "LIMA", "ENAM", "TUJUH", "DELAPAN", "SEMBILAN", "SEPULUH", "SEBELAS");
        $temp = "";
        if ($nilai < 12) {
            $temp = " " . $huruf[$nilai];
        } else if ($nilai < 20) {
            $temp = $this->penyebut($nilai - 10) . " BELAS";
        } else if ($nilai < 100) {
            $temp = $this->penyebut($nilai / 10) . " PULUH" . $this->penyebut($nilai % 10);
        } else if ($nilai < 200) {
            $temp = " SERATUS" . $this->penyebut($nilai - 100);
        } else if ($nilai < 1000) {
            $temp = $this->penyebut($nilai / 100) . " RATUS" . $this->penyebut($nilai % 100);
        } else if ($nilai < 2000) {
            $temp = " SERIBU" . $this->penyebut($nilai - 1000);
        } else if ($nilai < 1000000) {
            $temp = $this->penyebut($nilai / 1000) . " RIBU" . $this->penyebut($nilai % 1000);
        } else if ($nilai < 1000000000) {
            $temp = $this->penyebut($nilai / 1000000) . " JUTA" . $this->penyebut($nilai % 1000000);
        } else if ($nilai < 1000000000000) {
            $temp = $this->penyebut($nilai / 1000000000) . " MILYAR" . $this->penyebut(fmod($nilai, 1000000000));
        } else if ($nilai < 1000000000000000) {
            $temp = $this->penyebut($nilai / 1000000000000) . " TRILIYUN" . $this->penyebut(fmod($nilai, 1000000000000));
        }
        return $temp;
    }

    public function terbilang($nilai)
    {
        if ($nilai < 0) {
            $hasil = "MINUS " . trim($this->penyebut($nilai)) . " RUPIAH";
        } else {
            $hasil = trim($this->penyebut($nilai)) . " RUPIAH";
        }
        return $hasil;
    }

    public function sendInvoice(Request $request)
    {

        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'order_id' => 'required',
            ]);

            if ($validator->fails()) {
                return response($this->baseResponse->validationFail($validator->errors()->all()), JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            $order = Order::query()->findOrFail($request->order_id);

            if ($order->is_disbursement == false || $order->disbursement->status != 'COMPLETED') {
                return response(array('message' => 'If you want to send the invoice, you must complete the disbursement process until status is completed'),
                    JsonResponse::HTTP_BAD_REQUEST);
            }
            if ($order->is_send_invoice == true) {
                return response(array('message' => 'Invoice Has been Send , Cannot Send Invoice'),
                    JsonResponse::HTTP_BAD_REQUEST);
            }
            $headers = [
                'Authorization' => env("KP_TOKEN_AUTH"),
                'Accept' => 'application/json'
            ];
            $params = [
                'po_no' => $order->po_no,
                'invoice_no' => $order->disbursement ? $order->disbursement->external_id : '',
                'invoice_amount' => $order->disbursement ? $order->disbursement->amount : '',
                'invoice_date' => $order->disbursement ? date('Y-m-d H:i:s', strtotime($order->disbursement->created_at)) : ''
            ];
            $body = Body::Form($params);
            $response = \Unirest\Request::post(env("KP_SEND_INVOICE_URL"), $headers, $body);

            Log::info("RESPONSE SEND INVOICE " . json_encode($response));
            if ($response->code != 200) {
                return response(array('message' => 'Send data invoice to KP Failed'), JsonResponse::HTTP_BAD_REQUEST);
            }
            //update data order status menjadi golive
            $order->order_status = 'golive';
            $order->is_send_invoice = true;
            $order->save();

            DB::commit();
            return response(array('message' => 'Send data invoice to KP Success'), JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            return response(array('message' => 'Send data invoice to KP Error', 'error' => $e->getMessage()),
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
