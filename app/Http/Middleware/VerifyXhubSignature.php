<?php

namespace App\Http\Middleware;

use App\Project;
use Closure;
use Illuminate\Http\Request;

class VerifyXhubSignature
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return \Illuminate\Http\Response
     */
    public function handle(Request $request, Closure $next)
    {
        $slug = $request->route('slug');

        $project = Project::findBySlug($slug);

        $sigCheck = 'sha1='.hash_hmac('sha1', $request->getContent(), $project->secret);
        if (!hash_equals($sigCheck, $request->header('X-Hub-Signature'))) {
            return response('Invalid signature', 401);
        }

        return $next($request);
    }
}
