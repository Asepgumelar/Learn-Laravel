<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\BaseBackendController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardController extends BaseBackendController
{
    public function __construct()
    {
        parent::__construct();
        $this->menu = trans('menu.dashboard.name');
        $this->route = $this->routes['backend'] . 'dashboard';
        $this->slug = $this->slugs['backend'] . 'dashboard';
        $this->view = $this->views['backend'] . 'dashboard';
        $this->breadcrumb = '<li class="breadcrumb-item"><a href="' . route($this->route . '.index') . '">' . $this->menu . '</a></li>';
        # share parameters
        $this->share();
    }

    public function index()
    {
        try {
            $breadcrumb = $this->breadcrumbs($this->breadcrumb . '<li class="breadcrumb-item active">' . 'Index' . '</li>');
            $route = $this->route;
            return view($this->view . '.index', compact('breadcrumb', 'route'));
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            abort(500);
        }
    }
}