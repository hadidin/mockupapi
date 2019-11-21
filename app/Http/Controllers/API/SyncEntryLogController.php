<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class SyncEntryLogController extends Controller
{
    public function sync_entry_logs(){
        $logs_list= new \App\EntryLog;
        $logs_list=$logs_list->get_logs_list();

        for($a=0;$a<count($logs_list);$a++){
            $logs_id=$logs_list[$a]["id"];
            $lane_id=$logs_list[$a]["lane_id"];
            $camera_sn=$logs_list[$a]["camera_sn"];
            $car_color=$logs_list[$a]["car_color"];
            $plate_no=$logs_list[$a]["plate_no"];
            $in_out_flag=$logs_list[$a]["in_out_flag"];
            $is_success=$logs_list[$a]["is_success"];
            $leave_type=$logs_list[$a]["leave_type"];

            $json[$a]["logs_id"]=$logs_id;
            $json[$a]["lane_id"]=$lane_id;
            $json[$a]["camera_sn"]=$camera_sn;
            $json[$a]["car_color"]=$car_color;
            $json[$a]["plate_no"]=$plate_no;
            $json[$a]["in_out_flag"]=$in_out_flag;
            $json[$a]["is_success"]=$is_success;
            $json[$a]["leave_type"]=$leave_type;


        }

        dd($json);

//        $rates = Rate::where('site_id', $site_id)->where('service', '=', $service)->get();
    }
}
