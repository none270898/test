<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPremium
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->isPremium()) {
            return redirect()->route('premium.upgrade')
                ->with('error', 'Ta funkcja jest dostępna tylko dla użytkowników Premium.');
        }

        return $next($request);
    }
}