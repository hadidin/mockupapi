<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Matrix\Exception;

class localagentController extends Controller
{
    protected function fixPlateNo($old) {
        $fixArray = array(
            // for F88
            'VV1108' => 'VY1108',
            'WC113' => 'WC113D',
            'WRP111' => 'WRP1111',
             // for Parcel C
            'QM011' => 'QMQ111',
            'QM0111' => 'QMQ111', 
            '0M01' => 'QMQ111',
            '0M011' => 'QMQ111',
            'BME111' => 'BME1113',
            'BME11' => 'BME1113',
        );
        if (isset($fixArray[$old])) {
            return $fixArray[$old];
        }
        return false;
    }
    public function pre_push_plate_no(Request $request){
        log::info("[pre_push_plate_no] = $request");

        $body2=file_get_contents('php://input');
        $json_body2=json_decode($body2,true);
        $plate_no=$json_body2["body"]["result"]["PlateResult"]["license"];

        //check initial plate char
        $plate_no = self::check_initial_plate_number($plate_no);

        //fix to desired plate number for special case plate number that we failed to read
        $fixed_plate_no = \App\FixPlateNo::getDesirePlateNo($plate_no);
        if ($fixed_plate_no) {
            Log::warning("Change plate no from $plate_no to $fixed_plate_no");
            $plate_no = $fixed_plate_no;
        }
        $camera_id=$json_body2["body"]["vzid"]["sn"];
        $car_color=$json_body2["body"]["result"]["PlateResult"]["carColor"];
        $image_path=$json_body2["body"]["result"]["PlateResult"]["imageFilePath"];#big
        $image_frag_path=$json_body2["body"]["result"]["PlateResult"]["imageFragmentFilePath"];#small
        $lpr_id=$json_body2["id"];

        //get cam detail
        $camera_detail= \App\CameraDetail::getCameraDetail($camera_id);
        $camera_detail = json_decode(json_encode($camera_detail),true);
        #$lane_parking_type_flag=$lane_detail["parking_type_flag"];
        $lane_in_out_flag=$camera_detail["in_out_flag"];
        $lane_id=$camera_detail["lane_id"];

        $laneInfo['in_out_flag'] = $lane_in_out_flag;
        $laneInfo['lane_id'] = $lane_id;
        $laneInfo['parking_type_flag'] = $camera_detail['parking_type_flag'];
        $laneInfo['ext_cam_ref_id'] = $camera_detail['ext_cam_ref_id'];

        //save to pre_entry_log
        $logs_id = \App\PreEntryLog::insert_logs($lane_id,$camera_id,$car_color,$plate_no,$image_path,$image_frag_path,$lane_in_out_flag,$lpr_id);

        //check how many camera for this entry/exit
        $total_cam = \App\CameraDetail::camera_count($lane_id);
        if($total_cam == 1){
            $plateInfo["record_to_process"] = 1;
            //declare the plate no from first cam
            $plateInfo['cam1']['log_id'] = $logs_id["last_insert_id"];
            $plateInfo['cam1']['plate_no'] = $plate_no;
            $plateInfo['cam1']['camera_id'] = $camera_id;
            $plateInfo['cam1']['car_color'] = $car_color;
            $plateInfo['cam1']['image_path'] = $image_path;
            $plateInfo['cam1']['image_frag_path'] = $image_frag_path;
            $plateInfo['cam1']['lpr_id'] = $lpr_id;

            $plateInfo['cam2']['log_id'] = -1;
            $plateInfo['cam2']['plate_no'] = "";
            $plateInfo['cam2']['camera_id'] = "";
            $plateInfo['cam2']['car_color'] = "";
            $plateInfo['cam2']['image_path'] = "";
            $plateInfo['cam2']['image_frag_path'] = "";
            $plateInfo['cam2']['lpr_id'] = "";

            //sent to push plate no
            return self::push_plate_no($plateInfo,$laneInfo);
            \App\PreEntryLog::update_processing_flag($logs_id["last_insert_id"],1);

        }

        if($total_cam == 2){
           $plate_to_be_sent = $this->process_dual_pre_push($lane_id,$plate_no);

           //first attempt sent plate no by camera 1.
           if($plate_to_be_sent["record_to_process"] == 1){
               //check whether those record already sent by 2nd camera thread
               $record = \App\PreEntryLog::get_record($lane_id);
               #dd($record,$plate_to_be_sent);
               if(isset($record[0])){
                   //meaning that second request is not coming from another camera
                   log::warning("[$plate_no] request is sent by the first thread = $camera_id, log_id = $logs_id[last_insert_id], expecting 2nd thread from 2nd camera but none receive");
                   \App\PreEntryLog::update_processing_flag($plate_to_be_sent["cam1"]["log_id"],1);
                   return self::push_plate_no($plate_to_be_sent,$laneInfo);
               }
           }
           //receive both attempt by the camera for the lane
            if($plate_to_be_sent["record_to_process"] == 2){
                \App\PreEntryLog::update_processing_flag($plate_to_be_sent["cam1"]["log_id"],1);
                \App\PreEntryLog::update_processing_flag($plate_to_be_sent["cam2"]["log_id"],1);
                return self::push_plate_no($plate_to_be_sent,$laneInfo);
            }
        }
    }


