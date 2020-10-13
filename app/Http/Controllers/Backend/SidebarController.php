<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\BaseBackendController;
use App\Models\Menu;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Uuid as Generator;
use Ramsey\Uuid\Uuid;

class SidebarController extends BaseBackendController
{
    private $tempHolder = null;

    public function __construct()
    {
        parent::__construct();
        $this->menu = 'Sidebar';
        $this->route = $this->routes['backend'] . 'sidebars';
        $this->slug = $this->slugs['backend'] . 'sidebars';
        $this->view = $this->views['backend'] . 'sidebar';
        $this->breadcrumb = '<li><a href="' . route($this->route . '.index') . '">' . $this->menu . '</a></li>';
        # share parameters
        $this->share();
    }

    public function index()
    {
        try {
            $rootMenu = Menu::select('*')->where('id', config('psa.DEFAULT_SIDEBAR_ID'))->limit(1)->get();
            $breadcrumb = $this->breadcrumbs($this->breadcrumb . '<li class="active">' . 'Sidebar' . '</li>');
            return view($this->view . '.index', compact(
                'breadcrumb',
                'rootMenu'
            ));
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            abort(500);
        }
        //no-op
    }

    /**
     * Update tree structure  of the menu.
     *
     * @param Request $request
     * @param $id
     * @throws \Exception
     */
    public function tree(Request $request, $id)
    {
        $this->updateTree($id, $request->get('tree'));
    }

    /**
     * @param $id
     * @param $json
     * @throws \Exception
     */
    public function updateTree($id, $json)
    {
        try {
            DB::beginTransaction();
            $tree = json_decode($json, true);
            $this->tempHolder = [];
            $this->getParentChild($id, $tree);
            foreach ($this->tempHolder as $parent => $children) {
                foreach ($children as $key => $val) {
                    try {
                        $model = Menu::findOrFail($val);
                        if ($model) {
                            $model->fill(['parent_id' => $parent, 'menu_order' => $key]);
                            $model->update();
                        }
                    } catch (\Exception $e) {
                        Log::error($e->getMessage());
                        abort(500);
                    }
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return abort(500);
        }
    }

    public function show($id)
    {
        $model = Menu::findOrFail($id);
        $roles = Role::select('*')->get();
        $roleSelected = $model->roles()->get();
        $listSelectedRole = array();
        foreach ($roleSelected as $g) {
            $listSelectedRole[$g->id] = $g->id;
        }
        return view($this->view . '.form.index', compact('model',
                'roles',
                'listSelectedRole')
        );
    }

    public function showChildForm($id)
    {
        $model = Menu::findOrFail($id);
        $roles = Role::select('*')->get();
        $roleSelected = $model->roles()->get();
        $listSelectedRole = array();
        foreach ($roleSelected as $g) {
            $listSelectedRole[$g->id] = $g->id;
        }
        return view($this->view . '.form.parent', compact('model',
                'roles',
                'listSelectedRole')
        );
    }

    public function getParentChild($id, $array)
    {
        foreach ($array as $node) {
            $this->tempHolder[$id][] = Arr::get($node, 'id');
            if (isset($node['children'])) {
                $this->getParentChild(Arr::get($node, 'id'), $node['children']);
            }
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function updateChildNode(Request $request)
    {
        try {
            DB::beginTransaction();
            if ($request->input('is_root') == 'true') {
                $validator = Validator::make($request->all(), [
                    'role' => 'required',
                    'is_root' => 'required',
                    'menu_title' => 'required',
                    'slug' => 'required',
                    'menu_order' => 'numeric'
                ]);
            } else {
                $validator = Validator::make($request->all(), [
                    'role' => 'required',
                    'is_root' => 'required',
                    'menu_title' => 'required',
                    'slug' => 'required',
                    'url' => 'required'
                ]);
            }
            if ($validator->fails()) {
                Log::error($validator->errors());
                return redirect()
                    ->route($this->route . '.index')
                    ->withErrors($validator)
                    ->withInput();
            }
            $roles = $request->get('role');
            $menus = \App\Models\Menu::select()
                ->whereNotNull('slug')
                ->get();
            $roleSync = [];
            foreach ($menus as $menu) {
                if ($menu->id == $request->input('id')) {
                    $menu->menu_title = $request->input('menu_title');
                    $menu->slug = $request->input('slug');
                    if ($request->input('is_root') != 'true') {
                        $menu->url = $request->input('url');
                    }
                    $menu->description = $request->input('description');
                    if (!empty($request->input('menu_order'))) {
                        $menu->menu_order = $request->input('menu_order');
                    }
                    $menu->update();
                    foreach ($roles as $role) {
                        $tempExtra = [
                            'id' => Generator::uuid4()->toString(),
                            'is_enable' => 1
                        ];
                        $roleSync[$role] = $tempExtra;
                    }
                    $menu->roles()->sync($roleSync);
                }
            }
            DB::commit();
            return redirect()
                ->route($this->route . '.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            abort(500);
        }
    }

    /**
     * @param $id
     * @throws \Exception
     */
    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $hasParent = Menu::where('parent_id', $id)->get()->count() > 0 ? true : false;
            if ($hasParent) {
                $affected = DB::table('menus')
                    ->where('parent_id', $id)
                    ->update(['parent_id' => config('psa.DEFAULT_SIDEBAR_ID')]);
                if ($affected) {
                    $menu = Menu::findOrFail($id);
                    $menu->roles()->detach();
                    $menu->delete();
                }
            } else {
                $menu = Menu::findOrFail($id);
                $menu->roles()->detach();
                $menu->delete();
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            abort(500);
        }
    }

    public function showCreateRoot($id)
    {
        try {
            $model = Menu::findOrFail($id);
            $roles = Role::select('*')->get();
            $menu_order = (int)Menu::where('parent_id', $id)->max('menu_order');
            if ($menu_order > 0) {
                $menu_order += 1;
            }
            return view($this->view . '.form.create', compact(
                    'model',
                    'menu_order',
                    'roles')
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            abort(500);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($request->all(), [
                'role' => 'required',
                'menu_title' => 'required',
                'slug' => 'required',
                'menu_order' => 'numeric'
            ]);
            if ($validator->fails()) {
                return redirect()
                    ->route($this->route . '.index')
                    ->withErrors($validator)
                    ->withInput();
            }
            $parent_id = $request->input('parent_id');
            $roles = $request->get('role');
            $roleSync = [];
            $menu = new Menu();
            $menu->menu_title = $request->input('menu_title');
            $menu->slug = $request->input('slug');
            $menu->parent_id = $parent_id;
            $menu_order = (int)Menu::where('parent_id', $parent_id)->max('menu_order');
            if ($menu_order > 0) {
                $menu_order += 1;
            }
            $order = $request->input('menu_order');
            if (isset($order)) {
                $menu->menu_order = $order;
            } else {
                $menu->menu_order = $menu_order;
            }
            $menu->id = Uuid::getFactory()->uuid4()->toString();
            $menu->icon = $request->input('icon');
            $menu->url = $request->input('url');
            $menu->description = $request->input('description');
            $menu->save();
            foreach ($roles as $role) {
                $tempExtra = [
                    'id' => Generator::uuid4()->toString(),
                    'is_enable' => 1
                ];
                $roleSync[$role] = $tempExtra;
            }
            $menu->roles()->sync($roleSync);
            DB::commit();
            return redirect()
                ->route($this->route . '.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            abort(500);
        }

    }
}
