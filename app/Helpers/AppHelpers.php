<?php

use App\Models\Menu;

if (!function_exists('odk_admin_sidebar')) {
    function odk_admin_sidebar($withRole = true)
    {
        $menuData = Menu::adminSidebar($withRole)->get()->toArray();
        return $menuData;
    }
}

