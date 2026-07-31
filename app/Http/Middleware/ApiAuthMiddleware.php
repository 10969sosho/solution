<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key') ?? $request->input('api_key');

        $validApiKey = (string) env('ADMS_API_KEY', '');

        if ($validApiKey === '' || ! $apiKey || ! hash_equals($validApiKey, (string) $apiKey)) {
            Log::channel('adms')->warning('Invalid API key attempt', [
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: Invalid API key',
            ], 401);
        }

        Log::channel('adms')->info('API authentication successful', [
            'ip' => $request->ip(),
            'machine_sn' => $request->input('machine_sn'),
        ]);

        return $next($request);
    }
}
