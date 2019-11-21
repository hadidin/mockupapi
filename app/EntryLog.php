<?php

namespace App;
use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

class EntryLog extends Model
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

    public static function update_vendor_checked_result($logs_id,$result){
        //to make sure checking is finished before reply to snb request
        $ini_array = parse_ini_file("../cron/config.ini",true);
        $vendor_id=$ini_array['common']['VENDOR_ID'];
        if($vendor_id == 'V0004'){
            $result =0;
        }
        DB::table('psm_entry_log')
            ->where('id', $logs_id)
            ->update(
                [
                    'vendor_check_result' => $result
                ]
            );
        return "success";
    }

    public static function update_vendor_checked_result_season($logs_id,$result){
    DB::table('psm_entry_log')
        ->where('id', $logs_id)
        ->update(
            [
                'vendor_check_result' => $result
            ]
        );
    return "success";
    }

    public static function insert_logs($plateInfo,$lane_detail)
    {
//        dd($plateInfo);
        DB::table('psm_entry_log')->insert(
            [
                'lane_id' => $lane_detail["lane_id"],
                'camera_sn' => $plateInfo["cam1"]["camera_id"],
                'car_color' => $plateInfo["cam1"]["car_color"],
                'plate_no' => $plateInfo["cam1"]["plate_no"],
                'small_picture' => $plateInfo["cam1"]["image_frag_path"],
                'big_picture' => $plateInfo["cam1"]["image_path"],
                'plate_no2' => $plateInfo["cam2"]["plate_no"],

                'plate_no1_pre_entry_log_id' => $plateInfo["cam1"]["log_id"],
                'plate_no2_pre_entry_log_id' => $plateInfo["cam2"]["log_id"],

                'qr_sn' => "",
                'qr_code' => "",
                'check_result' => 0,
                'in_out_flag' => $lane_detail["in_out_flag"],
                'create_time' => date("Y-m-d H:i:s")
            ]
        );
        $id = DB::getPdo()->lastInsertId();
        return array('success' => true, 'last_insert_id' => $id);
    }

    public static function insert_logs_manual_push($lane_id,$camera_id,$car_color,$plate_no,$full_img,$small_img,$lane_in_out_flag,$leave_type)
    {
        DB::table('psm_entry_log')->insert(
            [
                'lane_id' => $lane_id,
                'camera_sn' => $camera_id,
                'car_color' => $car_color,
                'plate_no' => $plate_no,
                'small_picture' => $small_img,
                'big_picture' => $full_img,
                'qr_sn' => "",
                'qr_code' => "",
                'check_result' => 0,
                'in_out_flag' => $lane_in_out_flag,
                'create_time' => date("Y-m-d H:i:s"),
                'leave_type' => $leave_type
            ]
        );
        $id = DB::getPdo()->lastInsertId();
        return array('success' => true, 'last_insert_id' => $id);
    }
    public static function get_logs_details($id)
    {
        $logs_details = self::where('id',$id)->first();

        return $logs_details;
    }

    public static function update_logs_to_success($logs_id)
    {
        DB::enableQueryLog();
        DB::table('psm_entry_log')
            ->where('id', $logs_id)
            ->update(
                [
                    'is_success' => '1',
                    'create_time' => date("Y-m-d H:i:s")
                ]
            );
        return "success";
    }

    public static function update_logs_to_not_season_pass_user($logs_id){
        DB::enableQueryLog();
        DB::table('psm_entry_log')
            ->where('id', $logs_id)
            ->update(
                [
                    'is_season_subscriber' => '0',
                    'create_time' => date("Y-m-d H:i:s")
                ]
            );
        return "success";
    }

    public static function update_date_sync_to_cloud($logs_id)
    {
        DB::enableQueryLog();
        DB::table('psm_entry_log')
            ->where('id', $logs_id)
            ->update(
                [
                    'datetime_sync_kp_cloud' => date("Y-m-d H:i:s")
                ]
            );
        return "success";
    }
    public function get_logs_list(){
        DB::enableQueryLog();
        $get_logs_list = self::where('datetime_sync_kp_cloud',null)->get();
//        dd(DB::getQueryLog());
        return $get_logs_list;
    }

    public static function update_failed_reason($logs_id,$remark)
    {
        DB::enableQueryLog();
        DB::table('psm_entry_log')
            ->where('id', $logs_id)
            ->update(
                [
                    'failed_remark' => $remark
                ]
            );
        return "success";
    }


    public static function total_car_db($lane_to_be_view,$review_flag,$plate_search, $season_only, $from, $to)
    {
      

        $total_car_details = DB::table('psm_entry_log AS a')
        ->join('psm_lane_config AS b', 'a.lane_id', '=', 'b.id')
        ->select(
            'a.*','b.name as lane_name'
        ) 
        ->whereIn('a.lane_id',$lane_to_be_view)
        ->where('a.plate_no', 'like', '%' . $plate_search . '%')
        ->whereIn('a.is_season_subscriber',$season_only)
        ->whereBetween('a.create_time', [$from, $to])
        ->whereIn('a.review_flag',$review_flag); 

        return $total_car_details;

    }

    public static function summary_car_db($lane_to_be_view,$review_flag,$plate_search, $season_only, $from, $to)
    {

        $total_car_details = DB::table('psm_entry_log AS a')
        ->select(DB::raw('a.review_flag as flag, count(a.review_flag) as total'))
        ->join('psm_lane_config AS b', 'a.lane_id', '=', 'b.id') 
        ->whereIn('a.lane_id',$lane_to_be_view)
        ->where('a.plate_no', 'like', '%' . $plate_search . '%')
        ->whereIn('a.is_season_subscriber',$season_only)
        ->whereBetween('a.create_time', [$from, $to])
        ->whereIn('a.review_flag',$review_flag);
        
        return $total_car_details;

    }
    public static function total_car_season_park($from, $to)
    {
        $total_car_details = DB::table('psm_car_in_site AS a')
        ->join('psm_entry_log AS b', 'b.id', '=', 'a.entry_id')
        ->join('psm_lane_config AS c', 'c.id', '=', 'b.lane_id')
        ->join('psm_entry_log AS d', 'd.id', '=', 'a.exit_id') 
        ->join('psm_lane_config AS e', 'e.id', '=', 'd.lane_id')
        ->leftjoin('psm_ticket_payment AS f', 'f.ticket_id', '=', 'a.id')
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
                'd.id as exit_logs_id',
                'd.create_time as exit_logs_time',
                'd.small_picture as pic1_exit',
                'd.big_picture as pic2_exit',
                'd.lane_id as exit_lane_id',
                'd.leave_type',
                'c.name as entry_lane_name',
                'e.name as exit_lane_name',
                'f.amount',
                'f.external_ref_id as kiplepay_trx_id',
                'f.status as payment_status',
                'f.method',
                'f.trx_date as payment_time'
                )
                ->where('a.is_left', '1')
