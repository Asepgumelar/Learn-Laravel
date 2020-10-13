<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\BaseBackendController;
use App\Models\BranchPartner;
use App\Models\Partner;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Uuid;

class BranchPartnerController extends BaseBackendController
{
    public function __construct()
    {
        parent::__construct();
        $this->menu = 'Branch PSA - KP';
        $this->route = $this->routes['backend'] . 'branch_partners';
        $this->slug = $this->slugs['backend'] . 'branch_partners';
        $this->view = $this->views['backend'] . 'branch_partner';
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
            $breadcrumb = $this->breadcrumbs($this->breadcrumb . '<li class="active">' . 'List Branch' . '</li>');

            return view($this->view . '.index', compact('breadcrumb'));
        }

        catch (\Exception $e) {
            Log::error($e->getMessage());
            abort(404);
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
            $columnIndex        = $request->input('order')[0]['column'];
            $columnName         = $request->input('columns')[$columnIndex]['data'];
            $columnSortOrder    = $request->input('order')[0]['dir'];
            $searchValue        = $request->input('search')['value'];

            $conditions         = '1 = 1';

            if (!empty($searchValue))
            {
                $conditions    .= " AND name LIKE '%" . trim($searchValue) . "%'";
            }

            $countAll           = Branch::count();
            $paginate           = Branch::select('*')
                                        ->whereRaw($conditions)
                                        ->orderBy($columnName, $columnSortOrder)
                                        ->paginate($limit, ["*"], 'page', $page);
            $items              = array();

            foreach ($paginate->items() as $idx => $row)
            {
                $routeEdit      = route("backend::branch_partners.edit", $row['id']);
                $action         = null;
                // $action         = '<input type="radio" style="cursor:pointer" class="form-check-inpu t" name="id" id="" value="' . $row['id'] . '" onclick="viewRow(\'' . $row->id . '\')">';
                // $action         = '<br><input type="radio" style="cursor:pointer" class="form-check-input" name="id_coba" id="h1ye" value="' . $row['id'] . '">';
                // $action        .= '<a href="' . $routeDetail . '" style="margin:10px" class="text-light-blue" data-toggle="tooltip" data-placement="bottom" title="Detail"><i class="fa fa-eye"></i></a>';
                $action        .= '<a href="' . $routeEdit . '" style="margin:10px" class="text-yellow"  data-toggle="tooltip" data-placement="bottom" title="Edit"><i class="fa fa-edit"></i></a>';
                // $action        .= '<a href="#" style="margin:10px" class="text-red" onclick="deleteRow(\'' . $row['id'] . '\')" data-toggle="tooltip" data-placement="bottom" title="Delete"><i class="fa fa-trash"></i></a>';

                $items[] = array(
                    "id"            => $row['id'],
                    "name"          => $row->name,
                    "radio_button"  => $row['id'],
                    "action"        => $action
                );
            }

            $response = array(
                "draw"              => (int)$draw,
                "recordsTotal"      => (int)$countAll,
                "recordsFiltered"   => (int)$paginate->total(),
                "data"              => $items
            );

            return response()->json($response);

        }