    function process_dual_pre_push($lane_id,$plate_no) {
        $start_time = time();
        $record = \App\PreEntryLog::get_record($lane_id);
        //it is not break the process flow

        while(true) {
            try{
                if ((time() - $start_time) > 3) {

                    $plateInfo["record_to_process"] = 1;
                    //first camera
                    $plateInfo['cam1']['log_id'] = $record[0]->id;
                    $plateInfo['cam1']['plate_no'] = $record[0]->plate_no;
                    $plateInfo['cam1']['camera_id'] = $record[0]->camera_sn;
                    $plateInfo['cam1']['car_color'] = $record[0]->car_color;
                    $plateInfo['cam1']['image_path'] = $record[0]->small_picture;
                    $plateInfo['cam1']['image_frag_path'] = $record[0]->big_picture;
                    $plateInfo['cam1']['lpr_id'] = $record[0]->lpr_id;

                    //2nd camera
                    $plateInfo['cam2']['log_id'] = -1;
                    $plateInfo['cam2']['plate_no'] = "";
                    $plateInfo['cam2']['camera_id'] = "";
                    $plateInfo['cam2']['car_color'] = "";
                    $plateInfo['cam2']['image_path'] = "";
                    $plateInfo['cam2']['image_frag_path'] = "";
                    $plateInfo['cam2']['lpr_id'] = "";

                    return $plateInfo; // timeout, function took longer than 3 seconds
                }
                // Other processing
                $record = \App\PreEntryLog::get_record($lane_id);
                if(count($record) >= 2){
                    $plateInfo["record_to_process"] = 2;

                    //first camera
                    $plateInfo['cam1']['log_id'] = $record[0]->id;
                    $plateInfo['cam1']['plate_no'] = $record[0]->plate_no;
                    $plateInfo['cam1']['camera_id'] = $record[0]->camera_sn;
                    $plateInfo['cam1']['car_color'] = $record[0]->car_color;
                    $plateInfo['cam1']['image_path'] = $record[0]->small_picture;
                    $plateInfo['cam1']['image_frag_path'] = $record[0]->big_picture;
                    $plateInfo['cam1']['lpr_id'] = $record[0]->lpr_id;

                    //2nd camera
                    $plateInfo['cam2']['log_id'] = $record[1]->id;
                    $plateInfo['cam2']['plate_no'] = $record[1]->plate_no;
                    $plateInfo['cam2']['camera_id'] = $record[1]->camera_sn;
                    $plateInfo['cam2']['car_color'] = $record[1]->car_color;
                    $plateInfo['cam2']['image_path'] = $record[1]->small_picture;
                    $plateInfo['cam2']['image_frag_path'] = $record[1]->big_picture;
                    $plateInfo['cam2']['lpr_id'] = $record[1]->lpr_id;
                    return $plateInfo;
                }
            }
            catch (\Exception $e){
                log::notice("[$plate_no] request to push plate no already sent by seconds camera request");
                return false;
            }
        }
    }

//    public function push_plate_no($plate_no,$camera_id,$car_color,$image_path,$image_frag_path,$lpr_id,$plate_no2,$lane_detail){
      public function push_plate_no($plateInfo,$lane_detail){

        if($plateInfo["record_to_process"] == 2){
            //check plate number same, then set second camera to default
            if($plateInfo["cam1"]["plate_no"] == $plateInfo["cam2"]["plate_no"]){
                $plateInfo = self::set_single_plate_check_from_dual($plateInfo,"cam1");
            }
            if($plateInfo["cam1"]["plate_no"] == '_NONE_' && $plateInfo["cam2"]["plate_no"] != "_NONE_"){
                $plateInfo = self::set_single_plate_check_from_dual($plateInfo,"cam2");
            }
            if($plateInfo["cam2"]["plate_no"] == '_NONE_' && $plateInfo["cam1"]["plate_no"] != "_NONE_"){
                $plateInfo = self::set_single_plate_check_from_dual($plateInfo,"cam1");
            }
        }



//          dd($plateInfo,$lane_detail,"asassa");

        log::info("START... plate push =",[$plateInfo,$lane_detail]);
//dd($plateInfo,$lane_detail);
        #############################################
        ##check lane details (lane(camera) detail?)##
        #############################################
        $lane_parking_type_flag=$lane_detail["parking_type_flag"];
        $lane_in_out_flag=$lane_detail["in_out_flag"];
        $lane_id=$lane_detail["lane_id"];
        $ext_cam_id=$lane_detail["ext_cam_ref_id"];

        ##########################
        ##update entry_log table##
        ##########################
        $entry_logs = \App\EntryLog::insert_logs($plateInfo,$lane_detail);

        $logs_id=$entry_logs["last_insert_id"];





        #####################################
        ##get season card based on plate no##
        #####################################
        $seasonInfo= \App\SmcHolderInfo::getDualHolderInfo($plateInfo);

//        dd($plateInfo,$seasonInfo,$lane_detail);


        // this is a entry
        if($lane_detail['in_out_flag'] == 0){
            // try to get binded kiple park user info;
            $bindedUserInfo = \App\SmcHolderInfo::getBindedUserInfo_v2($plateInfo);
            $entrance=self::entry_request($plateInfo,$lane_detail,$seasonInfo,$bindedUserInfo,$logs_id);
            return $entrance;
        }

        #############################
        ##Car Exit request.........##
        #############################
        if($lane_in_out_flag==1){
            ###this is exit
            $carInSiteInfo = \App\CarInSite::check_car_in_site_by_plate_number($plate_no);
            $exit=self::exit_request($plateInfo,$lane_detail,$seasonInfo,$carInSiteInfo,$logs_id);
            return $exit;
        }
        #############################
        ##Car nesting entry request##
        #############################
        if($lane_in_out_flag==2){
            ###this is nesting entry
            dd("feature not ready");
        }
        #############################
        ##Car nesting exit request###
        #############################
        if($lane_in_out_flag==3){
            ###this is nesting exit
            dd("feature not ready");
        }
    }//function push_plate_no(Request $request) end