//                ->where('a.id', 'f.ticket_id')
                ->whereBetween('a.created_at', [$from, $to]);

//        log::info("aaaaaaaaa".json_decode(json_encode($total_car_details),true));

//        dd($total_car_details);


        return $total_car_details;
    }
 
    public static function total_car_in_reviewed($lane_to_be_view, $review_flag, $season_only, $from, $to)
    {
        $total_car_details = DB::table('psm_entry_log AS a')
        ->join('psm_lane_config AS b', 'a.lane_id', '=', 'b.id')
        ->select(
            'a.*','b.name as lane_name'
        )
        ->whereIn('a.lane_id',$lane_to_be_view)
        ->whereIn('a.review_flag',$review_flag)
        ->where('a.review_flag','<>' ,0)
        ->whereIn('a.is_season_subscriber',$season_only)
        ->whereBetween('a.create_time', [$from, $to]);


        return $total_car_details;

    }

    public static function statistic_entry_log()
    {
        $total_car_details = DB::table('psm_entry_log AS a')
        ->join('psm_lane_config AS b', 'a.lane_id', '=', 'b.id')
        ->select(
            'a.*','b.name as lane_name'
        );

        return $total_car_details;

    }

    public static function lpr_logic_code_to_check_result($lpr_logic_code){
        if (empty($lpr_logic_code)) {
            return 0;
        }
        // 'LS010' => 10
        return intval(substr($lpr_logic_code,2));
    }

    public static function update_checked_result($logs_id,$lpr_logic_code,$user_id,$locked_flag,$auto_deduct_flag,$wallet_balance,$parking_type,$lpr_operation,$remark,$is_season_subcriber){

        DB::enableQueryLog();
        $check_result = self::lpr_logic_code_to_check_result($lpr_logic_code);
        if($wallet_balance != null){
            $wallet_balance= $wallet_balance * 100;
        }
        DB::table('psm_entry_log')
            ->where('id', $logs_id)
            ->update(
                [
                    'check_result' => $check_result,
                    'kp_user_id' => $user_id,
                    'kp_locked_flag' => $locked_flag,
                    'kp_auto_deduct_flag' => $auto_deduct_flag,
                    'kp_wallet_balance' => $wallet_balance,
                    'parking_type' => $parking_type,
                    'failed_remark' => $remark,
                    'is_season_subscriber' => $is_season_subcriber
                ]
            );
        return "success";
    }

    public static function update_vendor_ticket_id_to_entry_logs($log_id,$eticket)
    {
        DB::enableQueryLog();
        DB::table('psm_entry_log')
            ->where('id', $log_id)
            ->update(
                [
                    'vendor_ticket_id' => substr($eticket,2)
                ]
            );
        return "success";
    }
    public static function update_secondary_season_plate_to_visitor($log_id,$plate_no)
    {
        DB::enableQueryLog();
        DB::table('psm_entry_log')
            ->where('id', $log_id)
            ->update(
                [
                    'previous_season_entry' => $plate_no
                ]
            );
        return "success";
    }

    public static function getLastEntryLog($cameraSN,$startTime,$endTime) {
        DB::enableQueryLog();
        $record = DB::table('psm_entry_log')
            ->select('id','camera_sn','plate_no','big_picture','kp_user_id','check_result')
            ->whereBetween('create_time', [$startTime, $endTime])
            ->where('camera_sn', $cameraSN)
            ->first();
        return $record;
    }

    public static function update_season_checked_result($lpr_logic,$logs_id){
        DB::enableQueryLog();
//        dd($lpr_logic);

	$season_check_result = -1;
        //entry
        if($lpr_logic == 'LS012'){$season_check_result = 0;}//_NONE_
        if($lpr_logic == 'LS003'){$season_check_result = 2;}//expired
        if($lpr_logic == 'LS021'){$season_check_result = 3;}//season secondary entry
        if($lpr_logic == 'LS004'){$season_check_result = 4;}//used/reeated entry
        if($lpr_logic == 'LS005'){$season_check_result = 5;}//inactive
        if($lpr_logic == 'LS006'){$season_check_result = 1;}//success
        if($lpr_logic == 'LS010'){$season_check_result = -1;}//will update on vendor checking
        if($lpr_logic == 'LS020'){$season_check_result = 6;}//outside access category

        //exit
        if($lpr_logic == 'LS009'){$season_check_result = 13;}//success
        if($lpr_logic == 'LS007'){$season_check_result = 11;}//repeated exit
        if($lpr_logic == 'LS008'){$season_check_result = 12;}//carLocked
        if($lpr_logic == 'LS013'){$season_check_result = 10;}//_NONE_
        if($lpr_logic == 99){$season_check_result = -1;}//will update on vendor checking


        DB::table('psm_entry_log')
            ->where('id', (integer)$logs_id)
            ->update(
                [
                    'season_check_result' => $season_check_result
                ]
            );
        #dd(DB::getQueryLog());
        return "success";
    }

    public static function update_visitor_checked_result($vendor_result,$logs_id){
        DB::enableQueryLog();

        $ini_array = parse_ini_file("../cron/config.ini",true);
        $vendor_id = $ini_array['common']['VENDOR_ID'];
        if($vendor_id == 'V0004'){
            //for snb will update on tcp swoole
            $visitor_check_result = -1;
            $lpr_logic = "";
        }
        else{
            $lpr_logic =$vendor_result["check_result"];
        }

	$visitor_check_result = -1;
        //entry
        if($lpr_logic == 'LS019'){$visitor_check_result = 2;}//duplicate TD respnse
        if($lpr_logic == 'LS010'){$visitor_check_result = 1;}//success entry
        #if($lpr_logic == 'LS012'){$visitor_check_result = 0;}//_NONE_
        //exit
        #if($lpr_logic == 'LS013'){$visitor_check_result = 10;}//_NONE_
        if($lpr_logic == 'LS011'){$visitor_check_result = 14;}//success exit
        if($lpr_logic == 'LS015'){$visitor_check_result = 11;}//unpaid
        if($lpr_logic == 'LS016'){$visitor_check_result = 13;}//expired/exceed gp
        if($lpr_logic == 'LS017'){$visitor_check_result = 12;}//ticket used
        if($lpr_logic == 'LS018'){$visitor_check_result = 15;}//system error
        if($lpr_logic == 'LS014'){$visitor_check_result = 14;}//ticket used

        //new logic to handle manual exit integration with vendor
        if($lpr_logic == 'LS022'){$visitor_check_result = 16;}//TZ00000000 = Command Rejected – No vehicle detected. Barrier is NOT raised
        if($lpr_logic == 'LS023'){$visitor_check_result = 17;}//TFxxxxxxxx = Ticket not Found.

        //to handle communication error with maxpark.if we got tcp connection error
        if($lpr_logic == 'LS024'){$visitor_check_result = 18;}

        DB::table('psm_entry_log')
            ->where('id', (integer)$logs_id)
            ->update(
                [
                    'visitor_check_result' => $visitor_check_result
                ]
            );
        #dd(DB::getQueryLog());
        return "success";
    }

    public static function last_exit($lane_id){
        DB::enableQueryLog();
        $last_exit = DB::table('psm_entry_log AS a')
            ->join('psm_lane_config AS b', 'a.lane_id', '=', 'b.id')
            ->select(
                'a.id',
		'a.lane_id',
		'b.name AS lane_name',
                'a.plate_no',
                'a.big_picture',
                'a.check_result',
                'a.season_check_result',
                'a.visitor_check_result',
                'a.create_time',
                'a.leave_type'
            )
            ->where('a.lane_id', $lane_id)
            ->where('a.in_out_flag', 1)
            ->orderBy('a.id','desc')
            ->first();
        $last_exit = (array)$last_exit;
        $last_entry = array();
	
	if($last_exit){
	    //get entry
	    $last_entry = DB::table('psm_car_in_site AS a')
		    ->leftjoin('psm_entry_log AS b','b.id', '=', 'a.entry_id')
		    ->leftjoin('psm_lane_config AS c','c.id', '=','b.lane_id')
		    ->select('b.id','b.lane_id','c.name AS lane_name','b.plate_no','b.big_picture','b.check_result','b.season_check_result',
			    'b.visitor_check_result','b.create_time','b.previous_season_entry','a.season_holder_id','a.vendor_ticket_id','a.is_left','a.exit_id')
		     ->where('a.plate_no',$last_exit['plate_no'])
                     ->where('b.id', '<', $last_exit['id'])

		    // ->where('a.is_left',0)
		    ->orderBy('a.id','desc')
		    ->first();
            if (!empty($last_entry)) {
                $last_entry = (array)$last_entry;
                // we found the matched entry record, but this entry record already matched another exit, 
		// (exit1 unpaid->paid->then out exit2, so both exit1 and exit2 should match same entry record)
		// so set the entry record to empty
                if ($last_entry['is_left']==1 && $last_entry['exit_id']<$last_exit['id']) {
                    $last_entry = array();
                }
            }	    
        }
        #dd(DB::getQueryLog());

        $records = array(
                            'exit_record' => $last_exit,
                            'entry_record' => $last_entry
                        );
        return $records;

    }


    /**
     * return the season/visitor entry/exit hourly summary report
     */
    public static function hourlyReport($startTime,$endTime) {
        // check result
        // 6 : season entry, 9 : season exit
        // 10 : visitor entry , 11 : visitor exit
        $sql = "SELECT DATE_FORMAT(create_time, '%H') AS dt,SUM(if(check_result=6,1,0)) as season_entry,
            SUM(if(check_result=10,1,0)) as visitor_entry,SUM(if(check_result=9,1,0)) as season_exit,
            SUM(if(check_result=11,1,0)) as visitor_exit
            FROM psm_entry_log 
            WHERE create_time>='$startTime' and create_time <'$endTime' AND check_result in (6,9,10,11)
            GROUP BY dt";
        $result = DB::select(DB::raw($sql));
        return $result;
    }

    public static function last_entry_logs_detail($entry_log_id){
        DB::enableQueryLog();
        $last_exit = DB::table('psm_entry_log')
            ->select('*')
            ->where('id',$entry_log_id)
            ->first();
        return $last_exit;
    }

    public static function update_leave_type($log_id,$leave_type)
    {
        DB::enableQueryLog();
        DB::table('psm_entry_log')
            ->where('id', $log_id)
            ->update(
                [
                    'leave_type' => $leave_type
                ]
            );
        return "success";
    }
    public static function update_manual_leave_by($user_id,$log_id){
        DB::enableQueryLog();
        DB::table('psm_entry_log')
            ->where('id', $log_id)
            ->update(
                [
                    'manual_leave_by' => $user_id
                ]
            );
        return "success";
    }

    public static function getDailyEfficiency($startTime,$endTime,$cutOffTime){
        // check result
        // 9 : season exit, 11 : visitor exit
        // leave type
        // 0:normal, 1: manual,2:force,3: mark
        $sql = "SELECT DATE_FORMAT(ADDTIME(create_time,'-{$cutOffTime}'),'%m-%d') AS dt, 
            SUM(IF(leave_type=0,1,0)) AS normal_leave, 
            SUM(IF(leave_type=1,1,0)) AS manual_leave,
            SUM(IF(leave_type=2,1,0)) AS force_leave,
            SUM(IF(leave_type=3,1,0)) AS mark_leave,
            COUNT(id) AS total_leave
            FROM psm_entry_log 
            WHERE create_time>='{$startTime}' AND create_time<'{$endTime}' AND check_result IN (9,11)
            GROUP BY dt";
        $result = DB::select(DB::raw($sql));
        return $result;        
    }

    protected static function getInStringFromArray($records,$isString=false) {
        if (empty($records)) {
            return '';
        }
        $str = '(';
        foreach($records as $record) {
            if ($isString) {
                $str .= "'{$record}',";
            } else {
                $str .= "{$record},";
            }
        }
        $str = substr($str,0,strlen($str)-1);
        $str .= ')';
        return $str;
    } 

    protected static function getRecordsWhereCondition($startTime,$endTime,$parkingTypes,$lanes,$reviewFlags,$leaveTypes,$searchContent){
        $where = "WHERE create_time>'{$startTime}' AND create_time<='{$endTime}' ";
        $inParkingTypes = EntryLog::getInStringFromArray($parkingTypes);
        if (!empty($inParkingTypes)) {
            $where .= " AND parking_type IN {$inParkingTypes} ";
        }
        $inLanes = EntryLog::getInStringFromArray($lanes);
        if (!empty($inLanes)) {
            $where .= " AND lane_id IN {$inLanes} ";
        }
        $inReviewFlags = EntryLog::getInStringFromArray($reviewFlags);
        if (!empty($inReviewFlags)) {
            $where .= " AND review_flag IN {$inReviewFlags} ";
        }
        $inLeaveTypes = EntryLog::getInStringFromArray($leaveTypes);
        if (!empty($inLeaveTypes)) {
            $where .= " AND leave_type IN {$inLeaveTypes} ";
        }
        if (!empty($searchContent)) {
            $where .= " AND (plate_no LIKE '%{$searchContent}%' OR plate_no2 LIKE '%{$searchContent}%' OR plate_no_reviewed LIKE '%{$searchContent}%' ) ";
        }
        return $where;
    }
    /**
     * get records total account to search conditions
     */
    public static function getRecordsTotal($startTime,$endTime,$parkingTypes,$lanes,$reviewFlags,$leaveTypes,$searchContent){
        $sql = "SELECT count(id) AS total
            FROM psm_entry_log ";
        $sql .=  EntryLog::getRecordsWhereCondition($startTime,$endTime,$parkingTypes,$lanes,$reviewFlags,$leaveTypes,$searchContent);
        $result = DB::select($sql);
        return $result[0]->total;        
    }
    /**
     * get all records according to search conditions
     */
    public static function getRecords($startTime,$endTime,$parkingTypes,$lanes,$reviewFlags,$leaveTypes,$searchContent,$start,$length,$sort,$order='DESC') {
        $sql = "SELECT id,create_time AS date_time,plate_no,plate_no2,lane_id,parking_type,plate_no_reviewed,small_picture,big_picture,review_flag,check_result,
            white_list_check_result,season_check_result,visitor_check_result,leave_type,kp_user_id
            FROM psm_entry_log ";
        $sql .=  EntryLog::getRecordsWhereCondition($startTime,$endTime,$parkingTypes,$lanes,$reviewFlags,$leaveTypes,$searchContent);
        if (!empty($sort)) {
            $sql .= " ORDER BY {$sort} {$order} ";
        }
        $sql .= "LIMIT {$start},{$length}";        
        $result = DB::select($sql);
        return $result;
    }
    /**
     * get review summary according to search conditions
     */
    public static function getReviewSummary($startTime,$endTime,$parkingTypes,$lanes,$reviewFlags,$leaveTypes,$searchContent) {
        $sql = "SELECT COUNT(id) AS total, SUM(IF(review_flag=0,1,0)) AS unreviewed,
            SUM(IF(review_flag=1,1,0)) AS correct,SUM(IF(review_flag=2,1,0)) AS wrong,
            SUM(IF(review_flag=3,1,0)) AS undetected, SUM(IF(review_flag=4,1,0)) AS trigger_wrong,
            SUM(IF(review_flag=5,1,0)) AS invalid
            FROM psm_entry_log ";
        $sql .=  EntryLog::getRecordsWhereCondition($startTime,$endTime,$parkingTypes,$lanes,$reviewFlags,$leaveTypes,$searchContent);
        $result = DB::select($sql);
        return $result[0];
    } 
    /**
     * get efficiency summary according to search conditions
     */
    public static function getEfficiencySummary($startTime,$endTime,$parkingTypes,$lanes,$reviewFlags,$leaveTypes,$searchContent) {
        $sql = "SELECT COUNT(id) AS total, SUM(IF(leave_type=0,1,0)) AS auto_leave,
            SUM(IF(leave_type=1,1,0)) AS manual_leave,SUM(IF(leave_type=2,1,0)) AS forced_leave,
            SUM(IF(leave_type=3,1,0)) AS marked_leave
            FROM psm_entry_log ";
        $sql .=  EntryLog::getRecordsWhereCondition($startTime,$endTime,$parkingTypes,$lanes,$reviewFlags,$leaveTypes,$searchContent);
        $sql .= ' AND check_result IN (9,11)';
        $result = DB::select($sql);
        return $result[0];
    }           
    /**
     * update the entry log record 
     */
    public static function updateRecordField($id,$field,$value) {
        $result = DB::table('psm_entry_log')
            ->where('id', $id)
            ->update([ $field => $value]);
        return $result;
    }


    public static function insert_log($plate_info,$plate_info2,$lane_info)
    {
        try {
            $result = DB::table('psm_entry_log')->insert(
                [
                    'lane_id' => $lane_info["lane_id"],
                    'camera_sn' => $plate_info["camera_sn"],
                    'car_color' => $plate_info["car_color"],
                    'plate_no' => $plate_info["plate_no"],
                    'small_picture' => $plate_info["small_picture"],
                    'big_picture' => $plate_info["big_picture"],
                    'plate_no2' => isset($plate_info2["plate_no"])?$plate_info2["plate_no"]:'',
    
                    'plate_no1_pre_entry_log_id' => $plate_info["pre_entry_id"],
                    'plate_no2_pre_entry_log_id' => isset($plate_info2["pre_entry_id"])?$plate_info2["pre_entry_id"]:-1,
    
                    'qr_sn' => "",
                    'qr_code' => "",
                    'parking_type' => 0,
                    'check_result' => 0,
                    'is_season_subscriber'=>0,
                    'in_out_flag' => $lane_info["in_out_flag"],                    
                    'create_time' => date("Y-m-d H:i:s")                
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

    public static function update_checked_result2($id,$check_result,$parking_type,$remark,
        $vendor_ticket_id,$user_id,$locked_flag,$auto_deduct_flag,$wallet_balance,$field,$value)
    {
        $updates = array(
            'check_result' => $check_result,
            'parking_type' => $parking_type,
            'failed_remark' => $remark,
            'vendor_ticket_id' => $vendor_ticket_id,                 
        );
        if (!empty($field)) {
            $updates[$field] = $value;
        }
        if (!empty($user_id)) {
            $updates['kp_user_id'] = $user_id;
        }
        if (!empty($locked_flag)) {
            $updates['kp_locked_flag'] = $locked_flag;
        }
        if (!empty($auto_deduct_flag)) {
            $updates['kp_auto_deduct_flag'] = $auto_deduct_flag;
        }
        if (!empty($wallet_balance)) {
            $updates['kp_wallet_balance'] = $wallet_balance;
        }
        $result = DB::table('psm_entry_log')
            ->where('id', $id)
            ->update( $updates );
        return $result;
    }

    public static function create_manual_leave($plate_info,$lane_info,$manual_leave_by,$manual_leave_remark)
    {
        $result = DB::table('psm_entry_log')->insert(
            [
                'lane_id' => $lane_info['lane_id'],
                'camera_sn' => $plate_info['camera_sn'],
                'car_color' => $plate_info['car_color'],
                'plate_no' => $plate_info['plate_no'],
                'small_picture' => $plate_info['small_picture'],
                'big_picture' => $plate_info['big_picture'],
                'qr_sn' => "",
                'qr_code' => "",
                'check_result' => 0,
                'leave_type' => 1,
                'manual_leave_by' => $manual_leave_by,
                'manual_leave_remark' => $manual_leave_remark,
                'in_out_flag' => $lane_info['in_out_flag'],
                'create_time' => date("Y-m-d H:i:s")
            ]
        );
        if ($result==1) {
            return DB::getPdo()->lastInsertId(); 
        }
        return false;
    }    

    /**
     * get manual leave summary conditions
     */
    public static function getManualLeaveSummary($startTime,$endTime) {
        $sql = "SELECT manual_leave_remark, COUNT(*) AS number
            FROM psm_entry_log
            WHERE create_time>='$startTime' AND create_time<='$endTime' AND leave_type = 1
            GROUP BY manual_leave_remark";
        $result = DB::select($sql);
        return $result;
    }         
}
