<?php

namespace Motomedialab\LaravelSelfHealingUrls\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnableSelfHealingUrls
{
    public function handle(Request $request, \Closure $next): Response
    {
        $request->attributes->set('disable_self_healing_urls', false);
        return $next($request);
    }
}
