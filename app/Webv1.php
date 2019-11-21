<?php

namespace App;
use Illuminate\Support\Facades\DB;

use Illuminate\Database\Eloquent\Model;

class Webv1 extends Model
{
    public function get_car_insite_list(){
        //$q="SELECT a.id as id_tbl_car_in_site,a.*,b.*,c.name AS lane_name,a.parking_type FROM psm_car_in_site a,psm_entry_log b,psm_lane_config c WHERE a.entry_id=b.id AND b.lane_id=c.id and a.is_left=0 ORDER BY a.created_at asc";
        $q="SELECT a.id AS id_tbl_car_in_site,a.*,b.*,c.name AS lane_name,a.parking_type ,d.user_name
FROM psm_car_in_site a
LEFT JOIN psm_entry_log b ON a.entry_id=b.id
LEFT JOIN psm_lane_config c ON c.id=b.lane_id
LEFT JOIN psm_smc_holder_info d ON d.card_id=a.season_holder_id AND d.delete_flag=0 AND d.active_flag=1
WHERE a.is_left=0";
        $car_insite_list=DB::select($q);
        return $car_insite_list;
    }
    public function release_car_to_exit($car_color,$plate_no,$card_id,$user_id){
        DB::enableQueryLog();

        $lane_info=DB::table('psm_lane_config')->select('id','camera_sn')
            ->where('in_out_flag', '1')
            ->get()->sortByDesc('id')->last();


        DB::table('psm_entry_log')->insert(
            [
                'lane_id' => $lane_info->id,
                'camera_sn' => $lane_info->camera_sn,
                'parking_type' => 1,
                'car_color' => $car_color,
                'plate_no' => $plate_no,
                'small_picture' => '',#get by pos
                'big_picture' => '',
                'qr_sn' => "",
                'qr_code' => "",
                'in_out_flag' => 1,
                'leave_type' => 1,
                'check_result' => 99,
                'vendor_check_result' => 99,
                'sync_status' => 2,//to avoid sync with cloud before update car to exit
                'create_time' => date("Y-m-d H:i:s")
            ]
        );
        $lastInsertId = DB::getPdo()->lastInsertId();
//

        DB::table('psm_car_in_site')
            ->where('season_holder_id', $card_id)
            ->where('is_left', '0')
            ->update(
                [
                    'exit_id' => $lastInsertId,
                    'is_left' => '1',
                    'updated_at' => date("Y-m-d H:i:s")
                ]
            );

        DB::table('psm_entry_log')
            ->where('id', $lastInsertId)
            ->update(
                [
                    'sync_status' => 0 //to sync with cloud back after car updated to exit.
                ]
            );
//        dd(DB::getQueryLog());
    }
    public function release_car_to_exit_normal_parking($ticket_id,$plate_no,$user_id){
        DB::enableQueryLog();
        $lane_info=DB::table('psm_lane_config')->select('id','camera_sn')
            ->where('in_out_flag', '1')
            ->get()->sortByDesc('id')->last();

        DB::table('psm_entry_log')->insert(
            [
                'lane_id' => $lane_info->id,
                'camera_sn' => $lane_info->camera_sn,
                'parking_type' => 0,
                'car_color' => '',
                'plate_no' => $plate_no,
                'small_picture' => '',#get by pos
                'big_picture' => '',
                'qr_sn' => "",
                'qr_code' => "",
                'in_out_flag' => 1,
                'leave_type' => 1,
                'check_result' => 99,
                'vendor_check_result' => 99,
                'create_time' => date("Y-m-d H:i:s")
            ]
        );
        $lastInsertId = DB::getPdo()->lastInsertId();
//

        DB::table('psm_car_in_site')
            ->where('plate_no', $plate_no)
            ->where('is_left', '0')
            ->update(
                [
                    'exit_id' => $lastInsertId,
                    'is_left' => '1',
                    'updated_at' => date("Y-m-d H:i:s")
                ]
            );
    }

    public function get_entry_history(){
//        $q="SELECT a.id as id_tbl_car_in_site,a.*,b.*,c.name AS lane_name FROM psm_car_in_site a,psm_entry_log b,psm_lane_config c WHERE a.entry_id=b.id AND b.lane_id=c.id and a.is_left=1 ORDER BY a.created_at desc";
        $q="SELECT a.id AS id_tbl_car_in_site,a.*,
b.id AS entry_logs_id,b.create_time AS entry_logs_time,b.small_picture AS pic1_entry,b.big_picture AS pic2_entry,b.lane_id AS entry_lane_id,b.plate_no,b.car_color,
d.id AS exit_logs_id,d.create_time AS exit_logs_time,d.small_picture AS pic1_exit,d.big_picture AS pic2_exit,d.lane_id AS exit_lane_id,d.leave_type
FROM psm_car_in_site a,psm_entry_log b,psm_lane_config c,psm_entry_log d
WHERE a.entry_id=b.id AND b.lane_id=c.id AND a.is_left=1 AND a.exit_id=d.id
ORDER BY a.created_at desc ";
        $entry_istory_list=DB::select($q);
//        dd($entry_istory_list);
        return $entry_istory_list;
    }

    public function get_entry_history_v2(){
            $entry_istory_list = DB::table('psm_car_in_site a,psm_entry_log b,psm_lane_config c,psm_entry_log d')
            ->select(
                'a.id as id_tbl_car_in_site',
                'a.*',
                'b.id as entry_logs_id',
                'b.create_time as entry_logs_time',
                'b.small_picture as pic1_entry',
                'b.big_picture as pic2_entry',
                'b.lane_id as entry_lane_id',
                'b.plate_no',
                'b.car_color',
                'b.id as exit_logs_id',
                'b.id as exit_logs_id',
                'b.create_time as exit_logs_time',
                'b.small_picture as pic1_exit',
                'b.big_picture as pic2_exit',
                'b.lane_id as exit_lane_id',
                'b.leave_type'
            )
            ->where('a.entry_id', 'b.id')
            ->where('b.lane_id', 'c.id')
            ->where('a.is_left', '1')
            ->where('a.exit_id', 'd.id')
            ->get();

            dd($entry_istory_list);
        return $entry_istory_list;
    }

    public function entry_log_db(){
        $q="select * from psm_entry_log ORDER BY id desc";
//        echo $q;die;
        $entry_log_db_list=DB::select($q);
        return $entry_log_db_list;
    }
}
