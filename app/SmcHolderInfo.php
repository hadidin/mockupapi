<?php

namespace App;
use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

class SmcHolderInfo extends Model
{
    protected $table = 'psm_smc_holder_info';

    protected $fillable = [
        'plate_no1', 'plate_no2', 'plate_no3', 'plate_no4', 'plate_no5'
    ];

    public function carInSite() {
       return $this->hasMany(\App\CarInSite::class, 'season_holder_id', 'card_id');
    }

    public static function getHolderInfo($plate_no){
        $holderInfo = self::where(function ($query) use ($plate_no) {
            $query->orWhere('plate_no1', $plate_no)
                ->orWhere('plate_no2', $plate_no)
                ->orWhere('plate_no3', $plate_no)
                ->orWhere('plate_no4', $plate_no)
                ->orWhere('plate_no5', $plate_no);
        })
            ->Where('active_flag', '1')
            ->Where('delete_flag', '0')
            ->orderBy('id','desc')
            ->first();
        return $holderInfo;
    }

    public static function getDualHolderInfo($plateInfo){

        if($plateInfo['record_to_process'] == 1){
            $plate_no1 = $plateInfo["cam1"]["plate_no"];
            $holder_info = self::getHolderInfo($plate_no1);
//            $plateInfo["holder_info"] = $holder_info;

            if($holder_info != null){
                $holder_info = json_decode(json_encode($holder_info),true);
                $response["success"] = true;
                $response["error"] = 'success';
                $response["message"] = 'success';
                $holder_info["plate_no"] = $plate_no1;
                $response["data"] = $holder_info;
            }
            else{
                $response["success"] = false;
                $response["error"] = 'no_season_pass';
                $response["message"] = 'not subcribe for season pass';
            }
            return $response;
        }

        //for dual plate no
        //check first plate
        $plate_no1 = $plateInfo["cam1"]["plate_no"];
        $holder_info = self::getHolderInfo($plate_no1);
        if($holder_info != null){
            $holder_info = json_decode(json_encode($holder_info),true);
            $response["success"] = true;
            $response["error"] = 'success';
            $response["message"] = 'success';
            $holder_info["plate_no"] = $plate_no1;
            $response["data"] = $holder_info;
            return $response;
        }

        //check second plate
        $plate_no2 = $plateInfo["cam2"]["plate_no"];
        $holder_info = self::getHolderInfo($plate_no2);
        if($holder_info != null){
            $holder_info = json_decode(json_encode($holder_info),true);
            $response["success"] = true;
            $response["error"] = 'success';
            $response["message"] = 'success';
            $holder_info["plate_no"] = $plate_no2;
            $response["data"] = $holder_info;
            return $response;
        }

        //no season pass for both
        $response["success"] = false;
        $response["error"] = 'no_season_pass';
        $response["message"] = 'not subcribe for season pass';
        return $response;

    }

    public static function getBindedUserInfo($plateInfo,$seasonInfo){
        $plate_no = $plateInfo["cam1"]["plate_no"];

//        dd($plateInfo,"smcholderinfoline32");
        //todo ..need to get 2 car plate no binded info
        $client = new \GuzzleHttp\Client([
            'verify' => false
        ]);

        //get configuration for kp cloud secret
        $ini_array = parse_ini_file("../cron/config.ini",true);
        $KPBOX_SECRET=$ini_array['sync_to_cloud']['KP_TOKEN'];
        $url=$ini_array['common']['KP_CLOUD']."/api/lpr/plate_no/bind/status?plate_number=$plate_no";
        Log::info("Request for card binded info $url");
        try{
            $transactions = $client->request('GET', $url, ['headers'  => [
                'Content-Type' => 'application/json','x-api-key' => $KPBOX_SECRET],'connect_timeout' => 2,'timeout' => 2]);
            $body = $transactions->getBody();
            $data=json_decode($body,true);
            Log::info("binded body= $body");
            if ($data['success']==true) {
                return $data['data'];
            } else {
                return null;
            }
        }//try
        catch (\Exception $e){
            Log::error("Failed to get binded info details, check access token on config.ini..or lpr_kiplepark_cloud, detail =$e");
            return null;
        }
        return response()->json($data);
    }

