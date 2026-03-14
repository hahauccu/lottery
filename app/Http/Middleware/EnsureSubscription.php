<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Filament::getTenant();
        if ($tenant) {
            $tenant->ensureSubscription();
        }

        return $next($request);
    }
}
