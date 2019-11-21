<?php

namespace App;
use DB;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class CarInSite extends Model
{
    protected $table = 'psm_car_in_site';

    protected $fillable = [
        'entry_id',
        'exit_id',
        'group_id',
        'parking_type',
        'season_holder_id',
        'user_id',
        'eticket_id',
        'locked_flag',
        'auto_deduct_flag',
        'parking_fee',
        'payment_type',
        'payment_time',
        'created_at',
        'updated_at'
    ];


    public static function car_in($entry_id_log,$parking_type,$card_id,$user_id,$eticket_id,$locked_flag,$auto_deduct_flag,$wallet_balance,$vendor_ticket_id,$plate_no)
    {
        DB::enableQueryLog();
        DB::table('psm_car_in_site')->insert(
            [
                'entry_id' => $entry_id_log,
                'parking_type' => $parking_type,
                'season_holder_id' => $card_id,
                'user_id' => $user_id,
                'eticket_id' => $eticket_id,
                'locked_flag' => $locked_flag,
                'auto_deduct_flag' => $auto_deduct_flag,
                'created_at' => date("Y-m-d H:i:s"),
                'vendor_ticket_id' => $vendor_ticket_id,
                'plate_no' => $plate_no
            ]
        );
        $dblog=DB::getQueryLog();
        return "success";
    }



    public static function car_out($exit_id_log,$plate_no)
    {
        DB::enableQueryLog();
        DB::table('psm_car_in_site')
            ->where('plate_no', $plate_no)
            ->where('is_left', '0')
            ->update(
            [
                'exit_id' => $exit_id_log,
                'is_left' => '1',
                'updated_at' => date("Y-m-d H:i:s")
            ]
        );
        return "success";
    }

    public static function check_car_in_site_by_season_id($card_id)
    {
        $season = \App\SmcHolderInfo::where('card_id', $card_id)
            ->first();

        $car_in_site = $season->carInSite()
            ->where('is_left', '0')
            ->get();

        if (count($car_in_site) < $season->parking_slot) {
            return false;
        }

        return true;
    }
    public static function check_car_in_site_by_plate_number($plateInfo)
    {
        if($plateInfo['record_to_process'] == 2){
            $car_detail = self::whereIn('plate_no', [$plateInfo['cam1']['plate_no'], $plateInfo['cam2']['plate_no']])
                ->where('is_left', '0')
                ->first();
        }
        else{
            $car_detail = self::where('plate_no',$plateInfo['cam1']['plate_no'])
                ->where('is_left', '0')
                ->first();
        }

        return $car_detail;
    }

    public static function check_car_in_site_by_plate_number2($plate_no)
    {
        $car_detail = self::where(function ($query) use ($plate_no) {
            $query->orWhere('plate_no', $plate_no)
                ->orWhere('plate_no2', $plate_no);
             })
            ->where('is_left', '0')
            ->first();

        return $car_detail;
    }
    public static function check_car_locking($plate_no)
    {
        $car_detail = self::where('plate_no', $plate_no)
            ->where('is_left', '0')
            ->value('locked_flag');

        return $car_detail;
    }

    public static function get_car_in_site_info($plate_no) {
        $car_detail = self::where('plate_no', $plate_no)
            ->where('is_left', '0')
            ->value('id')
            ->value('parking_type')
            ->value('locked_flag');

        return $car_detail;
    }

    public static function check_car_existance($plate_no)
    {
        $car_detail = self::where('is_left', '0')
            ->where('plate_no', $plate_no)
            ->value('id');
        return $car_detail;
    }

    public static function change_lock_status($kp_ticket_id,$locked_flag){

            #to get ticket id only.
            DB::enableQueryLog();
            $ticket_id=substr($kp_ticket_id,8);
            DB::table('psm_car_in_site')
                ->where('id', $ticket_id)
                ->where('is_left', 0)
                ->update(
                    [
                        'locked_flag' => $locked_flag,
                        'updated_at' => date("Y-m-d H:i:s")
                    ]
                );
            $dblog=DB::getQueryLog();

            try{
                $locked_flag = self::where('id',$ticket_id)
//            ->where('is_left', '0')
                ->value('locked_flag');
            }
            catch (\Exception $e){
                $locked_flag = $e;
            }


            return array("locked_flag"=>$locked_flag,"kp_ticket_id"=>$kp_ticket_id,"ticket_id"=>$ticket_id);

    }

    public static function check_car_in_site_binded($plate_no){
        $check_car_in_site_binded = self::where('is_left', '0')
            ->where('plate_no', $plate_no)
//            ->where('parking_type', 0)#get for normal parking only
            ->first();
        return $check_car_in_site_binded;
    }

    public static function vendor_to_insert_to_car_in_site($vendor_ticket_id,$user_id,$locked_flag,$auto_deduct_flag,$entry_id_log,$plate_no){
        DB::enableQueryLog();
        //normal parking from vendor integration to insert ticket
        DB::table('psm_car_in_site')->insert(
            [
                'entry_id' => $entry_id_log,
                'parking_type' => 0,
                'user_id' => $user_id,
                'locked_flag' => $locked_flag,
                'auto_deduct_flag' => $auto_deduct_flag,
                'created_at' => date("Y-m-d H:i:s"),
                'vendor_ticket_id' => $vendor_ticket_id,
                'plate_no' => $plate_no
            ]
        );
        $dblog=DB::getQueryLog();
        return "success";
    }

    public static function normal_parking_car_out($logs_id,$plate_no){
        DB::enableQueryLog();
        DB::table('psm_car_in_site')
            ->where('plate_no', $plate_no)
            ->where('is_left', '0')
            ->update(
                [
                    'exit_id' => $logs_id,
                    'is_left' => '1',
                    'updated_at' => date("Y-m-d H:i:s")
                ]
            );
        return "success";

    }

    public static function car_force_leave_for_normal($plate_no){
        DB::enableQueryLog();

        // check the record exist or not
        $carInParkRecord = DB::table('psm_car_in_site')
            ->select('id','parking_type','user_id')
            ->where('plate_no',$plate_no)
            ->where('is_left',0)
            ->get()
            ->first();
        if (empty($carInParkRecord)) {
            Log::error("car_force_leave_for_normal: record was not found for:  $plate_no.");
            return false;
        }

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
                'leave_type' => 2, // auto leave
                'check_result' => 11, //normal parking leave.
//                'vendor_check_result' => 99,
                'kp_user_id' => $carInParkRecord->user_id,
                'create_time' => date("Y-m-d H:i:s"),
                'sync_status' => 1
            ]
        );
        $lastInsertId = DB::getPdo()->lastInsertId();

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


        //to make sure sync log got the final result
        DB::table('psm_entry_log')
            ->where('id', $lastInsertId)
            ->update(
                [
                    'sync_status' => 0
                ]
            );
        return true;
    }

    public static function last_exit($lane_id){
        $entry_history_list = DB::table('psm_car_in_site AS a')
            ->join('psm_entry_log AS b', 'b.id', '=', 'a.entry_id')
            ->select(
                //exit
                'd.id as exit_id',
                'd.lane_id as exit_lane_id',
                'a.plate_no as exit_plate_no',
                'd.big_picture as exit_pic',
                'd.check_result as exit_check_result',
                'd.season_check_result as exit_season_check_result',
                'd.visitor_check_result as exit_visitor_check_result',
                'd.create_time as exit_create_time',

                //entry
                'b.id as entry_id',
                'b.lane_id as exit_lane_id',
                'a.plate_no as exit_plate_no',
                'b.big_picture as exit_pic',
                'b.check_result as exit_check_result',
                'b.season_check_result as exit_season_check_result',
                'b.visitor_check_result as exit_visitor_check_result',
                'b.create_time as exit_create_time',
                'a.season_holder_id',
                'a.vendor_ticket_id'
            )
            ->where('a.is_left', '0');
    }

    public static function searchEntry($plate_no,$full_match,$limit=10) {
        if ($full_match) {
            $entry_list = DB::table('psm_car_in_site AS a')
                ->leftjoin('psm_entry_log AS b', 'b.id', '=', 'a.entry_id')
                ->leftjoin('psm_lane_config AS c', 'c.id', '=', 'b.lane_id')
                ->select('b.id','b.lane_id','c.name AS lane_name','b.plate_no','b.big_picture','b.create_time','a.season_holder_id','a.vendor_ticket_id',
                    'b.check_result','b.season_check_result','b.visitor_check_result','b.previous_season_entry')
                ->where(function ($query) use ($plate_no) {
                    $query->orWhere('plate_no', $plate_no)
                        ->orWhere('plate_no2', $plate_no);
                })
                ->where('a.is_left', '0')
                ->limit($limit)
                ->get();
            return $entry_list;
        }
        $entry_list = DB::table('psm_car_in_site AS a')
            ->leftjoin('psm_entry_log AS b', 'b.id', '=', 'a.entry_id')
            ->leftjoin('psm_lane_config AS c', 'c.id', '=', 'b.lane_id')
            ->select('b.id','b.lane_id','c.name AS lane_name','b.plate_no','b.big_picture','b.create_time','a.season_holder_id','a.vendor_ticket_id',
                'b.check_result','b.season_check_result','b.visitor_check_result','b.previous_season_entry')
            ->where(function ($query) use ($plate_no) {
                $query->orWhere('a.plate_no','like','%'.$plate_no.'%')
                    ->orWhere('a.plate_no2','like','%'.$plate_no.'%');
            })
            ->where('a.is_left', '0')
            ->limit($limit)
            ->get();
        return $entry_list;
    }


    public static function dailyReport($datetime) {
        $sql = "SELECT * FROM (
            SELECT id,created_at AS entry_time,plate_no,parking_type,season_holder_id AS season_card,vendor_ticket_id AS visitor_ticket
            FROM psm_car_in_site
            WHERE created_at<'$datetime' and is_left=0
            UNION ALL
            SELECT a.id,a.created_at AS entry_time,a.plate_no,a.parking_type,a.season_holder_id AS season_card,a.vendor_ticket_id AS visitor_ticket
            FROM psm_car_in_site a
            LEFT JOIN psm_entry_log b ON b.id = a.exit_id
            WHERE a.created_at<'$datetime' AND b.create_time>='$datetime'
            ) c ORDER BY c.id ASC";
        $result = DB::select(DB::raw($sql));
        return $result;
    }


    public static function carInParkList(){
        $sql = "SELECT a.id,a.created_at AS entry_time,a.plate_no,a.plate_no2,a.parking_type,a.season_holder_id AS season_card,
            a.vendor_ticket_id AS visitor_ticket,c.user_name,b.big_picture,b.lane_id,a.locked_flag
            FROM psm_car_in_site a
            LEFT JOIN psm_entry_log b ON a.entry_id=b.id
            LEFT JOIN psm_smc_holder_info c ON c.card_id=a.season_holder_id AND c.delete_flag=0 AND c.active_flag=1 AND c.card_id <> ''
            WHERE a.is_left=0
            ORDER BY a.id DESC";
        $records = DB::select($sql);
        return $records;
    }


    public static function markAsLeave($id,$plate_no) {
        // check the record exist or not
        $carInParkRecord = DB::table('psm_car_in_site')
            ->select('id','parking_type','user_id')
            ->where('id',$id)
            ->where('plate_no',$plate_no)
            ->where('is_left',0)
            ->get()
            ->first();
        if (empty($carInParkRecord)) {
            Log::error("markAsLeave: record was not found for: $id, $plate_no.");
            return false;
        }
        // get one exit lane info
        $exitLaneRecord=DB::table('psm_lane_config')
            ->select('id','camera_sn')
            ->where('in_out_flag', '1')
            ->get()
            ->first();
        if (empty($exitLaneRecord)) {
            Log::warning("markAsLeave: exit lane was not found.");
            $exitLaneRecord = (object)array(
                'id' => -1,
                'camera_sn' => '00000000-00000000',
            );
        }

        // insert to entry log
        DB::table('psm_entry_log')->insert([
            'lane_id' => $exitLaneRecord->id,
            'camera_sn' => $exitLaneRecord->camera_sn,
            'parking_type' => $carInParkRecord->parking_type,
            'car_color' => '',
            'plate_no' => $plate_no,
            'small_picture' => '',
            'big_picture' => '',
            'qr_sn' => "",
            'qr_code' => "",
            'sync_status' => -1,//wait untill car insite in clear first then only stnc to cloud
            'in_out_flag' => 1,
            'leave_type' => 3, // 3 means mark as leave
            'check_result' => $carInParkRecord->parking_type==0?11:9,
            'kp_user_id' => $carInParkRecord->user_id,
            'create_time' => date("Y-m-d H:i:s")
        ]);
        $lastInsertId = DB::getPdo()->lastInsertId();
        // update the car in site
        $result = DB::table('psm_car_in_site')
            ->where('id', $id)
            ->where('is_left', '0')
            ->update(['exit_id' => $lastInsertId,'is_left' => '1','updated_at' => date("Y-m-d H:i:s")]);

        DB::table('psm_entry_log')
            ->where('id', $lastInsertId)
            ->update(
                [
                    'sync_status' => 0 //to sync with cloud
                ]
            );

        return $result;
    }

    public static function getTotal(){
        $sql = "SELECT count(a.id) as total
            FROM psm_car_in_site a
            WHERE a.is_left=0";
        $result = DB::select($sql);
        return $result[0]->total;
    }

    public static function getTotalAtTime($datetime) {
        $sql = "SELECT count(c.id) as total  FROM (
            SELECT id FROM psm_car_in_site WHERE created_at<='$datetime' and is_left=0
            UNION ALL
            SELECT a.id FROM psm_car_in_site a LEFT JOIN psm_entry_log b ON b.id = a.exit_id WHERE a.created_at<'$datetime' AND b.create_time>='$datetime'
            ) c";
        $result = DB::select($sql);
        return $result[0]->total;
    }
    public static function get_vendor_ticket_id($ticket_id){

        $vendor_ticket_id = self::where('id', $ticket_id)
            ->select('vendor_ticket_id')
            ->orderBy('id','DESC')
            ->first();

        return $vendor_ticket_id["vendor_ticket_id"];
//        dd($vendor_ticket_id->vendor_ticket_id);
//        $vendor_ticket_id = json_encode($vendor_ticket_id);
//        $vendor_ticket_id = json_decode($vendor_ticket_id,true);
//        $vendor_ticket_id = $vendor_ticket_id;
    }


    public static function car_in2($entry_id,$parking_type,$season_card_id,$user_id,$eticket_id,$locked_flag,$auto_deduct_flag,$wallet_balance,$vendor_ticket_id,$plate_no,$plate_no2)
    {
        try {
            $result = DB::table('psm_car_in_site')->insert(
                [
                    'entry_id' => $entry_id,
                    'parking_type' => $parking_type,
                    'season_holder_id' => $season_card_id,
                    'user_id' => $user_id,
                    'eticket_id' => $eticket_id,
                    'locked_flag' => $locked_flag,
                    'auto_deduct_flag' => $auto_deduct_flag,
                    'vendor_ticket_id' => $vendor_ticket_id,
                    'plate_no' => $plate_no,
                    'plate_no2' => $plate_no2,
                    'created_at' => date("Y-m-d H:i:s"),
                    'updated_at' => date("Y-m-d H:i:s"),
                ]
            );
            if ($result!=1) {
                return false;
            }
            return DB::getPdo()->lastInsertId();
        } catch (\Exception $e) {
            Log::error("[car_in2] get exception:$e");
        }
        return false;
    }

    public static function update_eticket_id_to_car_insite($subticket,$car_insite){
        try {
            $result = DB::table('psm_car_in_site')
                ->where('id', $car_insite)
                ->update(
                    [
                        'vendor_ticket_id' => $subticket,
                        'updated_at' => date("Y-m-d H:i:s")
                    ]
                );
            if ($result!=1) {
                return false;
            }
            return $result;
        } catch (\Exception $e) {
            Log::error("[car_in2] get exception:$e");
        }
        return false;
    }


    public static function getUnLeftRecordByPlateNo2($plate_no)
    {
        $record = DB::table('psm_car_in_site')
            ->where(function ($query) use ($plate_no) {
                $query->orWhere('plate_no', $plate_no)
                    ->orWhere('plate_no2', $plate_no);
            })
            ->where('is_left', '0')
            ->first();
        return $record;
    }

    public static function abnoraml_leave2($plate_no,$plate_no2,$leave_type,$check_result)
    {
        // check the record exist or not
        $carInParkRecord = DB::table('psm_car_in_site')
            ->select('id','parking_type','user_id')
            ->where(function ($query) use ($plate_no,$plate_no2) {
                $query->orWhere('plate_no', $plate_no)
                    ->orWhere('plate_no2', $plate_no);
                if (!empty($plate_no2)) {
                    $query->orWhere('plate_no', $plate_no2)
                    ->orWhere('plate_no2', $plate_no2);
                }
            })
            ->where('is_left',0)
            ->get()
            ->first();
        if (empty($carInParkRecord)) {
            Log::warning("abnoraml_leave2: record was not found for:  $plate_no,$plate_no2");
            return false;
        }

        $lane_info=DB::table('psm_lane_config')->select('id','camera_sn')
            ->where('in_out_flag', '1')
            ->get()->sortByDesc('id')->last();

        DB::table('psm_entry_log')->insert(
            [
                'lane_id' => $lane_info->id,
                'camera_sn' => $lane_info->camera_sn,
                'parking_type' => $carInParkRecord->parking_type,
                'car_color' => '',
                'plate_no' => $plate_no,
                'plate_no2' => $plate_no2,
                'small_picture' => '',#get by pos
                'big_picture' => '',
                'qr_sn' => "",
                'qr_code' => "",
                'in_out_flag' => 1,
                'leave_type' => $leave_type,
                'check_result' => $check_result,
                'kp_user_id' => $carInParkRecord->user_id,
                'create_time' => date("Y-m-d H:i:s"),
                'sync_status' => 1,
            ]
        );
        $lastInsertId = DB::getPdo()->lastInsertId();

        DB::table('psm_car_in_site')
            ->where(function ($query) use ($plate_no,$plate_no2) {
                $query->orWhere('plate_no', $plate_no)
                    ->orWhere('plate_no2', $plate_no);
                if (!empty($plate_no2)) {
                    $query->orWhere('plate_no', $plate_no2)
                    ->orWhere('plate_no2', $plate_no2);
                }
            })
            ->where('is_left', '0')
            ->update(
                [
                    'exit_id' => $lastInsertId,
                    'is_left' => '1',
                    'updated_at' => date("Y-m-d H:i:s")
                ]
            );
        //to make sure sync log got the final result
        DB::table('psm_entry_log')
            ->where('id', $lastInsertId)
            ->update(
                [
                    'sync_status' => 0
                ]
            );
        return true;
    }

    public static function car_out2($exit_entry_id,$plate_no,$plate_no2)
    {
        $result = DB::table('psm_car_in_site')
            ->where(function ($query) use ($plate_no,$plate_no2) {
                $query->orWhere('plate_no', $plate_no)
                    ->orWhere('plate_no2', $plate_no);
                if (!empty($plate_no2)) {
                    $query->orWhere('plate_no', $plate_no2)
                    ->orWhere('plate_no2', $plate_no2);
                }
            })
            ->where('is_left', '0')
            ->update(
            [
                'exit_id' => $exit_entry_id,
                'is_left' => '1',
                'updated_at' => date("Y-m-d H:i:s")
            ]
        );
        return $result;
    }

    public static function car_out_with_vendor_ticket($exit_entry_id,$vendor_ticket_id)
    {
        $result = DB::table('psm_car_in_site')
            ->where('vendor_ticket_id', $vendor_ticket_id)
            ->where('is_left', '0')
            ->update(
            [
                'exit_id' => $exit_entry_id,
                'is_left' => '1',
                'updated_at' => date("Y-m-d H:i:s")
            ]
        );
        return $result;
    }




    protected static function getHistoryRecordsWhereCondition($startTime,$endTime,$parkingTypes,
        $entryLanes,$exitLanes,$leaveTypes,$searchContent)
    {
        $where = "WHERE a.created_at>'{$startTime}' AND a.created_at<='{$endTime}' ";
        $inParkingTypes = EntryLog::getInStringFromArray($parkingTypes);
        if (!empty($inParkingTypes)) {
            $where .= " AND a.parking_type IN {$inParkingTypes} ";
        }
        $inEntryLanes = EntryLog::getInStringFromArray($entryLanes);
        if (!empty($inEntryLanes)) {
            $where .= " AND b.lane_id IN {$inEntryLanes} ";
        }
        $inExitLanes = EntryLog::getInStringFromArray($exitLanes);
        if (!empty($inExitLanes)) {
            $where .= " AND c.lane_id IN {$inExitLanes} ";
        }
        $inLeaveTypes = EntryLog::getInStringFromArray($leaveTypes);
        if (!empty($inLeaveTypes)) {
            $where .= " AND c.leave_type IN {$inLeaveTypes} ";
        }
        if (!empty($searchContent)) {
            $where .= " AND (a.plate_no LIKE '%{$searchContent}%' OR a.plate_no2 LIKE '%{$searchContent}%' ) ";
        }
        $where .= " AND a.is_left=1 ";
        return $where;
    }
    /**
     * get history records total account to search conditions
     */
    public static function getHistoryRecordsTotal($startTime,$endTime,$parkingTypes,$entryLanes,$exitLanes,$leaveTypes,$searchContent)
    {
        $sql = "SELECT count(a.id) AS total
            FROM psm_car_in_site a
            LEFT JOIN psm_entry_log as b on b.id=a.entry_id
            LEFT JOIN psm_entry_log as c on c.id=a.exit_id ";
        $sql .=  CarInSite::getHistoryRecordsWhereCondition($startTime,$endTime,$parkingTypes,$entryLanes,$exitLanes,$leaveTypes,$searchContent);
        $result = DB::select($sql);
        return $result[0]->total;
    }
    /**
     * get all history records according to search conditions
     */
    public static function getHistoryRecords($startTime,$endTime,$parkingTypes,$entryLanes,$exitLanes,$leaveTypes,$searchContent,$start,$length,$sort,$order='DESC')
    {
        $sql = "SELECT a.id,a.created_at AS date_time,a.plate_no,a.plate_no2,a.parking_type,
            b.create_time as entry_time,b.lane_id as entry_lane,
            c.create_time as exit_time,c.lane_id as exit_lane,c.leave_type,
            d.method as payment_method,d.amount as payment_amount,d.status as payment_status, d.trx_date as payment_time
            FROM psm_car_in_site a
            LEFT JOIN psm_entry_log as b on b.id=a.entry_id
            LEFT JOIN psm_entry_log as c on c.id=a.exit_id
            LEFT JOIN psm_ticket_payment as d on d.ticket_id=a.id ";
        $sql .=  CarInSite::getHistoryRecordsWhereCondition($startTime,$endTime,$parkingTypes,$entryLanes,$exitLanes,$leaveTypes,$searchContent);
        if (!empty($sort)) {
            $sql .= " ORDER BY {$sort} {$order} ";
        }
        $sql .= "LIMIT {$start},{$length}";
        $result = DB::select($sql);
        return $result;
    }
}
