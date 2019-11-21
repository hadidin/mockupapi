<?php

namespace App\Http\Controllers\API\Vendor;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class KipleboxController extends Controller
{
    protected function check_result_to_integer($check_result){
        if (empty($check_result)) {
            return 0;
        }
        return intval(substr($check_result,2));
    }
    public static function entry_request($entry_id,$binded_user_info,$plate_no,$plate_no2){
        // check the record exist or not
        $carInParkRecord = DB::table('psm_car_in_site')
            ->select('id','parking_type','user_id','vendor_ticket_id')
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
        if(!empty($carInParkRecord)){
            return "TD".$carInParkRecord->vendor_ticket_id;
        }


        Log::notice("[kiplebox entry_request] car insite: record was not found for:  $plate_no,$plate_no2");
        //generate subticket 1 on payment ticket table
        $vendor_ticket_id = self::car_entry_by_kiplebox($entry_id,$binded_user_info,$plate_no,$plate_no2);
        return $vendor_ticket_id;
    }

    public static function car_entry_by_kiplebox($entry_id,$bind_user_info,$plate_no,$plate_no2){
        // create car in site record
        $user_id = '';
        $eticket_id = '';
        $locked_flag = 0;
        $auto_deduct_flag = 0;
        $wallet_balance = 0;
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
        $plate_no1 = $plate_no;
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

        while(true){
            // Start transaction
            DB::beginTransaction();
            //put car in site
            $car_insite = \App\CarInSite::car_in2($entry_id,0,'',$user_id,"",$locked_flag,
                $auto_deduct_flag,$wallet_balance,"",$plate_no1,$plate_no2);
            if($car_insite == false){
                log::error("[car_entry_by_kiplebox] rolling back ticket payment creation.failed on car_in2 car in site id = $car_insite");
                DB::rollback();
                return "TX00000000";
                break;
            }

            //create payment subticket
            //first subticket always start from 101
            $subticket_no = 101;
            $entry_detail = \App\EntryLog::get_logs_details($entry_id);
            $entry_time = $entry_detail->create_time;


            $entry_gp = $rate = \App\Http\Controllers\ParkingRateController::get_grace_period($entry_time,'entry');

            $subticket = $subticket_no.$car_insite;
            $start_time = $entry_time;
            $payment_id = \App\TicketPayment::insert_kiplebox_payment_ticket($car_insite,$subticket,$entry_time,$start_time,$entry_gp);
            if($payment_id == false){
                log::error("[car_entry_by_kiplebox] rolling back ticket payment creation. failed on insert_kiplebox_payment_ticket car in site id = $car_insite");
                DB::rollback();
                return "TX00000000";
                break;
            }

            //update subticket no to car insite table
            $update_eticket_id_to_car_insite = \App\CarInSite::update_eticket_id_to_car_insite($subticket,$car_insite);
            if($update_eticket_id_to_car_insite == false){
                log::error("[car_entry_by_kiplebox] rolling back ticket payment creation. failed on update_eticket_id_to_car_insite car in site id = $car_insite");
                DB::rollback();
                return "TX00000000";
                break;
            }

            DB::commit();
            log::info("[car_entry_by_kiplebox] ticket payment is created $subticket");
            return "TK".$subticket;
        }

    }

