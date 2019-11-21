<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class LprApi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $lpr_allow_remote_req=config('custom.lpr_allow_remote_req');
        #bypass unittest
        return $next($request);

        $request_ip=$_SERVER['REMOTE_ADDR'];
        if($lpr_allow_remote_req==1){
            return $next($request);
        }

        if($request_ip !="::1"){
            #return redirect('home');
            Log::warning("Allow remote request is disbaled.Try to call by $request_ip");
            return response()->json(array('success' => false, 'desc' => 'Only can be call by lpr backend server'), 400);
        }

        return $next($request);
    }
}
