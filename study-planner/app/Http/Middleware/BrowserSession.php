<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

class BrowserSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->header('X-Study-Planner-Web') !== '1') {
            return $next($request);
        }
        // A custom header plus strict same-site cookies prevents cross-site form CSRF.
        $origin = $request->header('Origin');
        abort_if($origin && $origin !== $request->getSchemeAndHttpHost(), 403);
        abort_if($request->header('Sec-Fetch-Site') === 'cross-site', 403);

        foreach (['access', 'refresh'] as $kind) {
            try {
                $value = Crypt::decryptString($request->cookie('sp_'.$kind, ''));
                if ($kind === 'access') {
                    $request->headers->set('Authorization', 'Bearer '.$value);
                } elseif ($request->is('api/auth/refresh')) {
                    $request->merge(['refresh_token' => $value]);
                }
            } catch (DecryptException) {
                // Invalid cookies are treated as an unauthenticated session.
            }
        }

        $response = $next($request);
        if ($response instanceof JsonResponse) {
            $body = $response->getData(true);
            if (isset($body['data']['token'], $body['data']['refresh_token'])) {
                foreach (['access' => 'token', 'refresh' => 'refresh_token'] as $kind => $key) {
                    $response->headers->setCookie(cookie('sp_'.$kind,
                        Crypt::encryptString($body['data'][$key]), $kind === 'access' ? 60 : 43200,
                        '/api', null, app()->isProduction() || $request->isSecure(), true, false, 'strict'));
                    $body['data'][$key] = 'cookie-session';
                }
                $response->setData($body);
            }
        }
        if ($request->is('api/auth/logout') || ($request->is('api/auth/refresh') && $response->getStatusCode() === 401)) {
            foreach (['access', 'refresh'] as $kind) {
                $response->headers->clearCookie('sp_'.$kind, '/api');
            }
        }
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