    //manual push plate number
    public function manual_push_plate_no(Request $request){

        Log::info("Manual Exit:Request receive body");
        $entry_log_id = $request->entry_log_id;
        $plate_no = $request->plate_no;

        $previous_entry_record = \App\EntryLog::last_entry_logs_detail($entry_log_id);
        $previous_entry_record = json_decode(json_encode($previous_entry_record),true);

        $camera_id=$previous_entry_record["camera_sn"];
        $car_color=$previous_entry_record["car_color"];
        $image_path=$previous_entry_record["big_picture"];#big
        $image_frag_path=$previous_entry_record["small_picture"];#small
        $lpr_id="";
        $plateInfo = array();
        $plateInfo['plate_no'] = $plate_no;
        $plateInfo['camera_id'] = $camera_id;
        $plateInfo['car_color'] = $car_color;
        $plateInfo['image_path'] = $image_path;
        $plateInfo['image_frag_path'] = $image_frag_path;
        $plateInfo['lpr_id'] = $lpr_id;
        log::info("START... plateno = $plate_no");


        #############################################
        ##check lane details (lane(camera) detail?)##
        #############################################
        $lane_detail= \App\LaneConfig::getLaneDetail($camera_id);
        $lane_detail = $lane_detail->toArray();
        $lane_parking_type_flag=$lane_detail["parking_type_flag"];
        $lane_in_out_flag=$lane_detail["in_out_flag"];
        $lane_id=$lane_detail["id"];
        $ext_cam_id=$lane_detail["ext_cam_ref_id"];

        ##########################
        ##update entry_log table##
        ##########################
        $entry_logs=\App\EntryLog::insert_logs_manual_push($lane_id,$camera_id,$car_color,$plate_no,$image_path,$image_frag_path,$lane_in_out_flag,1);
        $logs_id=$entry_logs["last_insert_id"];

        #####################################
        ##get season card based on plate no##
        #####################################
        $seasonInfo= \App\SmcHolderInfo::getHolderInfo($plate_no);

        #############################
        ##Car Exit request.........##
        #############################
        if($lane_in_out_flag==1){
            ###this is exit
            $carInSiteInfo = \App\CarInSite::check_car_in_site_by_plate_number($plate_no);
            $exit=self::manual_exit_request($plateInfo,$lane_detail,$seasonInfo,$carInSiteInfo,$logs_id);
            return $exit;
        }
        #############################
        ##Car nesting entry request##
        #############################
        if($lane_in_out_flag==2){
            ###this is nesting entry
            dd("feature not ready");
        }
        #############################
        ##Car nesting exit request###
        #############################
        if($lane_in_out_flag==3){
            ###this is nesting exit
            dd("feature not ready");
        }
    }//function push_plate_no(Request $request) end

    protected function entry_request($plateInfo,$laneInfo,$seasonInfo,$bindedUserInfo,$logs_id){
//        $plate_no = $plateInfo['plate_no'];
//        log::info("Request ENTRY--------- at lane= {$laneInfo['lane_id']}, {$laneInfo['in_out_flag']}");
        $parking_type = 1;//season parking
        $lpr_logic = self::entry_request_season($plateInfo,$laneInfo,$seasonInfo,$bindedUserInfo,$logs_id);
        //update season checked result
        \App\EntryLog::update_season_checked_result($lpr_logic['check_result'],$logs_id);

        /*
         * if no season pass logic or season pass expired then request for vendor.
         * additional will sent to vendor if season pas user but expired(LS003)
         * if need new season check result to be sent to vendor can add in or condition here
        */


        if ($lpr_logic['status'] == false) {
            $result = self::entry_request_normal($plateInfo, $laneInfo, $bindedUserInfo,$logs_id);
            if ($result) {
                $lpr_logic = $result;
            }
            $parking_type = 0;//normal parking

            /*
             * for update visitor checked result we do inside entry_request_normal function when we got reply from vendor_push request
             * it because all the vendor response is come from there
             */

        }





        $lpr_logic_code = $lpr_logic['check_result'];
        //get lpr operation display
        $lpr_operation= new \App\Http\Controllers\API\LprController;
//        $lpr_operation=$lpr_operation->lpr_operation($lpr_logic_code,$plateInfo['plate_no'],$plateInfo['lpr_id'],$plateInfo['camera_id']);
        $lpr_operation=$lpr_operation->lpr_operation($lpr_logic_code,$plateInfo['cam1']['plate_no'],$plateInfo['cam1']['lpr_id'],$plateInfo['cam1']['camera_id']);

        log::info("{$plateInfo['cam1']['plate_no']}: lpr display=",$lpr_operation);
        #update logs check_result
        $user_id = '';
        $locked_flag = 0;
        $auto_deduct_flag = 0;
        $wallet_balance = 0;
//        dd($bindedUserInfo,$seasonInfo);
        if ($bindedUserInfo) {
            $plate_no = $seasonInfo['data']['plate_no'];
            $user_id = $bindedUserInfo['data'][$plate_no]['user_id'];
            $auto_deduct_flag = $bindedUserInfo['data'][$plate_no]['auto_deduct_flag'];
            $wallet_balance = $bindedUserInfo['data'][$plate_no]['wallet_balance'];
        }
        #update logs remark
        $remark=$lpr_operation['xx'];

        //add is season subcriber to update the flag
        $is_season = 0;
        if($seasonInfo != null){
            $is_season = 1;
        }

        \App\EntryLog::update_checked_result($logs_id,$lpr_logic_code,$user_id,$locked_flag,$auto_deduct_flag,$wallet_balance,$parking_type,$lpr_operation,$remark,$is_season);
        return response()->json($lpr_operation, 200);
    }

