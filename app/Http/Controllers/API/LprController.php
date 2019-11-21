<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LprController extends Controller
{
    public function zenith_array_text_format($message,$open_gate,$plate_no,$lpr_id,$camera_id,$code){
        if($open_gate==true){
            $op=',{
"type" : "open_gate"
}';

            #disabled open gate to demo purpose
            $disabled_open_gate=config('custom.lpr_disabled_open_gate');
            if($disabled_open_gate==1){
                $op="";
            }

        }
        if($open_gate==false){$op="";}

        $line1=base64_encode($message[0]);

        if($message[1] == "%%plate_no%%"){
            $line2=base64_encode($plate_no);
            $xx_line2 = $plate_no;
        }
        else{
            $line2=base64_encode($message[1]);
            $xx_line2 = $message[1];
        }


        $xx=$code."-".$message[0]."-".$xx_line2;

        $json_text='{
                    "id": "'.$lpr_id.'",
                    "sn": "'.$camera_id.'",
                    "xx": "'.$xx.'",
                    "operator":[
                    {
                    "type" : "led_display",
                    "messages": {
                        "line1": "'.$line1.'",
                        "line2": "'.$line2.'"
                      },
                    "time": 30
                    }'.$op.'
                    ]
                    }';
//        dd($op);

        $json_text = str_replace(array("\n", "\r"," "), '', $json_text);
        $json_payload = json_decode($json_text, true);
        return $json_payload;
    }

    public static function manual_triger_open_barrier($cam_sn,$line1,$line2){
        $json_text = '{
                          "sn": "'.$cam_sn.'",
                          "operator": [
                            {
                              "type": "led_display",
                              "messages": {
                                "line1": "'.$line1.'",
                                "line2": "'.$line2.'"
                              },
                              "time": 30
                            },{
                              "type": "open_gate"
                            }
                          ]
                       }';
        $json_text = str_replace(array("\n", "\r"," "), '', $json_text);
        $json_payload = json_decode($json_text, true);
        return $json_payload;
    }

    public function lpr_operation($code,$plate_no,$lpr_id,$camera_id){

        #sent to vendor for vehicle info
//        $vendor_id=config('custom.vendor_id');
//        $ini_array = parse_ini_file("../cron/config.ini",true);
//        $vendor_id=$ini_array['common']['VENDOR_ID'];
//
//        $vendor_push=\App\Http\Controllers\API\VendorHubController::push_normal_ticket($vendor_id,$plate_no,$image_path,$camera_id,$car_detail,$ext_cam_id,$lane_in_out_flag,$log_id,$code);


        $operation_mode=config('custom.lpr_demo_rpi');
        if($operation_mode==1){
            $operation=self::lpr_operation_demo($code,$plate_no,$lpr_id,$camera_id);
        }
        if($operation_mode==0){
            $operation=self::lpr_operation_live($code,$plate_no,$lpr_id,$camera_id);
        }

//        $operation["vendor_ticket_id"]=$vendor_push;
        return $operation;

    }

    public function lpr_operation_demo($code,$plate_no,$lpr_id,$camera_id){
        $lpr_operation_lib=\App\LprParam::get_param("lpr_operation");
        $lpr_operation_lib_arr=json_decode($lpr_operation_lib,true);

        $message=$lpr_operation_lib_arr[$code]["message"];
        $open_gate=$lpr_operation_lib_arr[$code]["open_gate"];

        $data=$this->trigger_lpr_operation($message,$open_gate,$plate_no,$lpr_id,$camera_id);
        $json_payload = json_decode($data, true);
        return $json_payload;

    }

    public function lpr_operation_live($code,$plate_no,$lpr_id,$camera_id){
        $lpr_operation_lib=\App\LprParam::get_param("lpr_operation");
        $lpr_operation_lib_arr=json_decode($lpr_operation_lib,true);
        #dd($code);
        #dd($lpr_operation_lib_arr);
        $message=$lpr_operation_lib_arr[$code]["message"];
        $open_gate=$lpr_operation_lib_arr[$code]["open_gate"];
        #dd($message,$open_gate);


        $json_payload = self::zenith_array_text_format($message,$open_gate,$plate_no,$lpr_id,$camera_id,$code);
        #dd($json_payload);
        return $json_payload;



    }


    public function trigger_lpr_operation($message,$open_gate,$plate_no,$lpr_id,$camera_id){

        $json_payload = self::zenith_array_text_format($message,$open_gate,$plate_no,$lpr_id,$camera_id);
        Log::info("Data sent to lpr_backend =",$json_payload);
        $json_payload=json_encode($json_payload);


        $client = new \GuzzleHttp\Client([
            'verify' => false
        ]);
        $odata=$json_payload;
        $body = \GuzzleHttp\Psr7\stream_for($odata);
        $url="http://".config('custom.lpr_backend_host').":".config('custom.lpr_backend_port')."/v1/device/operation";
        try{
            $transactions = $client->request('PUT', $url, ['body' => $body, 'headers'  => [
                'Content-Type' => 'application/json']]);
//            $data = $transactions->getBody();
         }//try
        catch (RequestException $e) {
            $error= response()->json(['error' => $e->getResponse()->getReasonPhrase()], $e->getResponse()->getStatusCode());
            Log::error("Failed to trgigger lpr backend details=$error");
            return response()->json(['error' => $error]);
        }
        catch (RequestException $e) {
            $error_msg=$e->getHandlerContext()["error"];
            Log::error("Failed to trgigger lpr backend details=$error_msg");
            return response()->json(['error' => $error_msg]);
        }
        catch (\Exception $e){
            Log::error("Failed to trgigger lpr backend details=$e");
            return response()->json(['error' => ""]);
        }

        $body = $transactions->getBody();
        Log::info("Data from lpr_backend = $body");
        return $json_payload;
    }

    public static function trigger_lpr_operation_manual_leave($camera_id,$message_line1,$message_line2){

        $json_payload = self::manual_triger_open_barrier($camera_id,$message_line1,$message_line2);
        Log::info("Data sent to lpr_backend =",$json_payload);
        $json_payload=json_encode($json_payload);


        $client = new \GuzzleHttp\Client([
            'verify' => false
        ]);
        $odata=$json_payload;
        $body = \GuzzleHttp\Psr7\stream_for($odata);
        $url="http://".config('custom.lpr_backend_host').":".config('custom.lpr_backend_port')."/v1/device/operation";
        Log::info('trigger_lpr_operation_manual_leave server to url = '.$url);
        try{
            $transactions = $client->request('PUT', $url, ['body' => $body, 'headers'  => [
                'Content-Type' => 'application/json']]);
//            $data = $transactions->getBody();
        }//try
        catch (\Exception $e){
            Log::error("Failed to trgigger lpr backend details=$e");
            return response()->json(['error' => ""]);
        }

        $body = $transactions->getBody();
        Log::info("Data from lpr_backend = $body");
        return $json_payload;
    }

    public function config_operation(Request $request){
        $new_config=$request->lpr_operation;
        $new_config=\GuzzleHttp\json_encode($new_config,JSON_PRETTY_PRINT);
        try{
            $config_operation= new \App\LprParam;
            $config_operation->update_lpr_operation($new_config);
            return "success";
        }
        catch (Exception $e){
            Log::error("faied to update db = $e");
            return "error";
        }

    }
    public  function test(){
        echo "test";
    }
}