    public static function getBindedUserInfo_v2($plateInfo){
        $plate_no1 = $plateInfo["cam1"]["plate_no"];
        $plate_no2 = $plateInfo["cam2"]["plate_no"];
        Log::info("[getBindedUserInfo_v2] get binded info",$plateInfo);
        $client = new \GuzzleHttp\Client([
            'verify' => false
        ]);

        if($plateInfo['record_to_process'] == 1){
            $dual_plate = false;
        }
        if($plateInfo['record_to_process'] == 2){
            $dual_plate = true;
        }
        $xodata = array(
            'plate_number1' => $plate_no1,
            'plate_number2' => $plate_no2,
            'dual_plate' => $dual_plate,
        );
        $odata = json_encode($xodata);


        //get configuration for kp cloud secret
        $ini_array = parse_ini_file("../cron/config.ini",true);
        $KPBOX_SECRET=$ini_array['sync_to_cloud']['KP_TOKEN'];

        $body = \GuzzleHttp\Psr7\stream_for($odata);

        $url=$ini_array['common']['KP_CLOUD']."/api/lpr/plate_no/bind/statusV2";
        Log::info("Request for card binded info $url");
        try{
            $transactions = $client->request('GET', $url, ['body' => $body, 'headers'  => [
                'Content-Type' => 'application/json','x-api-key' => $KPBOX_SECRET],'connect_timeout' => 2,'timeout' => 2]);
            $body = $transactions->getBody();
            $data=json_decode($body,true);
            Log::info("binded body= $body");
            if ($data['success']==true) {
                return $data;
            } else {
                return null;
            }
        }//try
        catch (\Exception $e){
            Log::error("Failed to get binded info details, check access token on config.ini..or lpr_kiplepark_cloud, detail =$e");
            return null;
        }
        return response()->json($data);
    }

    public static function getCarDetail($plate_no)
    {

        $car_detail = self::where(function ($query) use ($plate_no) {
                            $query->orWhere('plate_no1', $plate_no)
                                    ->orWhere('plate_no2', $plate_no)
                                    ->orWhere('plate_no3', $plate_no);
                            })
                            ->Where('active_flag', '1')
                            ->Where('delete_flag', '0')
                            ->first();


        return $car_detail;
    }

    public static function get_bind_info($plate_no,$plate_no2)
    {
        try{
            $client = new \GuzzleHttp\Client([
                'verify' => false
            ]);

            //get configuration for kp cloud secret
            $ini_array = parse_ini_file("../cron/config.ini",true);
            $KPBOX_SECRET=$ini_array['sync_to_cloud']['KP_TOKEN'];
            $url=$ini_array['common']['KP_CLOUD']."/api/lpr/plate_no/bind/statusV2";
            Log::info("Request for card binded info $url");
            $xodata = array(
                'plate_number1' => $plate_no,
                'dual_plate' => false,
            );
            if (!empty($plate_no2)) {
                $xodata['plate_number2'] =  $plate_no2;
                $xodata['dual_plate'] =  true;
            }
            $odata = json_encode($xodata);
            $body = \GuzzleHttp\Psr7\stream_for($odata);
            $response = $client->request('GET', $url,
                ['headers'  => ['Content-Type' => 'application/json','x-api-key' => $KPBOX_SECRET],
                'body' => $body,
                'connect_timeout' =>3,
                'timeout' => 3]);
            if ($response->getStatusCode()==200) {
                $body = $response->getBody();
                Log::info("Request for card binded info:$odata|$body");
                $data=json_decode($body,true);
                if ($data['success']==true) {
                    return $data['data'];
                }
            }
        }//try
        catch (\Exception $e){
            Log::error("Failed to get binded info details, check access token on config.ini..or lpr_kiplepark_cloud, detail =$e");
        }
        return false;
    }


