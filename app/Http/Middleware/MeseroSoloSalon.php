<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class MeseroSoloSalon
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && $user->role === 'mesero') {
            if ($user->tienePermiso('caja') && $request->is('caja', 'caja/*')) {
                return $next($request);
            }
            if ($user->tienePermiso('pos') && $request->is('pos', 'pos/*')) {
                return $next($request);
            }
            if ($user->tienePermiso('cocina') && $request->is('cocina', 'cocina/*')) {
                return $next($request);
            }
            if (! $request->is('mesero', 'mesero/*', 'profile', 'profile/*', 'logout')) {
                return redirect()->route('mesero.index');
            }
        }
        return $next($request);
    }
}
