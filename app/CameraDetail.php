<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

class CameraDetail extends Model
{
    protected $table = 'psm_camera';

    public static function getCameraDetail($camera_id)
    {
        DB::enableQueryLog();;
        $camera_detail = DB::table('psm_camera as a')
            ->join('psm_lane_config AS b', 'b.id', '=', 'a.lane_id')
            ->select('a.lane_id','a.position','b.name','b.in_out_flag','b.parking_type_flag','b.ext_cam_ref_id')
            ->where('a.camera_sn', $camera_id)
            ->first();
//        dd(DB::getQueryLog());
        return $camera_detail;
    }

    public static function camera_count($lane_id){
//        $total_cam = self::where('group_id', $cam_group)->count();
        $total_cam = self::where('lane_id', $lane_id)->count();
        return $total_cam;

    }

    public static function getAllCameras()
    {
        $records = DB::table('psm_camera as a')
            ->leftjoin('psm_lane_config AS b', 'b.id', '=', 'a.lane_id')
            ->select('a.camera_sn','a.ip_address','a.lane_id','a.position','a.camera_state','a.camera_state_log','a.updated_at','b.name AS lane_name','b.vendor_lane_id','b.in_out_flag','b.parking_type_flag','b.ext_cam_ref_id')
            ->get();
        return $records;
    }


    public static function updateCameraState($camera_sn,$ipaddress,$state,$raw_data)
    {
        $state_flag = 1;
        if ($state=='offline') {
            $state_flag = 0;
        }

        $result = DB::table('psm_camera_state_log')->insert(
            [
                'camera_sn' => $camera_sn,
                'state' => $state_flag,
                'raw' => $raw_data,
                'create_time' => date("Y-m-d H:i:s")
            ]
        );
        if ($result<1) {
            return false;
        }
        $log_id = DB::getPdo()->lastInsertId();

        $result = DB::table('psm_camera')
            ->where('camera_sn', $camera_sn)
            ->update(
                [
                    'camera_state' => $state,
                    'ip_address' => $ipaddress,
                    'camera_state_log' => $log_id,
                    'updated_at' => date("Y-m-d H:i:s")
                ]
            );
        return $result;
    }    

}