    public static function getAllRecords()
    {
        $records = DB::table('psm_smc_holder_info')
            ->get([
                'id',
                'card_id',
                'user_name',
                'user_name2',
                'company_name',
                'contact_no',
                'plate_no1',
                'plate_no2',
                'plate_no3',
                'plate_no4',
                'plate_no5',
                'parking_slot',
                'bay_no',
                'remarks',
                'valid_from',
                'valid_until',
                'active_flag',
                'access_category',
                'updated_at',
                'created_at'
            ]);

        return $records;
    }


    public static function getRecordByCardId($card_id)
    {
        $records =DB::table('psm_smc_holder_info')
            ->where('card_id', $card_id)
            ->get();
        return $records;
    }


    public static function createRecord(
        $card_id,
        $username,
        $username2,
        $company_name,
        $contact_no,
        $plate_no1,
        $plate_no2,
        $plate_no3,
        $plate_no4,
        $plate_no5,
        $parking_slot,
        $bay_no,
        $remarks,
        $valid_from,
        $valid_until,
        $active_flag
    ) {
        $customer_id = uniqid();
        $result = DB::table('psm_smc_holder_info')
            ->insert([
                'customer_id' => $customer_id,
                'card_id' => $card_id,
                'user_name' => $username,
                'user_name2' => $username2,
                'company_name' => $company_name,
                'contact_no' => $contact_no,
                'plate_no1' => $plate_no1,
                'plate_no2' => $plate_no2,
                'plate_no3' => $plate_no3,
                'plate_no4' => $plate_no4,
                'plate_no5' => $plate_no5,
                'plate_no5' => $plate_no5,
                'parking_slot' => $parking_slot,
                'bay_no' => $bay_no,
                'remarks' => $remarks,
                'valid_from' => $valid_from,
                'valid_until' => $valid_until,
                'active_flag' => $active_flag,
                'created_at'  => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ]);

        if ($result!=1) {
            return false;
        }
        return true;
    }

    public static function updateRecord(
        $id,
        $card_id,
        $username,
        $username2,
        $company_name,
        $contact_no,
        $plate_no1,
        $plate_no2,
        $plate_no3,
        $plate_no4,
        $plate_no5,
        $parking_slot,
        $bay_no,
        $remarks,
        $valid_from,
        $valid_until,
        $active_flag
    ) {
        $result =DB::table('psm_smc_holder_info')
            ->where('id', $id)
            ->update([
                'card_id' => $card_id,
                'user_name' => $username,
                'user_name2' => $username2,
                'company_name' => $company_name,
                'contact_no' => $contact_no,
                'plate_no1' => $plate_no1,
                'plate_no2' => $plate_no2,
                'plate_no3' => $plate_no3,
                'plate_no4' => $plate_no4,
                'plate_no5' => $plate_no5,
                'plate_no5' => $plate_no5,
                'parking_slot' => $parking_slot,
                'bay_no' => $bay_no,
                'remarks' => $remarks,
                'valid_from' => $valid_from,
                'valid_until' => $valid_until,
                'active_flag' => $active_flag,
                'updated_at' => date("Y-m-d H:i:s")
            ]);

        if ($result!=1) {
            return false;
        }
        return true;
    }

    public static function deleteRecord($id)
    {
        $result =DB::table('psm_smc_holder_info')
            ->where('id', $id)
            ->delete();
        if ($result!=1) {
            return false;
        }
        return true;
    }

    public static function searchRecordByPlateNo($plate_no)
    {
        $record = self::where(function ($query) use ($plate_no) {
                $query->orWhere('plate_no1', $plate_no)
                    ->orWhere('plate_no2', $plate_no)
                    ->orWhere('plate_no3', $plate_no)
                    ->orWhere('plate_no4', $plate_no)
                    ->orWhere('plate_no5', $plate_no);
                })
                ->Where('active_flag', '1')
                ->Where('delete_flag', '0')
                ->first();
        return $record;
    }

}