        catch (\Exception $e) {
            Log::error($e->getMessage());
            abort(404);
        }
    }

    public function getDatatablePartner(Request $request)
    {
        try {
            $draw               = $request->input('draw');
            $start              = $request->input('start');
            $length             = $request->input('length');
            $page               = (int)$start > 0 ? ($start / $length) + 1 : 1;
            $limit              = (int)$length > 0 ? $length : 10;
            $columnIndex        = $request->input('order')[0]['column'];
            $columnName         = $request->input('columns')[$columnIndex]['data'];
            $columnSortOrder    = $request->input('order')[0]['dir'];
            $searchValue        = $request->input('search')['value'];
            $idBranch           = $request->query('columns')[0]['search']['value'];;

            $conditions         = '1 = 1';

            if (!empty($searchValue))
            {
                $conditions    .= " AND name LIKE '%" . trim($searchValue) . "%'";
            }

            $countAll           = Partner::count();

            if ($idBranch) {
                $paginate = Partner::select('*')
                                    ->whereHas('branchs', function($query) use ($idBranch) {
                                        $query->where('branch_id', 'LIKE', "%$idBranch%");
                                    })
                                    ->whereRaw($conditions)
                                    ->orderBy($columnName, $columnSortOrder);
            }
            else {
                $paginate = Partner::select('*')
                                    ->whereRaw($conditions)
                                    ->orderBy($columnName, $columnSortOrder);
            }

            $items              = array();
            $paginate = $paginate->paginate($limit, ["*"], 'page', $page);

            foreach ($paginate->items() as $idx => $row)
            {
                $routeDetail    = route("backend::branch.show", $row['id']);
                $routeEdit      = route("backend::branch.edit", $row['id']);
                $action         = null;
                // $action        .= '<a href="' . $routeDetail . '" style="margin:10px" class="text-light-blue" data-toggle="tooltip" data-placement="bottom" title="Detail"><i class="fa fa-eye"></i></a>';
                // $action        .= '<a href="' . $routeEdit . '" style="margin:10px" class="text-yellow"  data-toggle="tooltip" data-placement="bottom" title="Edit"><i class="fa fa-edit"></i></a>';
                // $action        .= '<a href="#" style="margin:10px" class="text-red" onclick="deleteRow(\'' . $row['id'] . '\')" data-toggle="tooltip" data-placement="bottom" title="Delete"><i class="fa fa-trash"></i></a>';

                $items[] = array(
                    "id"            => $row['id'],
                    "code"          => $row->code,
                    "name"          => $row->name,
                );
            }

            $response = array(
                "draw"              => (int)$draw,
                "recordsTotal"      => (int)$countAll,
                "recordsFiltered"   => (int)$paginate->total(),
                "data"              => $items
            );
            return response()->json($response);

        }

        catch (\Exception $e) {
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
            $breadcrumb = $this->breadcrumbs($this->breadcrumb . '<li class="active">' . 'Create Branch' . '</li>');
            $branches = Partner::select('*')->get();
            return view($this->view . '.form.create', compact('breadcrumb', 'branches'));
        }

        catch (\Exception $e) {
            Log::error($e->getMessage());
            abort(404);
        }
    }

    public function getDatatableCreate(Request $request)
    {
        try {
            $draw               = $request->input('draw');
            $start              = $request->input('start');
            $length             = $request->input('length');
            $page               = (int)$start > 0 ? ($start / $length) + 1 : 1;
            $limit              = (int)$length > 0 ? $length : 10;
            $columnIndex        = $request->input('order')[0]['column'];
            $columnName         = $request->input('columns')[$columnIndex]['data'];
            $columnSortOrder    = $request->input('order')[0]['dir'];
            $searchValue        = $request->input('search')['value'];

            $conditions         = '1 = 1';

            if (!empty($searchValue))
            {
                $conditions    .= " AND name LIKE '%" . trim($searchValue) . "%'";
            }

            $countAll           = Branch::count();
            $paginate           = Branch::select('*')
                                        ->whereRaw($conditions)
                                        ->orderBy($columnName, $columnSortOrder)
                                        ->paginate($limit, ["*"], 'page', $page);
            $items              = array();

            foreach ($paginate->items() as $idx => $row)
            {
                $routeDetail    = route("backend::branch.show", $row['id']);
                $routeEdit      = route("backend::branch.edit", $row['id']);
                $action         = null;
                // $action         = '<input type="radio" style="cursor:pointer" class="form-check-inpu t" name="id" id="" value="' . $row['id'] . '" onclick="viewRow(\'' . $row->id . '\')">';
                // $action         = '<br><input type="radio" style="cursor:pointer" class="form-check-input" name="id_coba" id="h1ye" value="' . $row['id'] . '">';
                // $action        .= '<a href="' . $routeDetail . '" style="margin:10px" class="text-light-blue" data-toggle="tooltip" data-placement="bottom" title="Detail"><i class="fa fa-eye"></i></a>';
                // $action        .= '<a href="' . $routeEdit . '" style="margin:10px" class="text-yellow"  data-toggle="tooltip" data-placement="bottom" title="Edit"><i class="fa fa-edit"></i></a>';
                // $action        .= '<a href="#" style="margin:10px" class="text-red" onclick="deleteRow(\'' . $row['id'] . '\')" data-toggle="tooltip" data-placement="bottom" title="Delete"><i class="fa fa-trash"></i></a>';

                $items[] = array(
                    "id"            => $row['id'],
                    "name"          => $row->name,
                    "description"   => $row->description,
                    "action"        => $row['id'],
                );
            }

            $response = array(
                "draw"              => (int)$draw,
                "recordsTotal"      => (int)$countAll,
                "recordsFiltered"   => (int)$paginate->total(),
                "data"              => $items
            );

            return response()->json($response);

        }

        catch (\Exception $e) {
            Log::error($e->getMessage());
            abort(404);
        }
    }

    public function getDatatablePartnerCreate(Request $request)
    {
        try {
            $draw               = $request->input('draw');
            $start              = $request->input('start');
            $length             = $request->input('length');
            $page               = (int)$start > 0 ? ($start / $length) + 1 : 1;
            $limit              = (int)$length > 0 ? $length : 10;
            $columnIndex        = $request->input('order')[0]['column'];
            $columnName         = $request->input('columns')[$columnIndex]['data'];
            $columnSortOrder    = $request->input('order')[0]['dir'];
            $searchValue        = $request->input('search')['value'];

            $idBranch           = $request->query('columns')[0]['search']['value'];;

            $conditions         = '1 = 1';

            if (!empty($searchValue))
            {
                $conditions    .= " AND name LIKE '%" . trim($searchValue) . "%'";
            }

            $countAll           = Partner::count();

            $paginate           = Partner::select('*')
                                            ->whereRaw($conditions)
                                            ->orderBy($columnName, $columnSortOrder)
                                            ->paginate($limit, ["*"], 'page', $page);

            $items              = array();

            foreach ($paginate->items() as $idx => $row)
            {
                $getPartnerId = Partner::where('id', $row->id)
                                    ->whereHas('branchs',  function($query) use ($idBranch) {
                                        $query->where('branch_id', 'LIKE', "%$idBranch%");
                                    })->first();

                $items[] = array(
                    "id"            => $row['id'],
                    "code"          => $row->code,
                    "name"          => $row->name,
                    "description"   => $row->description,
                    "action"        => $getPartnerId
                );
            }

            $response = array(
                "draw"              => (int)$draw,
                "recordsTotal"      => (int)$countAll,
                "recordsFiltered"   => (int)$paginate->total(),
                "data"              => $items
            );

            return response()->json($response);
        }

        catch (\Exception $e) {
            Log::error($e->getMessage());
            abort(404);
        }
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'branchID' => 'required',
                'partnerID' => 'required'
            ]);

            if ($validator->fails()) {
                return redirect()
                    ->route($this->route . '.form.edit')
                    ->with('status', 'Anda harus checklist minimal satu partner')
                    ->withErrors($validator)
                    ->withInput();
            }

            $idBranch = $request->branchID;

            $partners = Partner::whereIn('id', $request->partnerID)->get();

            $branchModel = Branch::where('id', $idBranch)->first();

            if ($partners) {
                $branchModel->partners()->detach();

                foreach ($partners as $item) {
                    $branchModel->partners()->attach($item->id, [
                        'id' => Uuid::getFactory()->uuid4()->toString()
                    ]);
                }
            }

            DB::commit();

            return redirect()
                ->route($this->route . '.index')
                ->with('status', 'Branch Partnes has been added');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            abort(500);
        }
    }

    public function showEdit(Request $request, $id)
    {
        try {
            $breadcrumb = $this->breadcrumbs($this->breadcrumb . '<li class="active">' . 'Update Branch PSA - KP' . '</li>');

            $data = Branch::findOrFail($id);

            $partner = Partner::select('*')->get();

            $listPartner = array();

            foreach ($data->partners()->get() as $g)
            {
                $listPartner[$g->id] = $g->id;
            }

            return view($this->view . '.form.edit',
                    compact(
                        'breadcrumb',
                        'partner',
                        'data',
                        'listPartner'
                    ));
        }

        catch (\Exception $e) {
            Log::error($e->getMessage());
            abort(500);
        }
    }
    public function getBranches(Request $request)
    {
        try {
            $branches = Partner::where('name', 'LIKE', '%' . $request->input("term", "") . '%')->get();
            $items = array();
            foreach ($branches as $branch) {
                $items[] = array(
                    "id" => $branch->id,
                    "text" => $branch->name
                );
            }

            return response()->json($items);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            abort(404);
        }
    }
}
