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
        if ($user && $user->role === 'mesero' && ! $request->is('mesero', 'mesero/*', 'profile', 'profile/*', 'logout')) {
            return redirect()->route('mesero.index');
        }
        return $next($request);
    }
}
