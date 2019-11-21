<?php

namespace App;
use Illuminate\Support\Facades\DB;

use Illuminate\Database\Eloquent\Model;

class LaneConfig extends Model
{
    protected $table = 'psm_lane_config';

    public static function getLaneDetail($camera_id)
    {
        DB::enableQueryLog();
        $lane_detail = self::where('camera_sn', $camera_id)->first();
        return $lane_detail;
        $lane_detail = DB::table('psm_lane_config')
            ->select('id','name','camera_sn','ext_cam_ref_id','vendor_lane_id','in_out_flag','parking_type_flag')
            ->where('vendor_lane_id', $vendorID)
            ->first();

    }

    public static function getLaneDetailById($id)
    {
        $lane_detail = self::where('id', $id)->first();
        return $lane_detail;
    }    

    public static function update_camera_state($state,$camera_sn,$raw_data,$ipaddress){
        DB::enableQueryLog();

        if($state=='online'){$state_flag=1;}
        if($state=='offline'){$state_flag=0;}

        DB::table('psm_camera_state_log')->insert(
            [
                'camera_sn' => $camera_sn,
                'state' => $state_flag,
                'raw' => $raw_data,
                'create_time' => date("Y-m-d H:i:s")
            ]
        );
        $log_id = DB::getPdo()->lastInsertId();


        DB::table('psm_lane_config')
            ->where('camera_sn', $camera_sn)
            ->update(
                [
                    'camera_state' => $state,
                    'ip_address' => $ipaddress,
                    'camera_state_log' => $log_id,
                    'updated_at' => date("Y-m-d H:i:s")
                ]
            );
        return "success";
    }

    public static function getLaneDetailByVendorID($vendorID)
    {
        $lane_detail = DB::table('psm_lane_config')
            ->select('id','name','camera_sn','ext_cam_ref_id','vendor_lane_id','in_out_flag','parking_type_flag')
            ->where('vendor_lane_id', $vendorID)
            ->first();
        return $lane_detail;
    }

    public static function getAllLanes()
    {
        $lanes = DB::table('psm_lane_config')
                    ->select('id','name','camera_sn','ext_cam_ref_id','vendor_lane_id','in_out_flag','parking_type_flag','camera_state')
                    ->get();
        return $lanes;
    }

    public static function getTotalCamInLane($cam_group){
        DB::enableQueryLog();
        $total_cam = self::where('group_id', $cam_group)->count();
        return $total_cam;
    }

    public static function unit_test_get_all_lanes($in_out_flag){
        $lanes = DB::table('psm_lane_config AS a')
            ->leftJoin('psm_camera AS b','b.lane_id','a.id')
            ->select('a.id as lane_id','b.id as camera_id','a.name','a.in_out_flag','b.camera_sn','b.position')
            ->where('a.in_out_flag',$in_out_flag)
            ->where('b.camera_sn','<>','')
            ->get();
        return $lanes;
    }

}