    protected function entry_request_normal($plateInfo,$laneInfo,$bindedUserInfo,$logs_id){
        /*
         * Check plate number that in the whitelist only can enter using lpr normal parking
         * need to remove this checking if we want to allow all car can enter using normal parking
         */
        $plate_no_white_list = \App\Http\Controllers\API\VendorHubController::lpr_visitor_whitelist($plateInfo['plate_no']);
        if ($plate_no_white_list==false){
            Log::warning("plate={$plateInfo['plate_no']} not in whitelist");
            return array('status'=>true,'check_result'=>'LS018');
        }

        $check_car_in_site=self::check_car_existance($plateInfo['plate_no']);

        #request normal parking entry here
        $vendor_push = \App\Http\Controllers\API\VendorHubController::push_normal_ticket(
            $plateInfo['plate_no'],$plateInfo['image_path'],$plateInfo['camera_id'],$bindedUserInfo,$laneInfo['ext_cam_ref_id'],$laneInfo['in_out_flag'],$logs_id);

        /*
         * if we got response from vendor only will update visitor_checked_result.
         * for snb visitor checked result needed to be update from tcp swoole module
        */
        if($vendor_push){
            //update visitor checked result
            \App\EntryLog::update_visitor_checked_result($vendor_push,$logs_id);
        }

        #if receive ticket
        if($vendor_push['status'] == true){
            #insert to car insite here
            $user_id = '';
            #$eticket_id = '';
            $locked_flag = 0;
            $auto_deduct_flag = 0;
            #$wallet_balance = 0;
            #$vendor_ticket_id = '';

            if ($bindedUserInfo) {
                $user_id = $bindedUserInfo['user_id'];
                $auto_deduct_flag = $bindedUserInfo['auto_deduct_flag'];
                #$wallet_balance = $bindedUserInfo['wallet_balance'];
            }
            //if vendor return success, then force leave existing active ticket before put new ticket for same plate no
            if($check_car_in_site == true){
                Log::warning("plate={$plateInfo['plate_no']} repeated entry, force leave");
                \App\CarInSite::car_force_leave_for_normal($plateInfo['plate_no']);
            }

            \App\CarInSite::vendor_to_insert_to_car_in_site($vendor_push['vendor_ticket_id'],$user_id,$locked_flag,$auto_deduct_flag,$logs_id,$plateInfo['plate_no']);
            //sent checkresult to be set to log.on this case should be LS010.entry for normal parker
            $lpr_logic_code = $vendor_push['check_result'];
            return array('status'=>true,'check_result'=>$lpr_logic_code);
        }
        if($vendor_push['status'] == false){
            //to handle check result for normal parking to effect final check result
            return array('status'=>false,'check_result'=>$vendor_push['check_result']);
        }
        #return false;
        return false;
    }

    protected function entry_request_season($plateInfo,$laneInfo,$seasonInfo,$bindedUserInfo,$logs_id){

        ############################################
        ##No season pass record                   ##
        ############################################
        if($seasonInfo["success"] == false){
            Log::notice("No season pass subcribe for plate=",$plateInfo);
            #trigger lpr backend operation
            //The plate no is not subcribe in season pass for this site
            $lpr_logic_code='LS010';
            return array('status'=>false,'check_result'=>$lpr_logic_code);
        }



        #########################################
        ##repeat season pass/entry             ##
        #########################################
        $season_used =self::check_season_pass_or_car_in_site($seasonInfo,$plateInfo,$logs_id);

        if($season_used > 0){
            Log::warning("Season Pass used card={$seasonInfo['data']['card_id']},plate={$plateInfo['cam1']['plate_no']}");
            #trigger lpr backend operation
            //Season Pass used aka repeated entry


            if($season_used == 1){
                $lpr_logic_code ='LS004';
            }
            if($season_used == 2){
                $lpr_logic_code ='LS021';
            }
            // if the same car plate number used, then do not check normal again,
            // if same season pass used, then user can enter via normal case
            $status = $season_used==1?true:false;

            return array('status'=>$status,'check_result'=>$lpr_logic_code);
        }

        if($seasonInfo['data']['active_flag']==0){
            Log::warning("In-active season pass id for card={$seasonInfo['card_id']},plate={$plateInfo['plate_no']}");
            $lpr_logic_code='LS005';
            return array('status'=>false,'check_result'=>$lpr_logic_code);
        }

        $valid_until = date('Y-m-d H:i:s', strtotime($seasonInfo['data']['valid_until']));
        #$valid_until = strtotime($valid_until);
        $valid_from = date('Y-m-d H:i:s', strtotime($seasonInfo['data']['valid_from']));
        #$valid_from = strtotime($valid_from);
        if(date('Y-m-d H:i:s') > $valid_until) {
            Log::warning("Season expired for plate_no={$plateInfo['cam1']['plate_no']} card_id={$seasonInfo['data']['card_id']}, valid untill $valid_until");
            $lpr_logic_code='LS003';
            #dd($lpr_logic_code,$valid_from,date('Y-m-d H:i:s'),$valid_until);
            return array('status'=>false,'check_result'=>$lpr_logic_code);
        }
        // check season access category
        if (self::check_season_access_category($seasonInfo['data']['access_category']) == false) {
            Log::warning("Season check access category failed, access_category is : " . $seasonInfo['data']['access_category']);
            $lpr_logic_code='LS020';
            return array('status'=>false,'check_result'=>$lpr_logic_code);
        }
        
        ####################################
        ##update entry_log table_to_sucess##
        ####################################
        Log::info("Valid season pass user for plate_no={$plateInfo['cam1']['plate_no']} card_id={$seasonInfo['data']['card_id']}");
        $lpr_logic_code='LS006';
        $entry_logs=\App\EntryLog::update_logs_to_success($logs_id);
        ################################
        ##update car in house entrance##
        ################################
        $user_id = '';
        $eticket_id = '';
        $locked_flag = 0;
        $auto_deduct_flag = 0;
        $wallet_balance = 0;
        $vendor_ticket_id = '';
//        dd($bindedUserInfo);
        if ($bindedUserInfo) {
            $user_id = $bindedUserInfo['data'][$seasonInfo['data']['plate_no']]['user_id'];
            $auto_deduct_flag = $bindedUserInfo['data'][$seasonInfo['data']['plate_no']]['auto_deduct_flag'];
            $wallet_balance = $bindedUserInfo['data'][$seasonInfo['data']['plate_no']]['wallet_balance'];
        }
//        dd($plateInfo,$laneInfo,$seasonInfo,$bindedUserInfo,$logs_id,$season_used,"entry_request_season 551");
        \App\CarInSite::car_in($entry_id_log=$logs_id,$parking_type='1',$seasonInfo['data']['card_id'],
            $user_id,$eticket_id,$locked_flag,$auto_deduct_flag,$wallet_balance,$vendor_ticket_id,$plateInfo['cam1']['plate_no']);
        ####update car in house entrance end

        //update vendor check result so we can sync to cloud for snb case
        \App\EntryLog::update_vendor_checked_result_season($logs_id,1);

        return array('status'=>true,'check_result'=>$lpr_logic_code);

    }//function entry_request($card_id,$plate_no,$active_flag,$valid_until,$lane_id,$camera_id,$car_color,$lane_in_out_flag,$image_path,$image_file,$vip) end


