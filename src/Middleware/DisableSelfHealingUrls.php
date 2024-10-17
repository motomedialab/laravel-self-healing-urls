<?php

namespace Motomedialab\LaravelSelfHealingUrls\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DisableSelfHealingUrls
{
    public function handle(Request $request, \Closure $next): Response
    {
        $request->attributes->set('disable_self_healing_urls', true);

        return $next($request);
    }
}