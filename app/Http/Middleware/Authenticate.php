<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    protected function unauthenticated($request, array $guards)
    {
        if (
            $request->expectsJson() ||
            $request->is('api/*') ||
            str_starts_with($request->path(), 'api/')
        ) {
            abort(response()->json(['message' => 'Unauthenticated.'], 401));
        }
        parent::unauthenticated($request, $guards);
    }
}