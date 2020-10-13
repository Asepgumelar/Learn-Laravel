<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\BaseBackendController;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Uuid;

class PartnerController extends BaseBackendController
{
    public function __construct()
    {
        parent::__construct();
        $this->menu = 'Branch KP';
        $this->route = $this->routes['backend'] . 'partners';
        $this->slug = $this->slugs['backend'] . 'partners';
        $this->view = $this->views['backend'] . 'partner';
        $this->breadcrumb = '<li><a href="' . route($this->route . '.index') . '">' . $this->menu . '</a></li>';

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
            $breadcrumb = $this->breadcrumbs($this->breadcrumb . '<li class="active">' . 'List Branch KP' . '</li>');

            return view($this->view . '.index', compact('breadcrumb'));
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            abort(404);
        }
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

            $conditions = '1 = 1';

            if (!empty($searchValue)) {
                $conditions .= " AND name LIKE '%" . trim($searchValue) . "%'";
                $conditions .= " OR code LIKE '%" . trim($searchValue) . "%'";
            }

            $countAll = Partner::count();
            $paginate = Partner::select('*')
                ->whereRaw($conditions)
                ->orderBy($columnName, $columnSortOrder)
                ->paginate($limit, ["*"], 'page', $page);
            $items = array();

            foreach ($paginate->items() as $idx => $row) {
                $routeDetail = route("backend::partners.show", $row['id']);
                $routeEdit = route("backend::partners.edit", $row['id']);
                $action = null;
                $action .= '<a href="' . $routeDetail . '" style="margin:10px" class="text-light-blue" data-toggle="tooltip" data-placement="bottom" title="Detail"><i class="fa fa-eye"></i></a>';
                $action .= '<a href="' . $routeEdit . '" style="margin:10px" class="text-yellow"  data-toggle="tooltip" data-placement="bottom" title="Edit"><i class="fa fa-edit"></i></a>';
                $action .= '<a href="#" style="margin:10px" class="text-red" onclick="deleteRow(\'' . $row['id'] . '\')" data-toggle="tooltip" data-placement="bottom" title="Delete"><i class="fa fa-trash"></i></a>';

                $items[] = array(
                    "id" => $row['id'],
                    "code" => $row->code,
                    "name" => $row->name,
                    "address" => $row->address,
                    "type" => $row->type,
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
            abort(404);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        try {
            $breadcrumb = $this->breadcrumbs($this->breadcrumb . '<li class="active">' . 'Create Branch KP' . '</li>');

            return view($this->view . '.form.create', compact('breadcrumb'));
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            abort(404);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'code' => 'required',
                'name' => ['required', 'string', 'max:100'],
                'address' => 'required'
            ]);

            if ($validator->fails()) {
                return redirect()
                    ->route($this->route . '.create')
                    ->withErrors($validator)
                    ->withInput();
            }

            $partner = new Partner();
            $partner->code = $request->input('code');
            $partner->name = $request->input('name');
            $partner->address = $request->input('address');
            $partner->save();

            DB::commit();

            return redirect()
                ->route($this->route . '.index')
                ->with('status', 'Branch KP has been added');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            abort(500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Partner $partner
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $breadcrumb = $this->breadcrumbs($this->breadcrumb . '<li class="active">' . 'Detail Branch KP' . '</li>');
            $data = Partner::findOrFail($id);

            return view($this->view . '.show', compact('breadcrumb', 'data'));
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            abort(404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Partner $partner
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        try {
            $breadcrumb = $this->breadcrumbs($this->breadcrumb . '<li class="active">' . 'Edit Branch KP' . '</li>');
            $data = Partner::findOrFail($id);

            return view($this->view . '.form.edit', compact('breadcrumb', 'data'));
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            abort(404);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Partner $partner
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'code' => 'required',
                'name' => ['required', 'string', 'max:100'],
                'address' => 'required'
            ]);

            if ($validator->fails()) {
                return redirect()
                    ->route($this->route . '.edit', $request->get('id'))
                    ->withErrors($validator)
                    ->withInput();
            }

            $partner = Partner::findOrFail($request->get('id'));
            $partner->code = $request->input('code');
            $partner->name = $request->input('name');
            $partner->address = $request->input('address');
            $partner->update();

            DB::commit();

            return redirect()
                ->route($this->route . '.index')
                ->with('status', 'Branch KP has been updated');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            abort(404);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Partner $partner
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request)
    {
        try {
            $partner = Partner::findOrFail($request->id);
            $partner->delete();

            return response()->json(['code' => 200, 'message' => 'ok'], 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['code' => 400, 'message' => 'error' . $e->getMessage()], 400);
        }
    }
}
