<?php

namespace App\Http\Middleware;

use Closure;
use Log;

class ExternalIpWhitelist
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
        $cloud_whitelist=config('custom.cloud_whitelist');
        $white_list = explode(',',$cloud_whitelist);

        $request_ip=$_SERVER['REMOTE_ADDR'];

        for($a=0;$a<count($white_list);$a++){
            if($white_list[$a] == $request_ip){
                return $next($request);
            }
        }
        Log::critical("Try to call by $request_ip for external payment process");
        $error_code = 'whitelist_failed';
        $failed_response = \App\Http\Controllers\ErrorCodeController::error_response('general',$error_code);
        return response()->json($failed_response, 200);
    }
}
