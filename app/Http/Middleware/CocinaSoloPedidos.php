<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class CocinaSoloPedidos
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && $user->role === 'cocina' && ! $request->is('cocina', 'cocina/*', 'profile', 'profile/*', 'logout')) {
            return redirect()->route('cocina.index');
        }
        return $next($request);
    }
}
