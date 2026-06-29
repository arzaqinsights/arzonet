<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSuspended
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();
            $owner = ($user->role === 'team' && $user->parent_id) ? $user->parent : $user;

            if ($owner && $owner->is_suspended && !$request->is('logout') && !$request->is('super/*')) {
                return response()->view('errors.suspended', [
                    'reason' => $owner->suspension_reason
                ]);
            }
        }

        return $next($request);
    }
}
