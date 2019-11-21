<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Illuminate\Support\Facades\Log;

class KpCloudController extends Controller
{

    public function get_cloud_token(Request $request)
    {
        $token_url = $request->token_url;
        $accessToken = $request->token;
        $username = $request->username;
        $pswd = $request->pswd;
        Log::info("request for token validation",$request->all());


        try{
            $signer = new Sha256();
            $token = (new Parser())->parse($accessToken); // Parses from a string
            $exp = $token->getClaim('exp');
            $exp_check = $exp - 120;  #deduct 2 minute
            if($exp_check < time()){
                $accessToken=self::get_new_token($token_url,$username,$pswd);
                return $accessToken;
            }
        }
        catch (\Exception $e){
            $accessToken=self::get_new_token($token_url,$username,$pswd);
            return $accessToken;
        }

        return response()->json(['token' => $accessToken]);

//        $accessToken=self::get_new_token($token_url,$username,$pswd);
//        return $accessToken;
    }
    public static function get_new_token($token_url,$username,$pswd){
        $client = new \GuzzleHttp\Client([
            'verify' => false
        ]);
        $odata=array(
            "email" => $username,
            "password" => $pswd
        );
        $json_payload=json_encode($odata);
        $body = \GuzzleHttp\Psr7\stream_for($json_payload);
        $url=$token_url."/api/localagent/login";
        try{
            $transactions = $client->request('POST', $url, ['body' => $body, 'headers'  => [
                'Content-Type' => 'application/json']]);
            $data = $transactions->getBody();
            $character = json_decode($data,true);
//            dd($character);
            $token=$character["data"]["token"];
            $site_id=$character["data"]["site_id"];
            return response()->json(['token' => $token,'site_id'=>$site_id]);
//            return $token;
        }//try
        catch (\Exception $e){
            Log::error("Failed to get for new token=$e");
            return response()->json(['error' => ""]);
        }
    }

    public function change_lock_status(Request $request){
        $kp_ticket_id=$request->kp_ticket_id;
        $locked_flag=$request->locked_flag;
	Log::info("Change lock status request = ",$request->all());
        $process=\App\CarInSite::change_lock_status($kp_ticket_id,$locked_flag);
        return response()->json(array("success"=>true,"details"=>$process),200);
    }

}
