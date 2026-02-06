<?php

namespace App\Http\Middleware;

use App\Services\AdminActionLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogAdminActions
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('POST') && $request->user()) {
            $user = $request->user();

            AdminActionLogger::log([
                'user' => $user->name.' ('.$user->email.')',
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'is_livewire' => $request->hasHeader('X-Livewire'),
                'response_status' => $response->getStatusCode(),
            ]);
        }

        return $response;
    }
}