    protected function exit_request($plateInfo,$lane_detail,$seasonInfo,$carInSiteInfo,$logs_id){

        $plate_no = $plateInfo['plate_no'];
        log::info("Request EXIT--------- at lane= $plate_no");
        //check request exit from season pass first.
        $lpr_logic = self::exit_request_season($plateInfo,$lane_detail,$seasonInfo,$carInSiteInfo,$logs_id);
        //update season checked result
        \App\EntryLog::update_season_checked_result($lpr_logic['check_result'],$logs_id);

        //if not season pass user then check normal parker reequest to exit.
        if ($lpr_logic['status'] == false) {
            $rrrr = self::exit_request_normal($plateInfo,$lane_detail,$carInSiteInfo,$logs_id);
            if ($rrrr) {

                $lpr_logic = $rrrr;
            }
        }
        //if no entry record..just sent to vendor to check their system.will push display to center program based on vendor check result
        if ($lpr_logic['check_result'] == 'LS007') {
            $kiple_user_id = '';
            if ($carInSiteInfo!=null) {
                $kiple_user_id = $carInSiteInfo['user_id'];
            }
            //get check result or logic_code from vendor
            $lpr_logic = \App\Http\Controllers\API\VendorHubController::request_exit($plate_no,$kiple_user_id,$lane_detail['ext_cam_ref_id'],$logs_id,$plateInfo['image_path']);
            //update visitor check result for this entry log
            \App\EntryLog::update_visitor_checked_result($lpr_logic,$logs_id);
        }

        //final logic code aka check result
        $lpr_logic_code = $lpr_logic['check_result'];

//        dd($lpr_logic_code);
        //get lpr operation display
        $lpr_operation= new \App\Http\Controllers\API\LprController;
        $lpr_operation=$lpr_operation->lpr_operation($lpr_logic_code,$plate_no,$plateInfo['lpr_id'],$plateInfo['camera_id']);

        #update logs check_result
        $parking_type = 0;
        $user_id = '';
        $locked_flag = 0;
        $auto_deduct_flag = 0;
        $wallet_balance = 0;
        if ($carInSiteInfo) {
            $user_id = $carInSiteInfo['user_id'];
            $auto_deduct_flag = $carInSiteInfo['auto_deduct_flag'];
            $wallet_balance = $carInSiteInfo['wallet_balance'];
            $parking_type = $carInSiteInfo['parking_type'];
        }

        #update logs remark
        $remark=$lpr_operation['xx'];

        //add is season subcriber to update the flag
        $is_season = 0;
        if($seasonInfo != null){
            $is_season = 1;
        }

        //update logs check_result
        \App\EntryLog::update_checked_result($logs_id,$lpr_logic_code,$user_id,$locked_flag,$auto_deduct_flag,$wallet_balance,$parking_type,$lpr_operation,$remark,$is_season);

        log::info("$plate_no: lpr display=",$lpr_operation);
        return response()->json($lpr_operation, 200);
    }

