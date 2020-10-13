<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\JsonResponse;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param \Illuminate\Http\Request $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (!$request->ajax() || !$request->expectsJson()) {
            return route('login');
        } else {
            $errors = array();
            $errors['meta']['code'] = 401;
            $errors['meta']['message'] = trans('message.api.error');
            $errors['meta']['errors'] = array('You must login');
            $errors['data'] = [];
            return new JsonResponse($errors, 401);
        }

    }
}
