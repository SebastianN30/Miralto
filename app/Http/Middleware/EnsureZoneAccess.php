<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe cada zona de la app según el rol del usuario:
 *  - panel:   admin y employee (mesero y cocinero quedan fuera)
 *  - waiter:  todos menos cocinero
 *  - kitchen: todos menos mesero
 * Al ser rechazado, el usuario vuelve a su pantalla de inicio.
 */
class EnsureZoneAccess
{
    private const DENIED = [
        'panel' => ['waiter', 'cook'],
        'waiter' => ['cook'],
        'kitchen' => ['waiter'],
    ];

    public function handle(Request $request, Closure $next, string $zone): Response
    {
        $user = $request->user();

        if ($user && in_array($user->role, self::DENIED[$zone] ?? [], true)) {
            return redirect($user->homePath());
        }

        return $next($request);
    }
}
