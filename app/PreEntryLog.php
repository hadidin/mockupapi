<?php

namespace App;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class PreEntryLog extends Model
{
    protected $table = 'psm_entry_log';

    protected $fillable = [
        'id',
        'lane_id',
        'camera_sn',
        'car_color',
        'plate_no',
        'small_picture',
        'big_pricture',
        'qr_sn',
        'qr_code',
        'in_out_flag',
        'create_time',
        'datetime_sync_kp_cloud'
    ];

    public static function insert_logs($lane_id,$camera_id,$car_color,$plate_no,$full_img,$small_img,$lane_in_out_flag,$lpr_id)
    {
        DB::enableQueryLog();
        DB::table('psm_pre_entry_log')->insert(
            [
                'lane_id' => $lane_id,
                'camera_sn' => $camera_id,
                'car_color' => $car_color,
                'plate_no' => $plate_no,
                'small_picture' => $small_img,
                'big_picture' => $full_img,
                'qr_sn' => "",
                'qr_code' => "",
                'process_flag' => 0,
                'in_out_flag' => $lane_in_out_flag,
                'lpr_id' => $lpr_id,
                'created_at' => date("Y-m-d H:i:s")
            ]
        );
//        dd(DB::getQueryLog());
        $id = DB::getPdo()->lastInsertId();
        return array('success' => true, 'last_insert_id' => $id);
    }

    public static function get_record($lane_id)
    {
        $record = DB::table('psm_pre_entry_log')
            ->where('process_flag',0)
            ->where('lane_id',$lane_id)
            ->select('*')
            ->get();
        return $record;
    }

    public static function update_processing_flag($logs_id,$flag){
        DB::table('psm_pre_entry_log')
            ->where('id', $logs_id)
            ->where('process_flag', 0)
            ->update(
                [
                    'process_flag' => $flag,
                    'updated_at' => date("Y-m-d H:i:s")
                ]
            );
    }

    public static function get_unprocessed_records($from_id,$lane_id){
        return DB::table('psm_pre_entry_log')
            ->where('id','>=', $from_id)
            ->where('lane_id',$lane_id)
            ->where('process_flag', 0)
            ->orderBy('id','asc')
            ->get();
    }

    public static function updateUnprocessedRecords($ids){
        return DB::table('psm_pre_entry_log')
            ->whereIn('id', $ids)
            ->where('process_flag', 0)
            ->update(
                [
                    'process_flag' => 1,
                    'updated_at' => date("Y-m-d H:i:s")
                ]
            );
    }


    public static function insert_log($plate_info,$lane_info,$process_flag)
    {
        try {
            $result = DB::table('psm_pre_entry_log')->insert(
                [
                    'lane_id' => $lane_info['lane_id'],
                    'camera_sn' => $plate_info['camera_sn'],
                    'car_color' => $plate_info['car_color'],
                    'plate_no' => $plate_info['plate_no'],
                    'small_picture' => $plate_info['small_picture'],
                    'big_picture' => $plate_info['big_picture'],
                    'qr_sn' => "",
                    'qr_code' => "",
                    'is_season_subscriber' =>1,
                    'process_flag' => $process_flag,
                    'in_out_flag' => $lane_info['in_out_flag'],
                    'lpr_id' => $plate_info['lpr_id'],
                    'updated_at' => date("Y-m-d H:i:s"),
                    'created_at' => date("Y-m-d H:i:s")
                ]
            );
            if ($result!=1) {
                return false;
            }
            return DB::getPdo()->lastInsertId();
        } catch (\Exception $e) {
            Log::error("[insert_log] get exception:$e");
        }
        return false;
    }

}