    public static function get_ticket_info($ticket_id){
        //get latest subticket
        $subticket_id = \App\CarInSite::get_vendor_ticket_id($ticket_id);
        //get details subticket info
        $ticket_details = \App\TicketPayment::get_ticket_details($subticket_id);
        if($ticket_details->isEmpty()){
            log::warning("[get_ticket_info] Cannot find ticket details ticket id =$ticket_id");
            //return invalid ticket
            $error_code = 'invalid_ticket';
            $failed_response = \App\Http\Controllers\ErrorCodeController::error_response('get_ticket_info',$error_code);
            return $failed_response;
        }
        /*
         * check is used or not
         * if is_used=1  then return ticket is used aka car already left building
         * if is_used=0 then check pay status
         *    if pay status = 0 || 6 then return parking fee
         *    if pay status =2 ,check grace period
         *       if grace not exceed return paid ticket
         *       if grace exceed then generate new subticket.return payment information
         */
        if($ticket_details[0]["is_used"] == 1){
            log::warning("[get_ticket_info] ticked is used =$ticket_id");
            //return invalid ticket
            $error_code = 'used_ticket';
            $failed_response = \App\Http\Controllers\ErrorCodeController::error_response('get_ticket_info',$error_code);
            return $failed_response;
        }
        if($ticket_details[0]["is_used"] == 0){
            //check pay status
            $pay_status = $ticket_details[0]["status"];
            if($pay_status == 0 || $pay_status == 6){
                return self::parking_fee($ticket_id);
            }
            elseif($pay_status == 2){
                //check grace period
                $payment_time = $ticket_details[0]["trx_date"];
                $endTime = new Carbon($payment_time);
                $grace_exceed_time = $endTime->addMinutes(10);
                $grace_exceed_time = $grace_exceed_time->toDateTimeString();
                $date_now = date("Y-m-d H:i:s");
                if($grace_exceed_time > $date_now){
                    //within grace period
                    log::warning("[get_ticket_info] ticket is paid ticket id =$ticket_id");
                    $error_code = 'paid_ticket';
                    $failed_response = \App\Http\Controllers\ErrorCodeController::error_response('get_ticket_info',$error_code);
                    return $failed_response;
                }
                else{
                    //exceed grace period
                    $entry_time = $ticket_details[0]["entry_time"];
                    log::warning("[get_ticket_info] grace periods is exceed");
                    $create_subticket = self::generate_new_subticket($ticket_id,$entry_time,$ticket_details[0]["trx_date"]);
                    if($create_subticket){
                        return self::parking_fee($ticket_id);
                    }
                    else{
                        log::warning("[get_ticket_info] failed to create new subticket =$ticket_id");
                        $error_code = 'failed_create_payment_ticket';
                        $failed_response = \App\Http\Controllers\ErrorCodeController::error_response('get_ticket_info',$error_code);
                        return $failed_response;
                    }
                    ###return self::parking_fee($ticket_id);
                }
            }
        }
    }
    public static function parking_fee($ticket_id){
        //get latest subticket
        $subticket_id = \App\CarInSite::get_vendor_ticket_id($ticket_id);
        //get details subticket info
        $ticket_details = \App\TicketPayment::get_ticket_details($subticket_id);
        $sub_number = substr($subticket_id,0,3);
        $entry_time = $ticket_details[0]["entry_time"];
        $request_time = date("Y-m-d H:i:s");
        $rate = \App\Http\Controllers\ParkingRateController::getParkingFee($entry_time, "TCN", $request_time);
        log::info("[parking_fee] receive for entry at $entry_time req_time at $request_time is  $rate");
        $rate = json_decode(json_encode($rate),true);
        $parking_fee = $rate['original']['amount'];

//        dd($last_start_time,$date_grace_end,$ticket_details);
        log::info("$ticket_id total from fee $entry_time to $request_time is $parking_fee");

        //get previous fee
        $tot_prev_paid_amount = 0;
        if($sub_number > 101){
            $previous_amount = \App\TicketPayment::get_previous_amount_paid($ticket_id);
            $tot_prev_paid_amount = $previous_amount[0]->amount;
        }

        $subticket_list = \App\TicketPayment::get_all_subticket_list($ticket_id);
        $subticket_list = json_decode(json_encode($subticket_list),true);

        $final_parking_fee = $parking_fee - $tot_prev_paid_amount;
        log::info("$ticket_id final fee is $final_parking_fee ($parking_fee minus previous paid amount of $tot_prev_paid_amount)");

        //todo if grace periods still not exceed then return 0 amount
        $last_start_time = $ticket_details[0]['start_time'];
        $grace_period = $ticket_details[0]['grace_period'];
        $date_grace_end_unix = strtotime("+$grace_period minute", strtotime($last_start_time));
        $date_grace_end = date("Y-m-d H:i:s", $date_grace_end_unix);
        if(time() < $date_grace_end_unix){
            log::info("$ticket_id is inside grace period. final fee is force to 0 ");
            $final_parking_fee = 0;
        }

        $data = array(
            "odata" => $ticket_id,
            "ticket" => $subticket_id,
            "entry" => $entry_time,
            "exit" => $request_time,
            "value" => $final_parking_fee,
            "subticket_list" => $subticket_list
        );
        $success_response = \App\Http\Controllers\ErrorCodeController::success_response($data);
        return $success_response;
    }
    public static function external_auth_payment($ticket_id,$amount,$auth_code,$payment_method){
        $payment_date = date("Y-m-d H:i:s");
        //get rate
        $data = \App\Http\Controllers\API\Vendor\KipleboxController::get_ticket_info($ticket_id);
        if($data['success'] == false){
            if($data['error'] == 'paid_ticket'){
                $error_code = 'paid_ticket';
            }
            else{
                $error_code = 'invalid_request';
            }
            //for error return by vendor .kiplebox
            $error_response = \App\Http\Controllers\ErrorCodeController::error_response('external_auth_payment',$error_code);
            log::error("external_auth_payment error by kiplebox");
            return $error_response;
        }

        //check used auth code
        $check_used_auth = \App\TicketPayment::check_used_auth($auth_code);
        if($check_used_auth->first() != null){
            $check_used_auth = $check_used_auth[0]["subticket"];
            $error_code = 'used_auth_code';
            $error_response = \App\Http\Controllers\ErrorCodeController::error_response('external_auth_payment',$error_code);
            log::error("external_auth_payment error by kiplebox.used auth code by subticket = $check_used_auth",$data);
            return $error_response;
        }

        $parking_fee = $data['data']['value'];
        $amount = $amount * 100;
        if($parking_fee != $amount) {
            $error_code = 'amount_different';
            $error_response = \App\Http\Controllers\ErrorCodeController::error_response('external_auth_payment',$error_code);
            log::warning("external_auth_payment error by kiplebox");
            return $error_response;
        }
        if($parking_fee == 0 ){
            $error_code = 'invalid_ticket';
            $error_response = \App\Http\Controllers\ErrorCodeController::error_response('external_auth_payment',$error_code);
            log::warning("external_auth_payment error by kiplebox. try to pay 0 amount");
            return $error_response;
        }

        $subticket = $data['data']['ticket'];
        $entry_time = $data['data']['entry'];
        $exit_gp = $rate = \App\Http\Controllers\ParkingRateController::get_grace_period($entry_time,'exit');
        $payment_id = \App\TicketPayment::update_kiplebox_payment($subticket,$parking_fee,$payment_date,$auth_code,$payment_method,$amount);

        //generate new subticket
        $entry_time = $data["data"]['entry'];
        self::generate_new_subticket($ticket_id,$entry_time,$payment_date);

        $data = array(
            "ticket" => $subticket,
            "receipt" => (string)$payment_id[0]->id,//ticket_payment table ID
            "value" => $parking_fee/100,
            "gst" => 0,
            "pdate" => date("YmdHi"),
            "grace" => $exit_gp,
        );
        $success_response = \App\Http\Controllers\ErrorCodeController::success_response($data);
        return $success_response;
    }
    public static function generate_new_subticket($ticket_id,$entry_time,$last_payment_date){
        //create payment subticket
        $grace_period  = \App\Http\Controllers\ParkingRateController::get_grace_period($entry_time,'exit');
        $last_subticket = \App\TicketPayment::get_latest_subticket($ticket_id);
        $last_subticket = $last_subticket[0]->subticket;
        $last_subticket = substr($last_subticket,0,3);

        $new_subticket = $last_subticket + 1;
        $subticket = $new_subticket.$ticket_id;
        $start_time = $last_payment_date;

        while(true){
            DB::beginTransaction();
            $payment_id = \App\TicketPayment::insert_kiplebox_payment_ticket($ticket_id,$subticket,$entry_time,$start_time,$grace_period);
            if($payment_id == false){
                DB::rollback();
                log::error("[generate_new_subticket] rolling back ticket payment creation.failed on insert_kiplebox_payment_ticket car in site id = $ticket_id");
                break;
            }
            //update subticket no to car insite table
            $update_eticket_id_to_car_insite = \App\CarInSite::update_eticket_id_to_car_insite($subticket,$ticket_id);
            if($update_eticket_id_to_car_insite == false){
                DB::rollback();
                log::error("[generate_new_subticket] rolling back ticket payment creation.failed on update_eticket_id_to_car_insite car in site id = $ticket_id");
                break;
            }
            DB::commit();
            log::info("[car_entry_by_kiplebox] ticket payment is created $subticket");
            return $subticket;
        }

        return false;

    }
    public static function sent_normal_exit_request($plate_no,$logs_id,$car_in_site){
        //on exit for normal parking, sent car info for maxpark to process exit.
        if ($car_in_site==null) {
            return "TX";
        }
        $sub_ticket_id = $car_in_site->vendor_ticket_id;

        $ticket_id = $car_in_site->id;
        $req = \App\Http\Controllers\API\Vendor\KipleboxController::get_ticket_info($ticket_id);
        Log::info("$plate_no : Response from kiplebox for ticket info = ",$req);
        //ticket already paid and valid to exit
        if($req['success'] == true){
            if($req['data']["value"] == 0){
                //change payment ticket to used
                \App\TicketPayment::ticket_used($ticket_id);
                return "SE".$req['data']['ticket'];
            }
            else{
                //unpaid ticket
                return "TU.$sub_ticket_id";
            }
        }
        else{
            if($req["error"] == 'used_ticket'){
                return "TC.$sub_ticket_id";
            }
        }
    }
}
