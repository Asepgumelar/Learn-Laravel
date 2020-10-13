<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\BaseBackendController;
use App\Models\Permission;
use App\Models\Role;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Uuid;

class RoleController extends BaseBackendController
{
    public function __construct()
    {
        parent::__construct();
        $this->menu = 'Roles';
        $this->route = $this->routes['backend'] . 'roles';
        $this->slug = $this->slugs['backend'] . 'roles';
        $this->view = $this->views['backend'] . 'role';
        $this->breadcrumb = '<li><a href="' . route($this->route . '.index') . '">' . $this->menu . '</a></li>';
        # share parameters
        $this->share();
    }

    public function index()
    {
        try {
            $breadcrumb = $this->breadcrumbs($this->breadcrumb . '<li class="active">' . 'List Roles' . '</li>');
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
            $conditions = '1 = 1';
            if (!empty($searchValue)) {
                $conditions .= " AND role_name LIKE '%" . trim($searchValue) . "%'";
                $conditions .= " OR slug LIKE '%" . trim($searchValue) . "%'";
            }

            $countAll = Role::count();
            $paginate = Role::select('*')
                ->whereRaw($conditions)
                ->orderBy($columnName, $columnSortOrder)
                ->paginate($limit, ["*"], 'page', $page);
            $items = array();
            foreach ($paginate->items() as $idx => $row) {
                $action = null;
                $routeDetail = route("backend::roles.show", $row['id']);
                $routeEdit = route("backend::roles.showEdit", $row['id']);
                $action .= '<a href="' . $routeDetail . '" style="margin:10px" class="text-light-blue" data-toggle="tooltip" data-placement="bottom" title="Detail"><i class="fa fa-eye"></i></a>';
                $action .= '<a href="' . $routeEdit . '" style="margin:10px" class="text-yellow"  data-toggle="tooltip" data-placement="bottom" title="Edit"><i class="fa fa-edit"></i></a>';
                $action .= '<a href="#" style="margin:10px" class="text-red" onclick="deleteRow(\'' . $row['id'] . '\')" data-toggle="tooltip" data-placement="bottom" title="Delete"><i class="fa fa-trash"></i></a>';
                $items[] = array(
                    "id" => $row['id'],
                    "slug" => $row['slug'],
                    "role_name" => $row->role_name,
                    "description" => $row->description,
                    "permissions" => $row->permissions->pluck('name')->implode(','),
                    "is_active" => $row->is_active,
                    "is_default" => $row->is_default,
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

    public function showCreate()
    {
        try {
            $breadcrumb = $this->breadcrumbs($this->breadcrumb . '<li class="active">' . 'Create Role' . '</li>');
            $permissions = Permission::select('*')->get();
            return view($this->view . '.form.create', compact('breadcrumb', 'permissions'));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            abort(500);
        }
    }

    public function show($id)
    {
        try {
            $breadcrumb = $this->breadcrumbs($this->breadcrumb . '<li class="active">' . 'Detail Role' . '</li>');
            $data = Role::findOrFail($id);
            $permissions = Permission::select('*')->get();


            $permissionSelected = $data->permissions()->get();

            $listSelectedPermission = array();
            foreach ($permissionSelected as $g) {
                $listSelectedPermission[$g->id] = $g->id;
            }


            return view($this->view . '.form.show', compact('breadcrumb', 'data', 'permissions', 'permissionSelected', 'listSelectedPermission'));
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            abort(404);
        }
    }

    public function showEdit($id)
    {
        try {
            $breadcrumb = $this->breadcrumbs($this->breadcrumb . '<li class="active">' . 'Edit Role' . '</li>');
            $data = Role::findOrFail($id);

            $permissions = Permission::select('*')->get();

            $permissionSelected = $data->permissions()->get();

            $listSelectedPermission = array();
            foreach ($permissionSelected as $g) {
                $listSelectedPermission[$g->id] = $g->id;
            }

            return view($this->view . '.form.edit', compact('breadcrumb',
                    'data',
                    'permissions',
                    'listSelectedPermission')
            );
        } catch (Exception $e) {
            Log::error($e->getMessage());
            abort(500);
        }
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'slug' => ['required', 'string', 'max:150'],
                'role_name' => ['required', 'string'],
                'description' => 'required',
                'permissions' => 'required'
            ]);

            if ($validator->fails()) {
                return redirect()
                    ->route($this->route . '.showCreate')
                    ->withErrors($validator)
                    ->withInput();
            }

            $data = new Role();

            $data->slug = $request->input('slug');
            $data->role_name = $request->input('role_name');
            $data->description = $request->input('description');
            $data->is_active = 1;
            $data->is_default = 0;
            $data->save();

            foreach ($request->get('permissions') as $permission) {
                $permissionModel = Permission::where('id', $permission)->first();
                $data->permissions()->attach($permissionModel->id, ['id' => Uuid::getFactory()->uuid4()->toString()]);
            }

            DB::commit();
            return redirect()
                ->route($this->route . '.index')
                ->with('status', 'Role has been added');

        } catch (Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            abort(500);
        }
    }

    public function update(Request $request, $id)
    {

        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'slug' => ['required', 'string', 'max:150'],
                'role_name' => ['required', 'string'],
                'description' => 'required',
                'permissions' => 'required'
            ]);

            if ($validator->fails()) {
                return redirect()
                    ->route($this->route . '.showEdit', $id)
                    ->withErrors($validator)
                    ->withInput();
            }


            $data = Role::findOrFail($id);

            $data->slug = $request->input('slug');
            $data->role_name = $request->input('role_name');
            $data->description = $request->input('description');
            $data->is_active = 1;
            $data->is_default = 0;
            $data->save();


            $syncPermissions = null;
            foreach ($request->permissions as $permission) {
                $permissionModel = Permission::where('id', $permission)->first();
                if ($permissionModel) {
                    $tempExtra = [];
                    $tempExtra['id'] = Uuid::getFactory()->uuid4()->toString();
                    $tempExtra['created_at'] = date('Y-m-d');
                    $tempExtra['updated_at'] = date('Y-m-d');
                    $syncPermissions[$permissionModel->id] = $tempExtra;
                }
            }

            $data->permissions()->sync($syncPermissions);

            DB::commit();
            return redirect()
                ->route($this->route . '.index')
                ->with('status', 'Role has been updated');

        } catch (Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            abort(500);
        }
    }

    public function delete($id)
    {
        try {
            $data = Role::findOrFail($id);
            $data->delete();

            return response()->json(['code' => 200, 'message' => 'ok'], 200);

        } catch (\Exception $e) {

            Log::error($e->getMessage());
            return response()->json(['code' => 400, 'message' => 'error' . $e->getMessage()], 400);
        }
    }
}
