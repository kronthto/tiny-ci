<?php

namespace App\Http\Controllers;

use App\Commit;
use Illuminate\Http\Request;

class BuildLogController extends Controller
{
    /**
     * @param Commit  $commit
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function showBuildLog(Commit $commit, Request $request)
    {
        if (!hash_equals($commit->getSecretToken(), $request->get('token'))) {
            return response('Invalid token', 401);
        }

        return response($commit->joblog, 200, ['Content-Type' => 'text/plain']);
    }
}
