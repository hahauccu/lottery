<?php

namespace App\Http\Middleware;

use App\Support\AccessCodeGenerator;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyOperatorAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $brandCode = $request->route('brandCode');

        if (! $brandCode) {
            return response()->json(['message' => 'missing_brand_code'], 400);
        }

        // 檢查 X-Operator-Token header
        $token = $request->header('X-Operator-Token');

        // 或 cookie
        if (! $token) {
            $cookieName = 'lottery_access_'.preg_replace('/[^a-zA-Z0-9_]/', '_', $brandCode);
            $token = $request->cookie($cookieName);
        }

        if (! $token || ! AccessCodeGenerator::verify($brandCode, $token)) {
            return response()->json(['message' => 'invalid_operator_token'], 403);
        }

        return $next($request);
    }
}
