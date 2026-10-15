<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class CajaSoloCaja
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && $user->role === 'cajero') {
            if ($user->tienePermiso('pos') && $request->is('pos', 'pos/*')) {
                return $next($request);
            }
            if ($user->tienePermiso('mesero') && $request->is('mesero', 'mesero/*')) {
                return $next($request);
            }
            if ($user->tienePermiso('cocina') && $request->is('cocina', 'cocina/*')) {
                return $next($request);
            }
            if (! $request->is('caja', 'caja/*', 'profile', 'profile/*', 'logout')) {
                return redirect()->route('caja.index');
            }
        }
        return $next($request);
    }
}
