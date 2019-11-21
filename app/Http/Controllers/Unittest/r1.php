<?php

namespace App\Http\Controllers\Unittest;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class r1 extends Controller
{
    public static function unit_test_get_all_lanes($in_out_flag){
        $lanes = DB::table('psm_lane_config AS a')
            ->leftJoin('psm_camera AS b','b.lane_id','a.id')
            ->select('a.id as lane_id','b.id as camera_id','a.name','a.in_out_flag','b.camera_sn','b.position')
            ->where('a.in_out_flag',$in_out_flag)
            ->where('b.camera_sn','<>','')
            ->get();
        return $lanes;
    }
    public static function generate_parameter($plate_no,$cam_sn,$dvd_id){
        $post_data = '{"body":{"event_type":"plate_event","result":{"PlateResult":{"bright":0,"carBright":0,"carColor":0,"colorType":3,"colorValue":0,"confidence":99,"direction":4,"gioouts":[{"ctrltype":0,"ionum":0}],"imageFile":"","imageFilePath":"/big/is/here","imageFragmentFilePath":"/small/is/here","isoffline":0,"license":"'.$plate_no.'","location":{"RECT":{"bottom":520,"left":750,"right":932,"top":467}},"plateid":89175,"timeStamp":{"Timeval":{"decday":5,"dechour":10,"decmin":45,"decmon":11,"decsec":15,"decyear":2018,"sec":1541385915,"usec":963551}},"timeUsed":12518399,"triggerType":8,"type":8}},"vzid":{"enable_group":1,"gate_channel":0,"ip_addr":"192.168.100.0","led_channel":0,"name":"","sn":"'.$cam_sn.'","state":"online","type":"unknown"}},"cmd":"event_notify","id":"'.$dvd_id.'"}';
        return $post_data;
    }

    public static function post_request_api($post_data,$url){
        $json_payload = json_decode(json_encode($post_data), true);
        $client = new \GuzzleHttp\Client();
        $body = \GuzzleHttp\Psr7\stream_for($json_payload);
//        $url="http://localhost:8000/api/lpr/push_plate_no";
        $transactions = $client->request('POST', $url, ['body' => $body, 'headers'  => [
            'Content-Type' => 'application/json']]);
        $array_of_data = json_decode($transactions->getbody(),true);
        return $array_of_data;
    }

    public static function post_request_api_async($post_data,$url){
        $json_payload = json_decode(json_encode($post_data), true);
        $client = new \GuzzleHttp\Client();

        $promise1 = $client->getAsync('http://loripsum.net/api')->then(
            function ($response) {
                return $response->getBody();
            }, function ($exception) {
            return $exception->getMessage();
        }
        );
        $response1 = $promise1->wait();

        return $response1;

    }

    public static function get_season_plate_no(){
//        SELECT plate_no1 FROM psm_smc_holder_info WHERE valid_until > NOW() AND active_flag=1 AND delete_flag=0 LIMIT 1
        $season_plate = DB::table('psm_smc_holder_info')
            ->select('plate_no1')
            ->where('valid_until','>',date("Y-m-d H:i:s"))
            ->where('active_flag',1)
            ->where('delete_flag',0)
            ->first();
        return $season_plate->plate_no1;
    }


}
