<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PremiumMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || !auth()->user()->isPremium()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Ta funkcja jest dostępna tylko dla użytkowników Premium'
                ], 403);
            }
            
            return redirect()->route('subscription.index')
                ->with('error', 'Ta funkcja jest dostępna tylko dla użytkowników Premium');
        }

        return $next($request);
    }
}