    protected function manual_exit_request($plateInfo,$lane_detail,$seasonInfo,$carInSiteInfo,$logs_id){

        //update log for manual leave by operator
        $user_details= new \App\Http\Controllers\API\PassportController;
        $user_details_dict=$user_details->getDetails();
        $user_details_dict=json_encode($user_details_dict);
        $user_details_dict=json_decode($user_details_dict,true);
        $user_id=$user_details_dict["original"]["success"]["email"];
        \App\EntryLog::update_manual_leave_by($user_id,$logs_id);

        $plate_no = $plateInfo['plate_no'];
        log::info("Request manual EXIT by $user_id");
        //check request exit from season pass first.
        $lpr_logic = self::exit_request_season($plateInfo,$lane_detail,$seasonInfo,$carInSiteInfo,$logs_id);

        //update season checked result
        \App\EntryLog::update_season_checked_result($lpr_logic['check_result'],$logs_id);

        //if not season pass user then check normal parker reequest to exit.
        if ($lpr_logic['status'] == false) {
            $rrrr = self::manual_exit_request_normal($plateInfo,$lane_detail,$carInSiteInfo,$logs_id);
            if ($rrrr) {

                $lpr_logic = $rrrr;
            }
        }
        //if no entry record..just sent to vendor to check their system.will push display to center program based on vendor check result
        if ($lpr_logic['check_result'] == 'LS007') {
            $rrrr = self::manual_exit_request_normal($plateInfo,$lane_detail,$carInSiteInfo,$logs_id);
            if ($rrrr) {

                $lpr_logic = $rrrr;
            }
        }

        //final logic code aka check result
        $lpr_logic_code = $lpr_logic['check_result'];

        //get lpr operation display
        $lpr_operation= new \App\Http\Controllers\API\LprController;
        $lpr_operation=$lpr_operation->lpr_operation($lpr_logic_code,$plate_no,$plateInfo['lpr_id'],$plateInfo['camera_id']);


        //trigger open barrier to vzcenter program
        if ($lpr_logic_code == 'LS009'){
            //update leave type as manual leave
            \App\EntryLog::update_leave_type($logs_id,1);
            $message_line1 = $lpr_operation['operator'][0]['messages']['line1'];
            $message_line2 = $lpr_operation['operator'][0]['messages']['line2'];
            \App\Http\Controllers\API\LprController::trigger_lpr_operation_manual_leave($plateInfo['camera_id'],$message_line1,$message_line2);
        }


        #update logs check_result
        $parking_type = 0;
        $user_id = '';
        $locked_flag = 0;
        $auto_deduct_flag = 0;
        $wallet_balance = 0;
        if ($carInSiteInfo) {
            $user_id = $carInSiteInfo['user_id'];
            $auto_deduct_flag = $carInSiteInfo['auto_deduct_flag'];
            $wallet_balance = $carInSiteInfo['wallet_balance'];
            $parking_type = $carInSiteInfo['parking_type'];
        }

        #update logs remark
        $remark=$lpr_operation['xx'];

        //add is season subcriber to update the flag
        $is_season = 0;
        if($seasonInfo != null){
            $is_season = 1;
        }

        //for manual exit by lane dashboard always set to success exit for checked result
        if($lpr_logic_code != 'LS009'){
            $lpr_logic_code = 'LS011';
        }

        //update logs check_result
        \App\EntryLog::update_checked_result($logs_id,$lpr_logic_code,$user_id,$locked_flag,$auto_deduct_flag,$wallet_balance,$parking_type,$lpr_operation,$remark,$is_season);

        #log::info("$plate_no: lpr display=",$lpr_operation);
        $response = array(
                            'code' => 0,
                            'message' => 'success',
                            'data' => array('new_entry_log_id' => (integer)$logs_id)
                         );
        log::info("$plate_no: manual leave response=",$response);
        return response()->json($response, 200);
    }

    protected function exit_request_normal($plateInfo,$laneInfo,$carInSiteInfo,$logs_id){
        /*
         * Check plate number that in the whitelist only can enter using lpr normal parking
         * need to remove this checking if we want to allow all car can enter using normal parking
         *
         * do we still need this?
         */
        $plate_no_white_list = \App\Http\Controllers\API\VendorHubController::lpr_visitor_whitelist($plateInfo['plate_no']);
        if ($plate_no_white_list==false){
            Log::warning("plate={$plateInfo['plate_no']} not in whitelist.will not sent to vendor");
            return array('status'=>true,'check_result'=>'LS018');
        }
        
        #sent exit request to vendor
        $kiple_user_id = '';
        if ($carInSiteInfo!=null) {
            $kiple_user_id = $carInSiteInfo['user_id'];
        }
        $vendor_push = \App\Http\Controllers\API\VendorHubController::request_exit($plateInfo['plate_no'],$kiple_user_id,
            $laneInfo['ext_cam_ref_id'],$logs_id,$plateInfo['image_path']);
        #dd($vendor_push);

        /*
         * if we got response from vendor only will update visitor_checked_result.
         * for snb visitor checked result needed to be update from tcp swoole module
        */
        if($vendor_push){
            //update visitor checked result
            \App\EntryLog::update_visitor_checked_result($vendor_push,$logs_id);
        }

        if ($vendor_push!=null) {
            $lpr_operation["vendor_ticket_id"] = $vendor_push['vendor_ticket_id'];
            #update logs check_result
            $parking_type = 0;
            $user_id = '';
            $locked_flag = 0;
            $auto_deduct_flag = 0;
            $wallet_balance = 0;
            $vendor_ticket_id = '';
            if ($carInSiteInfo) {
                $user_id = $carInSiteInfo['user_id'];
                $auto_deduct_flag = $carInSiteInfo['auto_deduct_flag'];
                $wallet_balance = $carInSiteInfo['wallet_balance'];
                $parking_type = $carInSiteInfo['parking_type'];
            }

            return $vendor_push;
        }
        return false;
    }

