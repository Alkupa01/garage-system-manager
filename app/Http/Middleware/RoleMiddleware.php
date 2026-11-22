<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware pembatasan hak akses rute berdasarkan peran pengguna (admin atau tenant).
 */
class RoleMiddleware
{
    /**
     * Memeriksa apakah pengguna yang sedang login memiliki peran yang sesuai dengan parameter rute.
     *
     * @param Request $request Objek HTTP request masuk
     * @param Closure(Request): Response $next Callback penerus request
     * @param string $role Peran yang diizinkan ('admin' atau 'tenant')
     * @return Response
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!Auth::check() || Auth::user()->role !== $role) {
            abort(403, 'Akses ditolak.');
        }

        return $next($request);
    }
}