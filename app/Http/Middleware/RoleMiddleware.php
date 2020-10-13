<?php

namespace App\Http\Middleware;

use Closure;

class RoleMiddleware
{

    /**
     * @param $request
     * @param Closure $next
     * @param $role
     * @param null $permission
     * @return mixed
     */
    public function handle($request, Closure $next, $role)
    {

        if (!auth()->user()->hasAnyRoles($role)) {
            return abort(403);
        }
        return $next($request);

    }
}
