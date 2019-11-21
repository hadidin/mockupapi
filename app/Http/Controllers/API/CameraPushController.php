<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CameraPushController extends Controller
{
    /**
     * convert check result from string to integer
     */
    protected function check_result_to_integer($check_result){
        if (empty($check_result)) {
            return 0;
        }
        // 'LS010' => 10
        return intval(substr($check_result,2));
    }

    /**
     * map plate number
     */
    protected function map_plate_no($plate_no)
    {
        $new_plate_no = $plate_no;
        $start_with_maps = array(
            'V1P' => 'VIP',
            #'A1M' => 'AIM',
            'PATR10T' => 'PATRIOT',
            'R1MAU' => 'RIMAU',
            'PATR10T' => 'PATRIOT',
            'NBQS' => 'NBOS',
            'NBDS' => 'NBOS',
            'NB0S' => 'NBOS',
            'NB05' => 'NBOS',
            'XQ1C' => 'XOIC',
        );
        foreach($start_with_maps as $key=>$value) {
            if ( strpos($plate_no,$key)===0 ){
                $len = strlen($key);
                $new_plate_no = $value.substr($plate_no,$len);
                break;
            }
        }
        $temp = \App\FixPlateNo::getDesirePlateNo($new_plate_no);
        if ($temp) {
            Log::warning("Change plate no from $plate_no to $temp");
            $new_plate_no = $temp;
        }
        return $new_plate_no;
    }

    /**
     * check undetected plate no, include empty and _NONE cases
     */
    protected function check_undetected($plate_info,$lane_info)
    {
        if(empty($plate_info['plate_no']) || $plate_info['plate_no']== "_NONE_"){
            Log::warning("[check_undetected] plate no is emptry or _NONE_");
            $code ='LS013'; // exit undetected
            if ($lane_info['in_out_flag']==0) {
                $code ='LS012'; //entry undetected
            }
            return array('status'=>true,'check_result'=>$code,'message'=>'plate no is none');
        }
        $code_not_processed = 'LS092';
        return array('status'=>false,'check_result'=>$code_not_processed);
    }

    /**
     * entry request for undetected
     */
    protected function entry_request_undetected($entry_log_id,$lane_info,$plate_info,$plate_info2,&$attached_info)
    {
        // only one plate no for the undetected case
        if(empty($plate_info['plate_no']) || $plate_info['plate_no']== "_NONE_"){
            return array('status'=>true,'parking_type'=>9,'check_result'=>'LS012','message'=>'plate no is none');
        }
        $code_not_processed = 'LS092';
        return array('status'=>false,'parking_type'=>9,'check_result'=>$code_not_processed,'message'=>'undetected not processed');

    }

    /**
     * entry request for white list
     */
    protected function entry_request_white_list($entry_log_id,$lane_info,$plate_info,$plate_info2,&$attached_info)
    {
        Log::info("[entry_request_white_list] entry id:$entry_log_id,enter");

        $wl_info = \App\WhiteList::searchRecordByPlateNo($plate_info['plate_no']);
        if (empty($wl_info) && !empty($plate_info2)) {
            $wl_info = \App\WhiteList::searchRecordByPlateNo($plate_info2['plate_no']);
        }
        if (empty($wl_info)) {
            Log::warning("[entry_request_white_list] entry id:$entry_log_id, not in white list");
            return array('status'=>false,'parking_type'=>9,'check_result'=>'LS040','message'=>'not in white list');
        }
        Log::info("[exit_request_white_list] entry id:$entry_log_id,white list matched plate no:{$wl_info->plate_no}");
        $result['used_plate_no'] = $wl_info->plate_no;

        if ($wl_info->enable_flag==0) {
            Log::warning("[entry_request_white_list] entry id:$entry_log_id, white list disabled");
            return array('status'=>false,'used_plate_no'=>$wl_info->plate_no,'parking_type'=>9,'check_result'=>'LS041','message'=>'disabled in white list');
        }
        if (!empty($wl_info->valid_from)) {
            // valid from process as date, no time
            $validFrom = date('Y-m-d 00:00:00', strtotime($wl_info->valid_from));
            if(date('Y-m-d H:i:s') < $validFrom) {
                Log::warning("[entry_request_white_list] entry id:$entry_log_id, white list valid from:{$wl_info->valid_from} expired.");
                return array('status'=>false,'used_plate_no'=>$wl_info->plate_no,'parking_type'=>9,'check_result'=>'LS042','message'=>'valid from expired');
            }
        }
        if (!empty($wl_info->valid_until)) {
            // valid until process as date, no time
            $validUntil = date('Y-m-d 23:59:59', strtotime($wl_info->valid_until));
            if(date('Y-m-d H:i:s') > $validUntil) {
                Log::warning("[entry_request_white_list] entry id:$entry_log_id, white list valid until:{$wl_info->valid_until} expired.");
                return array('status'=>false,'used_plate_no'=>$wl_info->plate_no,'parking_type'=>9,'check_result'=>'LS042','message'=>'valid until expired');
            }
        }
        // try to auto leave the same plate no
        $auto_leave = 2;
        $check_result = $this->check_result_to_integer('LS048');
        $plate_no2 = isset($plate_info2['plate_no'])?$plate_info2['plate_no']:'';
        \App\CarInSite::abnoraml_leave2($wl_info->plate_no,'',$auto_leave,$check_result);
        Log::info("[entry_request_white_list] entry id:$entry_log_id,white list success");

        $user_id = '';
        $eticket_id = '';
        $locked_flag = 0;
        $auto_deduct_flag = 0;
        $wallet_balance = 0;
        $vendor_ticket_id = '';
        if (isset($attached_info['bind_user_info'])) {
            $bind_user_info = $attached_info['bind_user_info'];
            // force set locked flag to 0 this version
            $bind_user_info['locked_flag'] = 0;
            $user_id = $bind_user_info['user_id'];
            $auto_deduct_flag = $bind_user_info['auto_deduct_flag'];
            $wallet_balance = $bind_user_info['wallet_balance'];
            $locked_flag = $bind_user_info['locked_flag'];
        }
        $parking_type = 2;
        $plate_no2 = isset($plate_info2['plate_no'])?$plate_info2['plate_no']:'';
        $last_id = \App\CarInSite::car_in2($entry_log_id,$parking_type,$wl_info->id,$user_id,$eticket_id,$locked_flag,
            $auto_deduct_flag,$wallet_balance,$vendor_ticket_id,$wl_info->plate_no,'');
        if ($last_id==false) {
            Log::error("[entry_request_white_list] entry id:$entry_log_id,car in 2 failed");
        }
        return array('status'=>true,'used_plate_no'=>$wl_info->plate_no,'parking_type'=>2,'check_result'=>'LS043','message'=>'white list success');
    }

    /**
     * check current time is matched season pass access category or not
     * @param string $access_category the season pass access category, each week day has 8 chars,
     *   "00002400" means this season pass can access from 00:00 to 24:00. the week day is
     *   from Monday to Sunday, to total 7*8 = 56 chars, for example:
     *   00002400000024000000240000002400000024000000240000002400
     *   00002400000024000000240000002400000024000000130000000000
     *
     */
    protected function check_season_access_category($access_category) {
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
     * entry request for season
     */
    protected function entry_request_season($entry_log_id,$lane_info,$plate_info,$plate_info2,&$attached_info)
    {
        Log::info("[entry_request_season] entry id:$entry_log_id,enter");
        $plate_no2 = isset($plate_info2['plate_no'])?$plate_info2['plate_no']:'';
        $used_plate_no = $plate_info['plate_no'];

        // get season card info

        //check front camera
        $season_info = \App\SmcHolderInfo::getHolderInfo($plate_info['plate_no']);

        // dd($season_info);

        // if front comera not found, check rear camera
        if (empty($season_info) && !empty($plate_no2)) {
            $season_info = \App\SmcHolderInfo::getHolderInfo($plate_no2);
            $used_plate_no = $plate_no2;
        }
        if (empty($season_info)) {
            Log::info("[entry_request_season] entry id:$entry_log_id,not a season pass");
            return array('status'=>false,'parking_type'=>9,'season_check_result'=>0,'check_result'=>'LS025','message'=>'not a season pass');
        }
        $season_card_id = $season_info->card_id;
        Log::info("[entry_request_season] entry id:$entry_log_id,get season card id:$season_card_id");

        $bind_user_info = null;
        if (isset($attached_info['bind_user_info'])) {
            $bind_user_info = $attached_info['bind_user_info'];
        } else {
            $bind_user_info = \App\SmcHolderInfo::get_bind_info($plate_info['plate_no'],$plate_no2);
            $attached_info['bind_user_info'] = $bind_user_info;
        }
        // check active
        if($season_info->active_flag==0){
            Log::warning("[entry_request_season] entry id:$entry_log_id,season card is in active");
            return array('status'=>false,'used_plate_no'=>$used_plate_no,'parking_type'=>9,'season_check_result'=>5,'check_result'=>'LS005','message'=>'season pass inactive');
        }
        /*
        // check duplicate number, either plate no or plate no 2
        $record = \App\CarInSite::check_car_in_site_by_plate_number2($plate_info['plate_no']);
        if (!empty($record)) {
            Log::warning("[entry_request_season] entry id:$entry_log_id,duplicate plate no:{$plate_info['plate_no']}, last entry time is:{$record->created_at}");
            return array('status'=>true,'used_plate_no'=>$plate_info['plate_no'],'parking_type'=>9,'season_check_result'=>4,'check_result'=>'LS004','message'=>'season plate duplicate');
        }
        if (!empty($plate_no2)) {
            $record = \App\CarInSite::check_car_in_site_by_plate_number2($plate_no2);
            if (!empty($record)) {
                Log::warning("[entry_request_season] entry id:$entry_log_id,duplicate plate no2:{$plate_no2}, last entry time is:{$record->created_at}");
                return array('status'=>true,'used_plate_no'=>$plate_no2,'parking_type'=>9,'season_check_result'=>4,'check_result'=>'LS004','message'=>'season plate duplicate');
            }
        }
        */
        $record = \App\CarInSite::check_car_in_site_by_plate_number2($used_plate_no);
        if (!empty($record)) {
            Log::warning("[entry_request_season] entry id:$entry_log_id,duplicate plate no:{$used_plate_no}, last entry time is:{$record->created_at}");
            return array('status'=>true,'used_plate_no'=>$used_plate_no,'parking_type'=>9,'season_check_result'=>4,'check_result'=>'LS004','message'=>'season plate duplicate');
        }
        // check season secondary
        $record = \App\CarInSite::check_car_in_site_by_season_id($season_info->card_id);
        if ($record) {
            Log::warning("[entry_request_season] entry id:$entry_log_id, card_id: {$season_info->card_id} exceed season entry limit");
            return array('status'=>false,'used_plate_no'=>$used_plate_no,'parking_type'=>9,'season_check_result'=>3,'check_result'=>'LS021','message'=>'exceed season entry limit');
        }

        // check expired
        $valid_until = date('Y-m-d H:i:s', strtotime($season_info->valid_until));
        if(date('Y-m-d H:i:s') > $valid_until) {
            Log::warning("[entry_request_season] entry id:$entry_log_id,season card get expired, valid untill:$valid_until");
            return array('status'=>false,'used_plate_no'=>$used_plate_no,'parking_type'=>9,'season_check_result'=>2,'check_result'=>'LS003','message'=>'season card expired');
        }

        // check season access category
        if (self::check_season_access_category($season_info->access_category)==false) {
            Log::warning("[entry_request_season] entry id:$entry_log_id,season card access category failed, access_category:" . $season_info['access_category']);
            return array('status'=>false,'used_plate_no'=>$used_plate_no,'parking_type'=>9,'season_check_result'=>6,'check_result'=>'LS020','message'=>'season card acceess denied');
        }

        Log::info("[entry_request_season] season success");

        $result = array('status'=>true,'used_plate_no'=>$used_plate_no,'parking_type'=>1,'season_check_result'=>1,'check_result'=>'LS006','message'=>'season success');

        $user_id = '';
        $eticket_id = '';
        $locked_flag = 0;
        $auto_deduct_flag = 0;
        $wallet_balance = 0;
        $vendor_ticket_id = '';
        $vendor_ticket_id = '';
        if ($bind_user_info && $bind_user_info[$used_plate_no]['binded'] == true) {
            $user_info = $bind_user_info[$used_plate_no];
            // force set locked flag to 0 this version
            $user_info['locked_flag'] = 0;
            $user_id = $user_info['user_id'];
            $auto_deduct_flag = $user_info['auto_deduct_flag'];
            $wallet_balance = $user_info['wallet_balance'];
            $locked_flag = $user_info['locked_flag'];
            $result['bind_user_info'] = $user_info;
        }
        $parking_type = 1;
        $last_id = \App\CarInSite::car_in2($entry_log_id,$parking_type,$season_card_id,$user_id,$eticket_id,$locked_flag,
            $auto_deduct_flag,$wallet_balance,$vendor_ticket_id,$used_plate_no,'');

        if ($last_id==false) {
            Log::error("[entry_request_season] entry id:$entry_log_id,car in 2 failed");
        }
        return $result;
    }

    /**
     * entry request for visitor
     */
    protected function entry_request_visitor($entry_log_id,$lane_info,$plate_info,$plate_info2,&$attached_info)
    {
        Log::info("[entry_request_visitor] entry id:$entry_log_id,enter");

        $plate_no2 = isset($plate_info2['plate_no'])?$plate_info2['plate_no']:'';

        $bind_user_info = null;
        if (isset($attached_info['bind_user_info'])) {
            $bind_user_info = $attached_info['bind_user_info'];
        } else {
            $bind_user_info = \App\SmcHolderInfo::get_bind_info($plate_info['plate_no'],$plate_no2);
            $attached_info['bind_user_info'] = $bind_user_info;
        }

        // send to vendor
        $result = \App\Http\Controllers\API\VendorHubController::entry_request_visitor(
            $plate_info['plate_no'],$plate_no2,$plate_info['big_picture'],$plate_info['camera_sn'],
            $bind_user_info,$lane_info['ext_cam_ref_id'],$lane_info['in_out_flag'],$entry_log_id);
        Log::info("[entry_request_visitor] entry id:$entry_log_id, vendor result:".json_encode($result));

        $ini_array = parse_ini_file("../cron/config.ini",true);
        $vendor_id=$ini_array['common']['VENDOR_ID'];

        if($result['status'] == true && $vendor_id != "V0009"){
            // try to auto leave the same plate no
            $auto_leave = 2;
            $check_result = $this->check_result_to_integer('LS011');
            \App\CarInSite::abnoraml_leave2($plate_info['plate_no'],$plate_no2,$auto_leave,$check_result);

            // create car in site record
            $user_id = '';
            $eticket_id = '';
            $locked_flag = 0;
            $auto_deduct_flag = 0;
            $wallet_balance = 0;
            $vendor_ticket_id = isset($result['vendor_ticket_id'])?$result['vendor_ticket_id']:'';
            // choose one binded user information
            $user_info = null;
            if($bind_user_info){
                foreach($bind_user_info as $key => $value) {
                    if ($value['binded']==true) {
                        $user_info = $value;
                        break;
                    }
                }
            }
            $plate_no1 = $plate_info['plate_no'];
            if ($user_info) {
                // force set locked flag to 0 this version
                $user_info['locked_flag'] = 0;
                $user_id = $user_info['user_id'];
                $auto_deduct_flag = $user_info['auto_deduct_flag'];
                $wallet_balance = $user_info['wallet_balance'];
                $locked_flag = $user_info['locked_flag'];
                $result['bind_user_info'] = $user_info;
                // when sycn to cloud, we always send the plate_no1, so need to make sure
                // it is same as user binded plate no
                if ($plate_no1!=$user_info['plate_number']) {
                    $temp = $plate_no1;
                    $plate_no1 = $user_info['plate_number'];
                    $plate_no2 = $temp;
                }
            }
            $parking_type =0;
            $last_id = \App\CarInSite::car_in2($entry_log_id,$parking_type,'',$user_id,$eticket_id,$locked_flag,
                $auto_deduct_flag,$wallet_balance,$vendor_ticket_id,$plate_no1,$plate_no2);
            if ($last_id==false) {
                Log::error("[entry_request_visitor] entry id:$entry_log_id,car in 2 failed");
            }
        }
        return $result;
    }

    /**
     * entry request
     */
    protected function entry_request($entry_log_id,$lane_info,$plate_info,$plate_info2)
    {
        Log::info("[entry_request] entry id:$entry_log_id,lane:{$lane_info['name']},plate no:{$plate_info['plate_no']},plate no2:{$plate_info2['plate_no']}");

        $attached_info = array();
        $final_result = array('status'=>false,'parking_type'=>9,'visitor_check_result'=>10,'check_result'=>'LS092','message'=>'vendor exit not processed');
        $unprocessed_code = 'LS092';
        // undetected
        $result = $this->entry_request_undetected($entry_log_id,$lane_info,$plate_info,$plate_info2,$attached_info);
        if ($result['check_result']!=$unprocessed_code) {
            $final_result = $result;
            // udpate undetected check result to database
            $parking_type = $result['parking_type'];
            $code = $this->check_result_to_integer($result['check_result']);
            $remark = $result['check_result'].'-'.$result['message'];
            \App\EntryLog::update_checked_result2($entry_log_id,$code,$parking_type,$remark,'',null,null,null,null,null,null);
            if ($result['status']==true) {
                return $result;
            }
        }
        // white list check
        $result = $this->entry_request_white_list($entry_log_id,$lane_info,$plate_info,$plate_info2,$attached_info);
        if ($result['check_result']!=$unprocessed_code) {
            $final_result = $result;
            // udpate white list check result to database
            $parking_type = $result['parking_type'];
            $code = $this->check_result_to_integer($result['check_result']);
            $remark = $result['check_result'].'-'.$result['message'];
            \App\EntryLog::update_checked_result2($entry_log_id,$code,$parking_type,$remark,'',null,null,null,null,'white_list_check_result',$code);
            if ($result['status']==true) {
                return $result;
            }
        }
        // season check
        $result = $this->entry_request_season($entry_log_id,$lane_info,$plate_info,$plate_info2,$attached_info);
        if ($result['check_result']!=$unprocessed_code) {
            $final_result = $result;
            // udpate season check result to database
            $parking_type = $result['parking_type'];
            $code = $this->check_result_to_integer($result['check_result']);
            $remark = $result['check_result'].'-'.$result['message'];
            $season_check_result = $result['season_check_result'];
            $user_id = null;
            $locked_flag = null;
            $auto_deduct_flag = null;
            $wallet_balance = null;
             if (isset($result['bind_user_info'])) {
                $bind_user_info = $result['bind_user_info'];
                $user_id = $bind_user_info['user_id'];
                $locked_flag = $bind_user_info['locked_flag'];
                $auto_deduct_flag = $bind_user_info['auto_deduct_flag'];
                $wallet_balance = $bind_user_info['wallet_balance'];
            }
            \App\EntryLog::update_checked_result2($entry_log_id,$code,$parking_type,$remark,'',
                $user_id,$locked_flag,$auto_deduct_flag,$wallet_balance,'season_check_result',$season_check_result);
            if ($result['status']==true) {
                return $result;
            }
        }
        // visitor check
        $result = $this->entry_request_visitor($entry_log_id,$lane_info,$plate_info,$plate_info2,$attached_info);
        if ($result['check_result']!=$unprocessed_code) {
            $final_result = $result;
            // udpate visitor  result to database
            $parking_type = $result['parking_type'];
            $code = $this->check_result_to_integer($result['check_result']);
            $remark = $result['check_result'].'-'.$result['message'];
            $visitor_check_result = $result['visitor_check_result'];
            $vendor_ticket_id = isset($result['vendor_ticket_id'])?$result['vendor_ticket_id']:'';
            $user_id = null;
            $locked_flag = null;
            $auto_deduct_flag = null;
            $wallet_balance = null;
            if (isset($result['bind_user_info'])) {
                $bind_user_info = $result['bind_user_info'];
                $user_id = $bind_user_info['user_id'];
                $locked_flag = $bind_user_info['locked_flag'];
                $auto_deduct_flag = $bind_user_info['auto_deduct_flag'];
                $wallet_balance = $bind_user_info['wallet_balance'];
            }
            \App\EntryLog::update_checked_result2($entry_log_id,$code,$parking_type,$remark,$vendor_ticket_id,
                $user_id,$locked_flag,$auto_deduct_flag,$wallet_balance,'visitor_check_result',$visitor_check_result);
            return $result;
        }
        return $final_result;
    }

    /**
     * exit request for undetected
     */
    protected function exit_request_undetected($entry_log_id,$lane_info,$plate_info,$plate_info2,&$attached_info)
    {
        // only one plate no for the undetected case
        if(empty($plate_info['plate_no']) || $plate_info['plate_no']== "_NONE_"){
            return array('status'=>true,'parking_type'=>9,'check_result'=>'LS013','message'=>'plate no is none');
        }
        $code_not_processed = 'LS092';
        return array('status'=>false,'parking_type'=>9,'check_result'=>$code_not_processed,'message'=>'undetected not processed');

    }
    /**
     * exit request for white list
     */
    protected function exit_request_white_list($entry_log_id,$lane_info,$plate_info,$plate_info2,&$attched_info)
    {
        Log::info("[exit_request_white_list] entry id:$entry_log_id,enter");

        $wl_info = \App\WhiteList::searchRecordByPlateNo($plate_info['plate_no']);
        if (empty($wl_info) && !empty($plate_info2)) {
            $wl_info = \App\WhiteList::searchRecordByPlateNo($plate_info2['plate_no']);
        }

        if (empty($wl_info)) {
            Log::warning("[exit_request_white_list] entry id:$entry_log_id,not in white list");
            return array('status'=>false,'parking_type'=>9,'check_result'=>'LS045','message'=>'not in white list');
        }

        Log::info("[exit_request_white_list] entry id:$entry_log_id,white list matched plate no:{$wl_info->plate_no}");

        if ($wl_info->enable_flag==0) {
            Log::warning("[exit_request_white_list] entry id:$entry_log_id,white list disabled");
            return array('status'=>false,'used_plate_no'=>$wl_info->plate_no,'parking_type'=>9,'check_result'=>'LS046','message'=>'disabled in white list');
        }

        if (!empty($wl_info->valid_from)) {
            // valid from process as date, no time
            $validFrom = date('Y-m-d 00:00:00', strtotime($wl_info->valid_from));
            if(date('Y-m-d H:i:s') < $validFrom) {
                Log::warning("[exit_request_white_list] entry id:$entry_log_id,white list valid from:{$wl_info->valid_from} expired.");
                return array('status'=>false,'used_plate_no'=>$wl_info->plate_no,'parking_type'=>9,'check_result'=>'LS047','message'=>'valid from expired');
            }
        }

        if (!empty($wl_info->valid_until)) {
            // valid until process as date, no time
            $validUntil = date('Y-m-d 23:59:59', strtotime($wl_info->valid_until));
            if(date('Y-m-d H:i:s') > $validUntil) {
                Log::warning("[exit_request_white_list] entry id:$entry_log_id,white list valid until:{$wl_info->valid_until} expired.");
                return array('status'=>false,'used_plate_no'=>$wl_info->plate_no,'parking_type'=>9,'check_result'=>'LS047','message'=>'valid until expired');
            }
        }

        $plate_no2 = isset($plate_info2['plate_no'])?$plate_info2['plate_no']:'';
        Log::info("[exit_request_white_list] entry id:$entry_log_id,white list success");
        $update_result = \App\CarInSite::car_out2($entry_log_id,$plate_info['plate_no'],$plate_no2);
        return array('status'=>true,'used_plate_no'=>$wl_info->plate_no,'parking_type'=>2,'check_result'=>'LS048','message'=>'white list success');
    }

    /**
     * exit request for season
     */
    protected function exit_request_season($entry_log_id, $lane_info, $plate_info, $plate_info2, &$attched_info)
    {
        Log::info("[exit_request_season] entry id:$entry_log_id,enter");

        $plate_no2 = isset($plate_info2['plate_no']) ? $plate_info2['plate_no'] : '';
        $used_plate_no = $plate_info['plate_no'];
        // get season card info
        $season_info = \App\SmcHolderInfo::getHolderInfo($plate_info['plate_no']);
        if (empty($season_info) && !empty($plate_no2)) {
            $season_info = \App\SmcHolderInfo::getHolderInfo($plate_no2);
            $used_plate_no = $plate_no2;
            $second_plate_no = $plate_info['plate_no'];
        }
        if (empty($season_info)) {
            Log::info("[exit_request_season] entry id:$entry_log_id,not a season pass");
            return array('status'=>false,'parking_type'=>9,'season_check_result'=>0,'check_result'=>'LS026','message'=>'not a season pass');
        }

        $car_in_site = null;
        // get car in site record
        if (isset($attched_info['car_in_site'])) {
            $car_in_site = $attched_info['car_in_site'];
        } else {
            //use season pass no first
            Log::info("[exit_request_season] entry id:$entry_log_id,check car in site for season plate no $used_plate_no");
            $car_in_site = \App\CarInSite::getUnLeftRecordByPlateNo2($used_plate_no);

            if ($car_in_site) {
                $attched_info['car_in_site'] = $car_in_site;
            }
        }

        if (empty($car_in_site)){
            Log::warning("[exit_request_season] entry id:$entry_log_id,no entry record");
            return array('status'=>true,'parking_type'=>9,'season_check_result'=>11,'check_result'=>'LS007','message'=>'no entry record');
        }
        $attched_info['car_in_site'] = $car_in_site;
        if ($car_in_site->parking_type!=1) {
            Log::warning("[exit_request_season] entry id:$entry_log_id,record parking type is not season");
            return array('status'=>false,'used_plate_no'=>$used_plate_no,'parking_type'=>9,'season_check_result'=>0,'check_result'=>'LS026','message'=>'not season');
        }

        if ($car_in_site->locked_flag==1) {
            Log::warning("[exit_request_season] entry id:$entry_log_id,record is locked");
            return array('status'=>true,'used_plate_no'=>$used_plate_no,'parking_type'=>9,'season_check_result'=>12,'check_result'=>'LS008','message'=>'car locked');
        }

        Log::info("[exit_request_season] entry id:$entry_log_id,season success");
        \App\CarInSite::car_out2($entry_log_id,$used_plate_no,'');
        // TODO, need update is_success or not?
        return array('status'=>true,'used_plate_no'=>$used_plate_no,'parking_type'=>1,'season_check_result'=>13,'check_result'=>'LS009','message'=>'season success');
    }

    /**
     * exit request for visitor
     */
    protected function exit_request_visitor($entry_log_id,$lane_info,$plate_info,$plate_info2,&$attched_info,$leave_type)
    {
        Log::info("[exit_request_visitor] entry id:$entry_log_id,enter");

        $plate_no2 = isset($plate_info2['plate_no'])?$plate_info2['plate_no']:'';

        $car_in_site = null;
        // get car in site record
        if (isset($attched_info['car_in_site'])) {
            $car_in_site = $attched_info['car_in_site'];
        } else {
            $car_in_site = \App\CarInSite::getUnLeftRecordByPlateNo2($plate_info['plate_no']);
            if (empty($car_in_site) && $plate_info2 ) {
                $car_in_site = \App\CarInSite::getUnLeftRecordByPlateNo2($plate_info2['plate_no']);
            }
            if ($car_in_site) {
                $attched_info['car_in_site'] = $car_in_site;
            }
        }

        if ($car_in_site!=null && $car_in_site->locked_flag==1) {
            Log::warning("[exit_request_visitor] entry id:$entry_log_id,record is locked");
            return array('status'=>true,'parking_type'=>9,'visitor_check_result'=>20,'check_result'=>'LS008','message'=>'car locked');
        }

        #sent exit request to vendor
        $kiple_user_id = '';
        if ($car_in_site!=null) {
            $kiple_user_id = $car_in_site->user_id;
        }
        $result = \App\Http\Controllers\API\VendorHubController::exit_request_visitor($plate_info['plate_no'],
            $plate_no2,$kiple_user_id,$lane_info['ext_cam_ref_id'],$lane_info['in_out_flag'],
            $entry_log_id,$plate_info['big_picture'],$car_in_site,$leave_type);

        Log::info("[exit_request_visitor] entry id:$entry_log_id,vendor result:".json_encode($result));

        if($result['status']==true){
            $vendor_ticket_id = isset($result['vendor_ticket_id'])?$result['vendor_ticket_id']:'';
            $update_result = 0;
            // try car out using vendor ticket id first
            if (!empty($vendor_ticket_id)) {
                Log::info("[exit_request_visitor] entry id:$entry_log_id, car out using vendor ticket id:{$vendor_ticket_id}");
                $update_result = \App\CarInSite::car_out_with_vendor_ticket($entry_log_id,$vendor_ticket_id);
                if ($update_result<1) {
                    Log::warning("[exit_request_visitor] entry id:$entry_log_id,car out using vendor ticket id failed");
                }
            }
            // try car out using plate no
            if ($update_result<1) {
                $update_result = \App\CarInSite::car_out2($entry_log_id,$plate_info['plate_no'],$plate_no2);
            }
            if ($update_result<1) {
                Log::warning("[exit_request_visitor] entry id:$entry_log_id,car out failed");
            }
        }
        return $result;
    }


    /**
     * exit request
     */
    protected function exit_request($entry_log_id,$lane_info,$plate_info,$plate_info2)
    {
        $attched_info = array();
        $final_result = array('status'=>false,'parking_type'=>9,'visitor_check_result'=>10,'check_result'=>'LS092','message'=>'vendor exit not processed');
        $unprocessed_code = 'LS092';
        // undetected
        $result = $this->exit_request_undetected($entry_log_id,$lane_info,$plate_info,$plate_info2,$attached_info);
        if ($result['check_result']!=$unprocessed_code) {
            // udpate white list check result to database
            $parking_type = $result['parking_type'];
            $code = $this->check_result_to_integer($result['check_result']);
            $remark = $result['check_result'].'-'.$result['message'];
            \App\EntryLog::update_checked_result2($entry_log_id,$code,$parking_type,$remark,'',
                null,null, null,null,'white_list_check_result',$code);
            if ($result['status']==true) {
                return $result;
            }
        }

        // white list exit request
        $result = $this->exit_request_white_list($entry_log_id,$lane_info,$plate_info,$plate_info2,$attched_info);
        if ($result['check_result']!=$unprocessed_code) {
            $final_result = $result;
            // update white list check result to database
            $parking_type = $result['parking_type'];
            $code = $this->check_result_to_integer($result['check_result']);
            $remark = $result['check_result'].'-'.$result['message'];
            \App\EntryLog::update_checked_result2($entry_log_id,$code,$parking_type,$remark,'',
                null,null,null,null,'white_list_check_result',$code);
            if ($result['status']==true) {
                return $result;
            }
        }

        // season exit request
        $result = $this->exit_request_season($entry_log_id,$lane_info,$plate_info,$plate_info2,$attched_info);
        if ($result['check_result']!=$unprocessed_code) {
            $final_result = $result;
            // update season check result to database
            $parking_type = $result['parking_type'];
            $code = $this->check_result_to_integer($result['check_result']);
            $remark = $result['check_result'].'-'.$result['message'];
            $season_check_result = $result['season_check_result'];
            // from the car in site record get the bind user info
            $user_id = null;
            $locked_flag = null;
            $auto_deduct_flag = null;
            $wallet_balance = null;
            if (isset($attched_info['car_in_site'])) {
                $bind_user_info = $attched_info['car_in_site'];
                $user_id = $bind_user_info->user_id;
                $locked_flag = $bind_user_info->locked_flag;
                $auto_deduct_flag = $bind_user_info->auto_deduct_flag;
            }
            \App\EntryLog::update_checked_result2($entry_log_id,$code,$parking_type,$remark,'',
                $user_id,$locked_flag,$auto_deduct_flag,$wallet_balance,'season_check_result',$season_check_result);
            if ($result['status']==true) {
                return $result;
            }
        }

        // visitor exit request
        $result = $this->exit_request_visitor($entry_log_id,$lane_info,$plate_info,$plate_info2,$attched_info,0);
        if ($result['check_result']!=$unprocessed_code) {
            $final_result = $result;
            // update visitor check result to database
            $parking_type = $result['parking_type'];
            $code = $this->check_result_to_integer($result['check_result']);
            $remark = $result['check_result'].'-'.$result['message'];
            $visitor_check_result = $result['visitor_check_result'];
            $vendor_ticket_id = isset($result['vendor_ticket_id'])?$result['vendor_ticket_id']:'';
            // from the car in site record get the bind user info
            $user_id = null;
            $locked_flag = null;
            $auto_deduct_flag = null;
            $wallet_balance = null;
            if (isset($attched_info['car_in_site'])) {
                $bind_user_info = $attched_info['car_in_site'];
                $user_id = $bind_user_info->user_id;
                $locked_flag = $bind_user_info->locked_flag;
                $auto_deduct_flag = $bind_user_info->auto_deduct_flag;
            }
            \App\EntryLog::update_checked_result2($entry_log_id,$code,$parking_type,$remark,$vendor_ticket_id,
                $user_id,$locked_flag,$auto_deduct_flag,$wallet_balance,'visitor_check_result',$visitor_check_result);
            if ($result['status']==true) {
                return $result;
            }
        }
        return $final_result;
    }


    /**
     * manual exit request
     */
    protected function manual_exit_request($entry_log_id,$lane_info,$plate_info)
    {
        $attched_info = array();
        $final_result = array('status'=>false,'parking_type'=>9,'visitor_check_result'=>10,'check_result'=>'LS092','message'=>'vendor exit not processed');
        $unprocessed_code = 'LS092';
        // white list
        $result = $this->exit_request_white_list($entry_log_id,$lane_info,$plate_info,null,$attched_info);
        if ($result['check_result']!=$unprocessed_code) {
            $parking_type = $result['parking_type'];
            $code = $this->check_result_to_integer($result['check_result']);
            $remark = $result['check_result'].'-'.$result['message'];
            \App\EntryLog::update_checked_result2($entry_log_id,$code,$parking_type,$remark,'',
                null,null,null,null,'white_list_check_result',$code);
            if ($result['status']==true) {
                return $result;
            }
        }
        // season
        $result = $this->exit_request_season($entry_log_id,$lane_info,$plate_info,null,$attched_info);
        if ($result['check_result']!=$unprocessed_code) {
            $parking_type = $result['parking_type'];
            $code = $this->check_result_to_integer($result['check_result']);
            $remark = $result['check_result'].'-'.$result['message'];
            $season_check_result = $result['season_check_result'];

            // from the car in site record get the bind user info
            $user_id = null;
            $locked_flag = null;
            $auto_deduct_flag = null;
            $wallet_balance = null;
            if (isset($attched_info['car_in_site'])) {
                $bind_user_info = $attched_info['car_in_site'];
                $user_id = $bind_user_info->user_id;
                $locked_flag = $bind_user_info->locked_flag;
                $auto_deduct_flag = $bind_user_info->auto_deduct_flag;
            }
            \App\EntryLog::update_checked_result2($entry_log_id,$code,$parking_type,$remark,'',
                $user_id,$locked_flag,$auto_deduct_flag,$wallet_balance,'season_check_result',$season_check_result);
            if ($result['status']==true) {
                return $result;
            }
            // season no entry record case, we still need to ask vendor to check in manual exit case
            if ($result['status']==true && $result['check_result']!='LS007' ) {
                return $result;
            }
        }
        // visitor
        $result = $this->exit_request_visitor($entry_log_id,$lane_info,$plate_info,'',$attched_info,1);
        if ($result['check_result']!=$unprocessed_code) {
            $parking_type = $result['parking_type'];
            $code = $this->check_result_to_integer($result['check_result']);
            $remark = $result['check_result'].'-'.$result['message'];
            $visitor_check_result = $result['visitor_check_result'];
            $vendor_ticket_id = isset($result['vendor_ticket_id'])?$result['vendor_ticket_id']:'';
            // from the car in site record get the bind user info
            $user_id = null;
            $locked_flag = null;
            $auto_deduct_flag = null;
            $wallet_balance = null;
            if (isset($attched_info['car_in_site'])) {
                $bind_user_info = $attched_info['car_in_site'];
                $user_id = $bind_user_info->user_id;
                $locked_flag = $bind_user_info->locked_flag;
                $auto_deduct_flag = $bind_user_info->auto_deduct_flag;
            }
            \App\EntryLog::update_checked_result2($entry_log_id,$code,$parking_type,$remark,$vendor_ticket_id,
                $user_id,$locked_flag,$auto_deduct_flag,$wallet_balance,'visitor_check_result',$visitor_check_result);
            if ($result['status']==true) {
                return $result;
            }
        }
        return $final_result;
    }

    /**
     * process plate no push request, support dual camera
     */
    protected function process_plate_no_push($lane_info,$plate_info,$plate_info2)
    {
        Log::info("[process_plate_no_push] enter, lane:{$lane_info['name']},plate no:{$plate_info['plate_no']},plate no2:{$plate_info2['plate_no']}");
        $entry_log_id = \App\EntryLog::insert_log($plate_info,$plate_info2,$lane_info);
        if ($entry_log_id==false) {
            Log::error("[process_plate_no_push] insert log failed");
            return array('status'=>true,'check_result'=>'LS091','message'=>'insert entry log failed');
        }
        Log::info("[process_plate_no_push] entry id:{$entry_log_id}");
        if ($lane_info['in_out_flag']==0) {
            return $this->entry_request($entry_log_id,$lane_info,$plate_info,$plate_info2);
        }
        if ($lane_info['in_out_flag']==1) {
            return $this->exit_request($entry_log_id,$lane_info,$plate_info,$plate_info2);
        }
        return array('status'=>true,'check_result'=>'LS091','message'=>'in out flag error');
    }

    /**
     * pre process plate no push, for dual camera case
     * @param array $plate_info the plate information array
     * @param array $lane_cameras all the cameras at the same line
     * @param array $lane_records all the camera records at the same line
     * @return array
     */
    protected function pre_process_plate_no_push($plate_info,&$lane_cameras,&$lane_records)
    {
        Log::info("[pre_process_plate_no_push] enter, plate info:".json_encode($plate_info));
        // get all cameras
        $cameras = \App\CameraDetail::getAllCameras();
        if (empty($cameras)) {
            log::error("[pre_process_plate_no_push] no cameras");
            $lane_info = array(
                'lane_id' => -1,
                'in_out_flag' => 0,
                'parking_type_flag' => 0,
                'ext_cam_ref_id' => 0,
            );
            $pre_entry_id = \App\PreEntryLog::insert_log($plate_info,$lane_info,1);
            if ($pre_entry_id==false) {
                log::error("[pre_process_plate_no_push] insert log failed.");
            }
            return array('status'=>false,'check_result'=>'LS001','message'=>'no cameras');
        }
        // find the camera that push the plate no
        $camera = null;
        foreach($cameras as $c) {
            if ($c->camera_sn==$plate_info['camera_sn']) {
                $camera = $c;
                break;
            }
        }
        if (empty($camera)) {
            log::error("[push_plate_no] can not find the specified camera");
            $lane_info = array(
                'lane_id' => -1,
                'in_out_flag' => 0,
                'parking_type_flag' => 0,
                'ext_cam_ref_id' => 0,
            );
            $pre_entry_id = \App\PreEntryLog::insert_log($plate_info,$lane_info,1);
            if ($pre_entry_id==false) {
                log::error("[pre_process_plate_no_push] insert log failed.");
            }
            return array('status'=>false,'check_result'=>'LS001','message'=>'specified camera not found');
        }

        $lane_info = array(
            'lane_id' => $camera->lane_id,
            'name' => $camera->lane_name,
            'in_out_flag' => $camera->in_out_flag,
            'parking_type_flag' => $camera->parking_type_flag,
            'ext_cam_ref_id' => $camera->ext_cam_ref_id,
        );
        // find the other cameras under same lane
        $lane_cameras = array();
        foreach($cameras as $c) {
            if ($c->lane_id==$camera->lane_id) {
                $lane_cameras[] = $c;
            }
        }
        $lane_camera_count = count($lane_cameras);
        log::info("[pre_process_plate_no_push] lane:{$camera->lane_id} has {$lane_camera_count} cameras");
        if ($lane_camera_count==1) {
            // only one camera for this lane,
            $pre_entry_id = \App\PreEntryLog::insert_log($plate_info,$lane_info,1);
            if ($pre_entry_id==false) {
                log::error("[pre_process_plate_no_push] insert log failed.");
                return array('status'=>false,'check_result'=>'LS091','message'=>'insert pre entry failed');
            }
            $plate_info['pre_entry_id'] = $pre_entry_id;
            // create lane records to return
            $lane_record = new \stdClass();
            $lane_record->id = $pre_entry_id;
            $lane_record->plate_no = $plate_info['plate_no'];
            $lane_record->camera_sn = $plate_info['camera_sn'];
            $lane_records[] = $lane_record;
            return $this->process_plate_no_push($lane_info,$plate_info,null);
        }

        // more cameras on the same line, need to merge
        $pre_entry_id = \App\PreEntryLog::insert_log($plate_info,$lane_info,0);
        if ($pre_entry_id==false) {
            log::error("[pre_process_plate_no_push] insert log failed.");
            return array('status'=>false,'check_result'=>'LS091','message'=>'insert pre entry failed');
        }
        $plate_info['pre_entry_id'] = $pre_entry_id;



        // sleep x miliseconds to wait other camera's push
        $ini_array = parse_ini_file("../cron/config.ini",true);
        $camera_wait_time =  $ini_array['common']['CAM_WAIT_TIME'];
        $db_wait_time =  $ini_array['common']['DATABASE_CHECK_NEXT_WAIT'];
        log::info("[pre_process_plate_no_push] start to wait:$camera_wait_time",$plate_info);
        $start_time = time();
        $from_id = $pre_entry_id-(count($cameras)+1);

        while(true){
            if ((time() - $start_time) < $camera_wait_time) {
                //get lane records
                $lane_records = \App\PreEntryLog::get_unprocessed_records($from_id,$camera->lane_id);
                $lane_records_array = json_decode(json_encode($lane_records),true);
                $record = count($lane_records_array);
                if($record == 2){
                    log::info("[pre_process_plate_no_push] stop to wait, already got 2 record:$camera_wait_time",$plate_info);
                    break;
                }
                usleep($db_wait_time);
            }
            else{
                log::info("[pre_process_plate_no_push] stop to wait, after reach time limit");
                break;
            }
        }

        // get all the unprocessed records at the same lane
        // e.g: the current entry id is 100, total camera count is 6, then we check from 100-(6+1)
        // then make sure each camera can get same all unprocessed records with efficent way
        $from_id = $pre_entry_id-(count($cameras)+1);
        $lane_records = \App\PreEntryLog::get_unprocessed_records($from_id,$camera->lane_id);
        log::info("[pre_process_plate_no_push] get unprocessed records.".json_encode($lane_records));
        if (empty($lane_records) || count($lane_records)==0) {
            log::warning("[pre_process_plate_no_push] no unprocessed pre entry records, another camera has processed");
            // means another camera already processed
            // TODO, this camera do not show any message, since processed by another one
            return array('status'=>false,'check_result'=>'LS090','message'=>'no unprocessed pre entry records');
        }

        // try to update these records with processed, only one camera can update succeeded
        $update_ids = array();
        $plate_info2 = null;
        foreach($lane_records as $r) {
            $update_ids[] = $r->id;
            // get the second record info;
            if ($r->id!=$pre_entry_id) {
                $plate_info2 = array(
                    'plate_no' => $r->plate_no,
                    'camera_sn' => $r->camera_sn,
                    'car_color' => $r->car_color,
                    'big_picture' => $r->big_picture,
                    'small_picture' => $r->small_picture,
                    'lpr_id' => $r->lpr_id,
                    'pre_entry_id' => $r->id,
                );
            }
        }
        $updated_number = \App\PreEntryLog::updateUnprocessedRecords($update_ids);
        if ($updated_number!=count($update_ids)) {
            log::warning("[pre_process_plate_no_push] updated number:$updated_number,another camera has processed");
            // update failed, that means another camera get succeeded.
            return array('status'=>false,'check_result'=>'LS090','message'=>'update failed, another one has processed');
        }

        if (!empty($plate_info2)) {
            if ($plate_info2['plate_no']==$plate_info['plate_no']) {
                // two camera captured same plate no
                log::info("[pre_process_plate_no_push] two camara capture same plate number");
                // the first plate no is alwasy the front one
                if ($camera->position!=0) {
                    $plate_info = $plate_info2;
                }
                return $this->process_plate_no_push($lane_info,$plate_info,null);
            }
            if ($plate_info['plate_no']=="_NONE_") {
                log::info("[pre_process_plate_no_push] two camara capture one is _NONE_");
                return $this->process_plate_no_push($lane_info,$plate_info2,null);
            }
            if ($plate_info2['plate_no']=="_NONE_") {
                log::info("[pre_process_plate_no_push] two camara capture one is _NONE_");
                return $this->process_plate_no_push($lane_info,$plate_info,null);
            }
            log::info("[pre_process_plate_no_push] two camara capture different plate number:{$plate_info2['plate_no']},{$plate_info['plate_no']}");
            // the first plate no is always the front one
            if ($camera->position!=0) {
                $temp = $plate_info;
                $plate_info = $plate_info2;
                $plate_info2 = $temp;
            }
            return $this->process_plate_no_push($lane_info,$plate_info,$plate_info2);
        }
        return $this->process_plate_no_push($lane_info,$plate_info,null);
    }

    /**
     * encode the led message
     */
    protected function encode_led_message($message,$plateNo)
    {
        if ($message=='%%plate_no%%') {
            $message = $plateNo;
        }
        return base64_encode($message);
    }

    /**
     * generate camera operation request
     */
    protected function generate_camera_request($camera_sn,$id,$plate_no,$code)
    {
        $operations = \App\LprParam::get_param("lpr_operation");
        $operations=json_decode($operations,true);

        if (!isset($operations[$code])) {
            // no operations for this code;
            return false;
        }

        $open_gate = $operations[$code]["open_gate"];
        $messages = $operations[$code]["message"];

        $operators = array();

        $disabled_open_gate=config('custom.lpr_disabled_open_gate');
        if ($disabled_open_gate) {
            $open_gate = false;
        }

        if ($open_gate) {
            $operators[] = array(
                'type' => 'open_gate'
            );
        }

        $ledLine1 = $this->encode_led_message($messages[0],$plate_no);
        $ledLine2 = $this->encode_led_message($messages[1],$plate_no);
        $operators[] = array(
            'type' => 'led_display',
            'messages'=> array(
                'line1'=> $ledLine1,
                'line1_org'=> $messages[0],
                'line2'=> $ledLine2,
                'line2_org'=> $messages[1],
            ),
            'time' => 30,
        );

        $response = array(
            'id' =>$id,
            'sn' => $camera_sn,
            'code' => $code,
            'operator' => $operators,
        );
        return $response;
    }

    /**
     * send operation request to camera
     */
    protected function send_camera_operation($camera_sn,$id,$plate_no,$code)
    {
        Log::info("[send_camera_operation] enter,code:$code,plate_no:$plate_no");

        $request = $this->generate_camera_request($camera_sn,$id,$plate_no,$code);
        if ($request==false){
            Log::info("[send_camera_operation] generate request failed");
            return false;
        }
        Log::info("[send_camera_operation] request:".json_encode($request));
        try{
            $json_payload=json_encode($request);
            $client = new \GuzzleHttp\Client([
                'verify' => false
            ]);
            $body = \GuzzleHttp\Psr7\stream_for(json_encode($request));
            $url="http://".config('custom.lpr_backend_host').":".config('custom.lpr_backend_port')."/v1/device/operation";
            Log::info("[send_camera_operation] url:$url");
            $response = $client->request('PUT', $url,
                ['body' => $body, 'headers'  => ['Content-Type' => 'application/json'],'connect_timeout'=> 1,'timeout' => 1]);
            if ($response->getStatusCode()==200) {
                return true;
            }
            Log::error("[send_camera_operation] return failed");
        } catch (\Exception $e){
            Log::error("[send_camera_operation] get exeption:$e");
        }
        return false;
    }

    /**
     * push plate no
     */
    public function push_plate_no(Request $request)
    {
        log::info("[push_plate_no] = $request");
        // get plate no information
        $body2=file_get_contents('php://input');
        $json_body2=json_decode($body2,true);
        $plate_no=$json_body2["body"]["result"]["PlateResult"]["license"];
        $plate_no = $this->map_plate_no($plate_no);

        $plate_info = array(
            'plate_no' => $plate_no,
            'camera_sn' => $json_body2["body"]["vzid"]["sn"],
            'car_color' => $json_body2["body"]["result"]["PlateResult"]["carColor"],
            'big_picture' => $json_body2["body"]["result"]["PlateResult"]["imageFilePath"],
            'small_picture' => $json_body2["body"]["result"]["PlateResult"]["imageFragmentFilePath"],
            'lpr_id' => $json_body2["id"],
        );
        $lane_cameras = array();
        $lane_records = array();
        $result = $this->pre_process_plate_no_push($plate_info,$lane_cameras,$lane_records);
        Log::info("[push_plate_no] result:".json_encode($result));
        // do not need to show led string since another camera will process
        if ($result['check_result']=='LS090') {
            return response()->json($result, 200);
        }
        // dual camera case
        if (count($lane_cameras)>0) {
            // get one plate no that not equal _NONE_
            $solid_plate_no = '_NONE_';
            foreach($lane_records as $r) {
                if ($r->plate_no!='_NONE_') {
                    $solid_plate_no = $r->plate_no;
                    break;
                }
            }

            // for dual camera case, $lane_camers should have two records,
            // but the lane_records may just have one record, so we need to show
            // this record's plate no to two cameras
            foreach($lane_cameras as $c) {
                $plate_no = '';
                foreach($lane_records as $r) {
                    // the camera may not have lane record, so just use the first one
                    $plate_no = $r->plate_no;
                    if ($r->camera_sn==$c->camera_sn) {
                        break;
                    }
                }
                if (empty($plate_no) || $plate_no=='_NONE_' ){
                    $plate_no = $solid_plate_no;
                }
                // for white list/season expired cases, we need show the used plate no;
                if (isset($result['used_plate_no'])) {
                    $plate_no = $result['used_plate_no'];
                }
                $this->send_camera_operation($c->camera_sn,'',$plate_no,$result['check_result']);
            }
        } else {
            $this->send_camera_operation($plate_info['camera_sn'],'',$plate_no,$result['check_result']);
        }
        return response()->json($result, 200);
    }

    /**
     * manual push plate no
     */
    public function manual_push_plate_no(Request $request)
    {
        $entry_log_id = $request->entry_log_id;
        $plate_no = $request->plate_no;
        $manual_leave_remark = $request->remark;
        if ($manual_leave_remark===null) {
            $manual_leave_remark = '';
        }
        $current_user = $request->user();
        $manual_leave_by = $current_user->email;
        log::info("[manual_push_plate_no] id:$entry_log_id,plate_no:$plate_no,remark:$manual_leave_remark,by user:$manual_leave_by");

        $previous_entry_record = \App\EntryLog::last_entry_logs_detail($entry_log_id);
        if (empty($previous_entry_record)) {
            log::error("[manual_push_plate_no] can not find entry log id");
            $response = array(
                'code' => 1,
                'success' => 'false',
                'error' => 'invalid_paramter',
                'message' => 'can not find entry log id',
            );
            return response()->json($response, 200);
        }

        if ($previous_entry_record->in_out_flag!=1) {
            log::error("[manual_push_plate_no] invalid lane in out flag");
            $response = array(
                'code' => 1,
                'success' => 'false',
                'error' => 'invalid_paramter',
                'message' => 'invalid lane in out flag',
            );
            return response()->json($response, 200);
        }

        $plate_info = array(
            'plate_no' => $plate_no,
            'camera_sn' => $previous_entry_record->camera_sn,
            'car_color' => $previous_entry_record->car_color,
            'big_picture' => $previous_entry_record->big_picture,
            'small_picture' =>  $previous_entry_record->small_picture,
            'lpr_id' => '',
        );

        $lane_detail= \App\LaneConfig::getLaneDetailById($previous_entry_record->lane_id);
        $lane_info = array(
            'lane_id' => $lane_detail->id,
            'name' => $lane_detail->name,
            'in_out_flag' => $lane_detail->in_out_flag,
            'parking_type_flag' => $lane_detail->parking_type_flag,
            'ext_cam_ref_id' => $lane_detail->ext_cam_ref_id,
        );

        $entry_log_id =  \App\EntryLog::create_manual_leave($plate_info,$lane_info,$manual_leave_by,$manual_leave_remark);
        if ($entry_log_id==false) {
            log::error("[manual_push_plate_no] create new entry log failed");
            $response = array(
                'code' => 1,
                'success' => 'false',
                'error' => 'database_error',
                'message' => 'create new entry log failed',
            );
            return response()->json($response, 200);
        }

        Log::info("[manual_push_plate_no] entry id:$entry_log_id,plate no:{$plate_info['plate_no']}");
        $result = $this->manual_exit_request($entry_log_id,$lane_info,$plate_info);
        Log::info("[manual_push_plate_no] result:".json_encode($result));

        $success = $this->send_camera_operation($plate_info['camera_sn'],'',$plate_no,$result['check_result']);
        $response = array(
            'code' => $success?0:1,
            'success' => $success,
            'error' => $success?'success':'failed',
            'message' => 'manual exit '. ($success?'success':'failed'),
        );
        return response()->json($response, 200);
    }

    /**
     * push camera state
     */
    public function push_state(Request $request)
    {

        $body2=file_get_contents('php://input');
        $json_body2=json_decode($body2,true);
        Log::info("[push_state] receive state:",$json_body2);

        $camera_sn=$json_body2["body"]["vzid"]["sn"];
        $ipaddress=$json_body2["body"]["vzid"]["ip_addr"];
        $state=$json_body2["body"]["vzid"]["state"];

        $result = \App\CameraDetail::updateCameraState($camera_sn,$ipaddress,$state,$body2);

        $response = array(
            'success' => $result<1?false:true,
            'error' => $result<1?'failed':'success',
            'message' => 'update state to database '.($result<1?'failed':'success'),
        );
        return response()->json($response, 200);
    }

}