    //manual exit request normal parking
    protected function manual_exit_request_normal($plateInfo,$laneInfo,$carInSiteInfo,$logs_id){
        $ini_array = parse_ini_file("../cron/config.ini",true);
        $vendor_id = $ini_array['common']['VENDOR_ID'];
        if($vendor_id == "V0004"){
            log::warning("manual_exit_request_normal for snb is not support");
            return false;
        }

        #sent exit request to vendor
        $kiple_user_id = '';
        if ($carInSiteInfo!=null) {
            $kiple_user_id = $carInSiteInfo['user_id'];
        }
        $vendor_push = \App\Http\Controllers\API\VendorHubController::manual_request_exit($plateInfo['plate_no'],$kiple_user_id,
            $laneInfo['ext_cam_ref_id'],$logs_id,$plateInfo['image_path']);
        //dd($vendor_push);

        /*
         * if we got response from vendor only will update visitor_checked_result.
         * for snb visitor checked result needed to be update from tcp swoole module
        */
        if($vendor_push){
            //update visitor checked result
            \App\EntryLog::update_visitor_checked_result($vendor_push,$logs_id);
        }

        if ($vendor_push!=null) {
            $lpr_operation["vendor_ticket_id"] = $vendor_push['vendor_ticket_id'];
            #update logs check_result
            $parking_type = 0;
            $user_id = '';
            $locked_flag = 0;
            $auto_deduct_flag = 0;
            $wallet_balance = 0;
            $vendor_ticket_id = '';
            if ($carInSiteInfo) {
                $user_id = $carInSiteInfo['user_id'];
                $auto_deduct_flag = $carInSiteInfo['auto_deduct_flag'];
                $wallet_balance = $carInSiteInfo['wallet_balance'];
                $parking_type = $carInSiteInfo['parking_type'];
            }

            return $vendor_push;
        }
        return false;
    }

    public function exit_request_season($plateInfo,$laneInfo,$seasonInfo,$carInSiteInfo,$logs_id){
        $plate_no = $plateInfo['plate_no'];
        if($plate_no == "_NONE_"){
            Log::notice("undetected plate no $plate_no");
            #trigger lpr backend operation
            //Localpsm did not get small img for plate number and the number plate showing _NONE_
            $lpr_logic_code='LS013';
            return array('status'=>true,'check_result'=>$lpr_logic_code);
        }
        // try to get record from car in site table
        if ($carInSiteInfo==null) {
            if ($seasonInfo!=null) {
                // this is a season card, but on entry record, we just block this exit
                Log::warning("$plate_no season exit without entry record");
                $lpr_logic_code='LS007';
                return array('status'=>true,'check_result'=>$lpr_logic_code);
            }
            /*
             * this is a normal exit without entry record, pass to vendor check as normal.
             * for snb final check result will confirm by swole thread.
             * for current visitor on snb site will always get this check result as we only whitelist few email to allow auto deduct
             * it is different flow compare to maxpark.
            */
            $lpr_logic_code='LS026';
            Log::warning("$plate_no is exit with visitor ticket");
            return array('status'=>false,'check_result'=>$lpr_logic_code);
        }
        if ($carInSiteInfo['locked_flag']==1) {
            // this car is locked, both normal and season lock control is done by us
            Log::warning("$plate_no is in locked status");
            $lpr_logic_code='LS008';
            return array('status'=>true,'check_result'=>$lpr_logic_code);
        }
        if ($carInSiteInfo['parking_type']==1) {
            // this is a valid season exit
            Log::info("$plate_no valid season exit");
            $lpr_logic_code='LS009';

            \App\CarInSite::car_out($exit_id_log=$logs_id,$plate_no);
            ####update car in house entrance end

            ##update entry_log table_to_sucess##
            $entry_logs=\App\EntryLog::update_logs_to_success($logs_id);

            //update vendor check result so we can sync to cloud for snb case
            \App\EntryLog::update_vendor_checked_result_season($logs_id,1);

            return array('status'=>true,'check_result'=>$lpr_logic_code);
        }

        return array('status'=>false,'check_result'=>'LS026');


    }//function exit_request($card_id,$plate_no,$active_flag,$lane_id,$camera_id,$car_color,$lane_in_out_flag,$image_path,$image_file,$vip) end
//    public function check_season_pass_or_car_in_site($card_id,$plate_no,$logs_id)
    public function check_season_pass_or_car_in_site($seasonInfo,$plateInfo,$logs_id){
        // first check has same plate number in car_in_site table
        $check_car_in_site_by_plate_number= \App\CarInSite::check_car_in_site_by_plate_number($plateInfo);
        if($check_car_in_site_by_plate_number != null){
            $logs_detail=\App\EntryLog::get_logs_details($check_car_in_site_by_plate_number["entry_id"]);
            Log::warning("Season Pass same plate number is used by ".$logs_detail["plate_no"]." entrance at ".$check_car_in_site_by_plate_number["created_at"]);
            return 1;
        }
        // then check has same season pass in car_in_site table
        $card_id = $seasonInfo['data']['card_id'];
        $check_car_in_site_by_season_id= \App\CarInSite::check_car_in_site_by_season_id($card_id);
        if($check_car_in_site_by_season_id != null){
            $logs_detail=\App\EntryLog::get_logs_details($check_car_in_site_by_season_id["entry_id"]);
            //update secondary plate number to entry log table
            \App\EntryLog::update_secondary_season_plate_to_visitor($logs_id,$check_car_in_site_by_season_id['plate_no']);
            Log::warning("Season Pass same ".$card_id." is used by".$logs_detail["plate_no"]." entrance at ".$check_car_in_site_by_season_id["created_at"]);
            return 2;
        }
        return 0;
    }//function check_car_in_site($card_id,$plate_no) end
    
