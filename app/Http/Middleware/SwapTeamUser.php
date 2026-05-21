<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SwapTeamUser
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->role === 'team' && $user->parent_id) {
                $parent = $user->parent;
                if ($parent) {
                    // Store the original team user in application container
                    app()->instance('team_user', $user);
                    // Swap to parent admin user for database queries & limits
                    Auth::setUser($parent);
                }
            }
        }

        return $next($request);
    }
}
