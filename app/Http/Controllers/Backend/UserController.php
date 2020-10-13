<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\BaseBackendController;
use App\Libraries\ImageLibrary;
use App\Models\Branch;
use App\Models\Image;
use App\Models\Role;
use App\Models\User;
use Exception;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Uuid;

class UserController extends BaseBackendController
{
    public function __construct()
    {
        parent::__construct();
        $this->menu = 'Users';
        $this->route = $this->routes['backend'] . 'users';
        $this->slug = $this->slugs['backend'] . 'users';
        $this->view = $this->views['backend'] . 'user';
        $this->breadcrumb = '<li><a href="' . route($this->route . '.index') . '">' . $this->menu . '</a></li>';
        # share parameters
        $this->share();
    }

    public function index()
    {
        try {
            $breadcrumb = $this->breadcrumbs($this->breadcrumb . '<li class="active">' . 'List Users' . '</li>');
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

    public function loadDataSelect(Request $request)
    {
        $branch = Branch::where('name', 'LIKE', '%' . $request->input('term', '') . '%')
            ->get(['id', 'name as text']);
        return ['results' => $branch];
    }

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
                $conditions .= " AND username LIKE '%" . trim($searchValue) . "%'";
                $conditions .= " OR email LIKE '%" . trim($searchValue) . "%'";
            }

            $countAll = User::count();
            $paginate = User::select('*')
                ->whereRaw($conditions)
                ->orderBy($columnName, $columnSortOrder)
                ->paginate($limit, ["*"], 'page', $page);
            $items = array();
            foreach ($paginate->items() as $idx => $row) {
                $action = null;
                $routeDetail = route("backend::users.edit", $row['id']);
                $action .= '<a href="' . $routeDetail . '" data-toggle="tooltip" data-placement="bottom" title="Edit"><i class="fa fa-edit"></i></a>';
                //$action .= '<a onclick="deleteRow(' . $idx . ')" data-toggle="tooltip" data-placement="right" title="Delete"><input id="delete_' . $idx . '" type="hidden" value="' . $row['id'] . '"><i class="fa fa-trash" style="margin: 10px;color: #ff4d65"></i></a>';
                $items[] = array(
                    "id" => $row['id'],
                    "username" => $row['username'],
                    "email" => $row->email,
                    "phone_number"=>$row->phone_number,
                    "role_name" => $row->roles->pluck('role_name')->implode(','),
                    "branch_name" => $row->branches->pluck('name')->implode(','),
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
            $breadcrumb = $this->breadcrumbs($this->breadcrumb . '<li class="active">' . 'Create User' . '</li>');
            $roles = Role::select('*')->get();
            $branches = Branch::select('*')->get();
            return view($this->view . '.form.create', compact('breadcrumb', 'roles', 'branches'));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            abort(500);
        }
    }

    public function showProfileForm(Request $request)
    {
        try {
            $breadcrumb = $this->breadcrumbs($this->breadcrumb);
            $route = $this->route;
            return view($this->view . '.index', compact('breadcrumb', 'route'));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            abort(500);
        }
    }

    public function showEdit($id)
    {
        try {
            $breadcrumb = $this->breadcrumbs($this->breadcrumb . '<li class="active">' . 'Edit User' . '</li>');
            $data = User::findOrFail($id);

            $roles = Role::select('*')->get();
            $branches = Branch::select('*')->get();

            $roleSelected = $data->roles()->get();
            $branchSelected = $data->branches()->get();

            $listSelectedRole = array();
            foreach ($roleSelected as $g) {
                $listSelectedRole[$g->id] = $g->id;
            }
            $listSelectedBranch = array();
            foreach ($branchSelected as $g) {
                $listSelectedBranch[$g->id] = $g->id;
            }
            return view($this->view . '.profile', compact('breadcrumb',
                    'data',
                    'roles',
                    'listSelectedRole', 'branches', 'listSelectedBranch')
            );
        } catch (Exception $e) {
            Log::error($e->getMessage());
            abort(500);
        }
    }

    public function store(Request $request)
    {
        try {
            if ($request->hidden_id == '') {
                return $this->create($request);
            } else {
                return $this->update($request, $request->input('hidden_id'));
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            abort(500);
        }

    }

    /**
     * Prefix for create or update post data.
     *
     * @param Request $request
     * @return RedirectResponse
     * @throws Exception
     */
    public function create(Request $request)
    {

        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'username' => ['required', 'string', 'max:150'],
                'email' => ['required', 'string', 'email', 'unique:users'],
                'password' => 'required',
                'first_name' => ['required', 'string', 'max:150'],
                'last_name' => ['required', 'string', 'max:150'],
                'roles' => 'required',
                'branches' => 'required',
                'phone_number' => ['required' , 'numeric' , 'digits_between:10,15']
            ]);

            if ($validator->fails()) {
                return redirect()
                    ->route($this->route . '.showCreate')
                    ->withErrors($validator)
                    ->withInput();
            }

            $no = substr($request->input('phone_number'),0,1) == "0" ? "+62".substr($request->input('phone_number'),1) : "+62".$request->input('phone_number') ;
            $user = new User();
            foreach ($request->file() as $key => $file) {
                if ($request->hasFile($key)) {
                    if ($request->file($key)->isValid()) {
                        $imageId = (new ImageLibrary())->saveUserImg($request->file($key), 'images/user',
                            $request->username);
                        $user->avatar = $imageId;
                    }
                } else {
                    $key_id = !empty($request->$key . '_old') ? $request->$key . '_old' : null;
                    $user->$key = $key_id;
                }
            }

            $user->username = $request->input('username');
            $user->email = $request->input('email');
            $user->password = Hash::make($request->input('password'));
            $user->first_name = $request->input('first_name');
            $user->last_name = $request->input('last_name');
            $user->phone_number = $no;
            $user->active = 1;
            $user->save();

            $roleModel = Role::where('id', $request->roles)->first();
            $user->roles()->attach($roleModel->id, ['id' => Uuid::getFactory()->uuid4()->toString()]);

            foreach ($request->get('branches') as $branch) {
                $branchModel = Branch::where('id', $branch)->first();
                $user->branches()->attach($branchModel->id, ['id' => Uuid::getFactory()->uuid4()->toString()]);
            }

            DB::commit();
            return redirect()
                ->route($this->route . '.index')
                ->with('status', 'User has been added');

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
                'username' => 'required',
                'email' => 'required|unique:users,email,' . $id . '|max:255',
                'first_name' => 'required',
                'last_name' => 'required',
                // 'roles' => 'required',
                'branches' => 'required',
                'phone_number' => ['required' , 'numeric' ,'digits_between:10,15']
            ]);

            if ($validator->fails()) {
                return redirect()
                    ->route($this->route . '.edit', $id)
                    ->withErrors($validator)
                    ->withInput();
            }

            $no = substr($request->input('phone_number'),0,1) == "0" ? "+62".substr($request->input('phone_number'),1) : "+62".$request->input('phone_number') ;
            $user = User::findOrFail($id);
            foreach ($request->file() as $key => $file) {
                if ($request->hasFile($key)) {
                    if ($request->file($key)->isValid()) {
                        $imageLib = new ImageLibrary();
                        if ($user->image) {
                            if (Storage::disk('public')->exists($user->image->image_url)) {
                                $imageLib->delete('public', Image::findOrFail($user->avatar));
                            }
                            $imageId = $imageLib->saveUserImg($request->file($key), 'images/user',
                                $request->username);
                            $user->avatar = $imageId;
                        } else {
                            $imageId = $imageLib->saveUserImg($request->file($key), 'images/user',
                                $request->username);
                            $user->avatar = $imageId;
                        }
                    }
                } else {
                    $key_id = !empty($request->$key . '_old') ? $request->$key . '_old' : null;
                    $user->$key = $key_id;
                }
            }

            $user->username = $request->input('username');
            $user->email = $request->input('email');
            $user->first_name = $request->input('first_name');
            $user->last_name = $request->input('last_name');
            $user->phone_number = $no ;
            $user->update();

            // $roleModel = Role::where('id', $request->roles)->first();
            // $syncRoles = null;
            // if ($roleModel) {
            //     $tempExtra = [];
            //     $tempExtra['id'] = Uuid::getFactory()->uuid4()->toString();
            //     $tempExtra['created_at'] = date('Y-m-d');
            //     $tempExtra['updated_at'] = date('Y-m-d');
            //     $syncRoles[$roleModel->id] = $tempExtra;
            // }
            // $user->roles()->sync($syncRoles);

            $syncBranches = null;
            foreach ($request->branches as $branch) {
                $branchModel = Branch::where('id', $branch)->first();
                if ($branchModel) {
                    $tempExtra = [];
                    $tempExtra['id'] = Uuid::getFactory()->uuid4()->toString();
                    $tempExtra['created_at'] = date('Y-m-d');
                    $tempExtra['updated_at'] = date('Y-m-d');
                    $syncBranches[$branchModel->id] = $tempExtra;
                }
            }
            $user->branches()->sync($syncBranches);

            DB::commit();
            return redirect()
                ->route($this->route . '.index')
                ->with('status', 'User has been updated');

        } catch (Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            abort(500);
        }
    }

    public function removeMedia(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $data = User::findOrFail($id);
            $data->avatar = null;
            $data->update();
            $image = Image::findOrFail($request->image_id);
            if ($image->image_url != 'images/avatar/avatar-128x128.png') {
                $imageLib = new ImageLibrary();
                if (Storage::disk('public')->exists($image->image_url)) {
                    $imageLib->delete('public', $image);
                }
                $image->delete();
            }
            DB::commit();
            return response()->json(['message' => 'ok'], 200);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function updatePassword(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'existing_password' => 'required',
                'new_password' => 'required|min:8|required_with:password_confirmation|same:password_confirmation',
            ]);

            if ($validator->fails()) {
                return redirect()
                    ->route($this->route . '.edit', $request->get('hidden_id'))
                    ->withErrors($validator)
                    ->withInput();
            }

            $user = User::findOrFail($request->get('hidden_id'));

            if (Hash::check($request->get('existing_password'), $user->password)) {
                $user->password = Hash::make($request->input('new_password'));
                $user->update();

                DB::commit();

                return redirect()
                    ->route($this->route . '.edit', $request->get('hidden_id'))
                    ->with('success', 'Password changed successfully!');
            } else {
                return redirect()
                    ->route($this->route . '.edit', $request->get('hidden_id'))
                    ->with('failed', 'Current password is incorrect')
                    ->withErrors($validator)
                    ->withInput();
            }

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }

    }

    public function getBranches(Request $request)
    {
        try {
            $branches = Branch::where('name', 'LIKE', '%' . $request->input("term", "") . '%')->get();
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