    public function check_car_locking($plate_no){
        $check_car= \App\CarInSite::check_car_locking($plate_no);
        if($check_car==1){
            Log::warning("$plate_no try to exit but its in locked status");
            return true;
        }
        return false;
    }//function check_car_locking($card_id)

    public function check_car_existance($plate_no){
        $check_car= \App\CarInSite::check_car_existance($plate_no);
        if($check_car == null){
            return false;
        }
        return true;
    }//function check_car_existance($card_id)

    public function push_state(Request $request){

        $body2=file_get_contents('php://input');
        $json_body2=json_decode($body2,true);
        Log::info("Requestxxxx receive all php://input ",$json_body2);

        $camera_id=$json_body2["body"]["vzid"]["sn"];
        $ipaddress=$json_body2["body"]["vzid"]["ip_addr"];
        $state=$json_body2["body"]["vzid"]["state"];

        \App\LaneConfig::update_camera_state($state,$camera_id,$body2,$ipaddress);



    }//function push_state(Request $request) end


    /**
     * check current time is matched season pass access category or not
     * @param string $access_category the season pass access category, each week day has 8 chars,
     *   "00002400" means this season pass can access from 00:00 to 24:00. the week day is 
     *   from Monday to Sunday, to total 7*8 = 56 chars, for example:
     *   00002400000024000000240000002400000024000000240000002400
     *   00002400000024000000240000002400000024000000130000000000
     * 
     */
    function check_season_access_category($access_category) {
        if (empty($access_category)) {
            // if the access category is empty, then we allow season pass
            return true;
        }
        $weekday = intval(date('w'));
        // 0 is Sunday, ... 6 is Staturday
        if ($weekday==0) {
            $weekday = 7;
        }
        $weekday_access = substr($access_category,($weekday-1)*8,8);
        if (empty($weekday_access)) {
            // if the access category for this week day is empty, then we allow season pass
            return true;
        }
        if (strlen($weekday_access)!=8) {
            // if the access category is invalid, the we allow season pass
            return true;
        }
        $cur_hour = intval(date('H'));
        $cur_minute = intval(date('i'));    
        $cur_value = $cur_hour*60+$cur_minute;
        $access_start_hour = intval(substr($weekday_access,0,2));
        $access_start_minute = intval(substr($weekday_access,2,2));
        $access_start_value = $access_start_hour*60+$access_start_minute;
        $access_end_hour = intval(substr($weekday_access,4,2));
        $access_end_minute = substr($weekday_access,6,2);
        $access_end_value = $access_end_hour*60+$access_end_minute;
        if ($cur_value<$access_start_value || $cur_value>$access_end_value) {
            return false;
        }
        return true;
    }

    /**
     * try to fix the plate number that start with special chars
     *
     * @param string $plate_no  the original plate number
     * @return string the fixed plate number
     */
    protected function check_initial_plate_number($plate_no){
        $start_with_maps = array(
            'V1P' => 'VIP',
            #'A1M' => 'AIM',
            'PATR10T' => 'PATRIOT',
            'R1MAU' => 'RIMAU',
            'PATR10T' => 'PATRIOT',
            'NBQS' => 'NBOS',
            'NBDS' => 'NBOS',
            'NB0S' => 'NBOS',
            'SUKDM' => 'SUKOM',
            'SUK0M' => 'SUKOM',
        );
        foreach($start_with_maps as $key=>$value) {
            if ( strpos($plate_no,$key)===0 ){
                $len = strlen($key);
                $plate_no = $value.substr($plate_no,$len);
                return $plate_no;
            }
        }
        return $plate_no;
    }

    function set_single_plate_check_from_dual($plateInfo,$cam_to_choose){

        if($cam_to_choose == 'cam1') {
            $plateInfo["record_to_process"] = 1;
            $plateInfo['cam2']['log_id'] = -1;
            $plateInfo['cam2']['plate_no'] = "";
            $plateInfo['cam2']['camera_id'] = "";
            $plateInfo['cam2']['car_color'] = "";
            $plateInfo['cam2']['image_path'] = "";
            $plateInfo['cam2']['image_frag_path'] = "";
            $plateInfo['cam2']['lpr_id'] = "";
        }

        if($cam_to_choose == 'cam2') {
            $plateInfo["record_to_process"] = 1;
            $plateInfo['cam1']['log_id'] = $plateInfo['cam2']['log_id'];
            $plateInfo['cam1']['plate_no'] = $plateInfo['cam2']['plate_no'];
            $plateInfo['cam1']['camera_id'] = $plateInfo['cam2']['camera_id'];
            $plateInfo['cam1']['car_color'] = $plateInfo['cam2']['car_color'];
            $plateInfo['cam1']['image_path'] = $plateInfo['cam2']['image_path'];
            $plateInfo['cam1']['image_frag_path'] = $plateInfo['cam2']['image_frag_path'];
            $plateInfo['cam1']['lpr_id'] = $plateInfo['cam2']['lpr_id'];

            $plateInfo['cam2']['log_id'] = -1;
            $plateInfo['cam2']['plate_no'] = "";
            $plateInfo['cam2']['camera_id'] = "";
            $plateInfo['cam2']['car_color'] = "";
            $plateInfo['cam2']['image_path'] = "";
            $plateInfo['cam2']['image_frag_path'] = "";
            $plateInfo['cam2']['lpr_id'] = "";
        }

        return $plateInfo;


    }
}
