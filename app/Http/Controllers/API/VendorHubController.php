<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Matrix\Exception;

class VendorHubController extends Controller
{

    public static function push_normal_ticket($plate_no,$image_path,$camera_id,$binded_user_info,$ext_cam_id,$lane_in_out_flag,$log_id){
        $ini_array = parse_ini_file("../cron/config.ini",true);
        $vendor_id=$ini_array['common']['VENDOR_ID'];
        //entry lane
        if($lane_in_out_flag==0){
            if($vendor_id=='V0001'){
                    $eticket = \App\Http\Controllers\API\Vendor\MaxparkController::push_vehicle_info_php($plate_no,$image_path,$camera_id,$binded_user_info,$ext_cam_id,$lane_in_out_flag);
                    //update vendor ticket id
                    $entry_logs=\App\EntryLog::update_vendor_ticket_id_to_entry_logs($log_id,$eticket);

//                    dd($eticket);
                    $response = self::response_ls_dict($eticket);
                    #dd($response);
                    return $response;
            }

            //vendor V0004 which is for snb will treat using swoole socket..check check_result definition
            else{
                /*
                   * it will be use by snb case.
                   * if snb accept the entry request and the final check result will change to LS011 by swole thread
                */
                return array('status'=>false,'check_result'=>'LS025');
            }
        }
        else{
            //not using entry lane..just reject
            return array('status'=>false);
        }
    }

    public static function response_ls_dict($eticket){
        //ls response we got from ld command request
        $ls_response = substr($eticket,0,2);
        $vendor_ticket_id = substr($eticket,2);

        /*
         5.2.2c.6 TKxxxxxxxx = Non Season vehicle. xxxxxxxx will be the Ticket Number
         */
        // 0=_NONE_,1=success,2=duplicateNo,3=sysError,
        // 10=_NONE_,11=notPaid,12=tickedUsed,13=exceedGP,14=success,15=unknownErrorVendor,16=no vehicle found,17=ticket not found
        if($ls_response == 'TK'){
            return array('status'=>true,'parking_type'=>0,'vendor_ticket_id'=>$vendor_ticket_id,
                'ls_response'=>$ls_response,'visitor_check_result'=>1,'check_result'=>'LS010','message'=>'vendor success');
        }
        if($ls_response == 'TU'){
            return array('status'=>false,'parking_type'=>9,'vendor_ticket_id'=>$vendor_ticket_id,
                'ls_response'=>$ls_response,'visitor_check_result'=>11,'check_result'=>'LS015','message'=>'vendor not paid');
        }
        if($ls_response == 'TG'){
            return array('status'=>false,'parking_type'=>9,'vendor_ticket_id'=>$vendor_ticket_id,
                'ls_response'=>$ls_response,'visitor_check_result'=>13,'check_result'=>'LS016','message'=>'vendor exceeded gp');
        }
        if($ls_response == 'TC'){
            return array('status'=>false,'parking_type'=>9,'vendor_ticket_id'=>$vendor_ticket_id,
                'ls_response'=>$ls_response,'visitor_check_result'=>12,'check_result'=>'LS017','message'=>'vendor ticket used');
        }
        if($ls_response == 'TX'){
            return array('status'=>false,'parking_type'=>9,'vendor_ticket_id'=>$vendor_ticket_id,
                'ls_response'=>$ls_response,'visitor_check_result'=>3,'check_result'=>'LS018','message'=>'vendor other error');
        }
        if($ls_response == 'TD'){
            return array('status'=>false,'parking_type'=>9,'vendor_ticket_id'=>$vendor_ticket_id,
                'ls_response'=>$ls_response,'visitor_check_result'=>2,'check_result'=>'LS019','message'=>'vendor duplicate no');
        }
        if($ls_response == 'SE'){
            return array('status'=>true,'parking_type'=>0,'vendor_ticket_id'=>$vendor_ticket_id,
                'ls_response'=>$ls_response,'visitor_check_result'=>14,'check_result'=>'LS011','message'=>'vendor success');
        }
        else{
            return array('status'=>false,'parking_type'=>9,'vendor_ticket_id'=>$vendor_ticket_id,
                'ls_response'=>$ls_response,'visitor_check_result'=>15,'check_result'=>'LS024','message'=>'vendor error');
        }
    }

    public static function response_mp_dict($eticket){
        //ls response we got from ld command request
        $ls_response = substr($eticket,0,2);
        $vendor_ticket_id = substr($eticket,2);
//        dd($eticket);

        /*
            5.4.2c STATUS (10 Chars) = Status of the License Plate. This will have the following meaning:
            5.4.2c.1 TZ00000000 = Command Rejected – No vehicle detected. Barrier is NOT raised
            5.4.2c.2 TKxxxxxxxx = Paid and within Grace Period. xxxxxxxx will be the Ticket Number
            5.4.2c.3 TFxxxxxxxx = Ticket not Found.
            5.4.2c.4 TUxxxxxxxx = Ticket not Paid.
            5.4.2c.5 TGxxxxxxxx = Exceeded Grace Period..
            5.4.2c.6 TCxxxxxxxx = Ticket already used.
            5.4.2c.7 TXxxxxxxxx = Other Error.

         */
        if($ls_response == 'TK'){
            return array('status'=>true,'parking_type'=>0,'vendor_ticket_id'=>$vendor_ticket_id,
                'ls_response'=>$ls_response,'visitor_check_result'=>14,'check_result'=>'LS011','message'=>'vendor success');
        }
        if($ls_response == 'TU'){
            return array('status'=>true,'parking_type'=>9,'vendor_ticket_id'=>$vendor_ticket_id,
                'ls_response'=>$ls_response,'visitor_check_result'=>11,'check_result'=>'LS015','message'=>'vendor not paid');
        }
        if($ls_response == 'TG'){
            return array('status'=>true,'parking_type'=>9,'vendor_ticket_id'=>$vendor_ticket_id,
                'ls_response'=>$ls_response,'visitor_check_result'=>13,'check_result'=>'LS016','message'=>'vendor exceeded gp');
        }
        if($ls_response == 'TC'){
            return array('status'=>true,'parking_type'=>9,'vendor_ticket_id'=>$vendor_ticket_id,
                'ls_response'=>$ls_response,'visitor_check_result'=>12,'check_result'=>'LS017','message'=>'vendor ticket used');
        }
        if($ls_response == 'TX'){
            return array('status'=>true,'parking_type'=>9,'vendor_ticket_id'=>$vendor_ticket_id,
                'ls_response'=>$ls_response,'visitor_check_result'=>3,'check_result'=>'LS018','message'=>'vendor other error');
        }
        if($ls_response == 'TZ'){
            return array('status'=>true,'parking_type'=>9,'vendor_ticket_id'=>$vendor_ticket_id,
                'ls_response'=>$ls_response,'visitor_check_result'=>3,'check_result'=>'LS022','message'=>'Command Rejected – No vehicle detected. Barrier is NOT raised');
        }
        if($ls_response == 'TF'){
            return array('status'=>true,'parking_type'=>9,'vendor_ticket_id'=>$vendor_ticket_id,
                'ls_response'=>$ls_response,'visitor_check_result'=>3,'check_result'=>'LS023','message'=>'Ticket not Found');
        }
        else{
            return array('status'=>false,'vendor_ticket_id'=>"",'mp_response'=>"XX",'check_result'=>'LS024');
        }
    }

    public static function in_autodeduct_white_list($user_id) {
        $white_list = \App\AutodeductWhitelist::checkUserWhitelist($user_id);
        if($white_list->isNotEmpty()){
            return true;
        }
        return false;
    }
    public static function lpr_visitor_whitelist($plate_no) {
        $white_list = array(
            'PHR8057' => 1,
            'VCH2904' => 1,
//            'BYS623' => 1,
            'NDH7882' => 1,
            'BED7188' =>1,
            'WPX3115' => 1
        );
        if (isset($white_list[$plate_no]) && $white_list[$plate_no]==1) {
            return true;
        }
        //for enable whitelist plate number should return false here..
        return true;
    }

    public static function request_exit($plate_no,$kiple_user_id,$ext_cam_id,$logs_id,$image_path){

        $ini_array = parse_ini_file("../cron/config.ini",true);
        $vendor_id = $ini_array['common']['VENDOR_ID'];
//        $site_id = $ini_array['common']['SITE_ID'];
//        $autopay_url = $ini_array['common']['AUTOPAY_URL'];
//        $autopay_secret = $ini_array['common']['AUTOPAY_SECRET'];
        $lane_in_out_flag = 1;

        if($vendor_id=='V0001'){
            //check car_in_site. it is use to get vendor_ticket_id too
            $entry_record = \App\CarInSite::check_car_in_site_by_plate_number($plate_no);
            if(!$entry_record){
                Log::warning("$plate_no : Not exist in car insite..payment process will not going through");
                //will sent exit request to maxpark also
                $tcp_req = \App\Http\Controllers\API\Vendor\MaxparkController::sent_normal_exit_request($plate_no,empty($kiple_user_id)?false:true,$ext_cam_id,$logs_id,$image_path,$entry_record->id,$lane_in_out_flag);
                //update vendor ticket id
                $entry_logs=\App\EntryLog::update_vendor_ticket_id_to_entry_logs($logs_id,$tcp_req);
                $ls_response = self::response_ls_dict($tcp_req);
                return $ls_response;
            }

            if (VendorHubController::in_autodeduct_white_list($kiple_user_id)==false) {
                Log::warning("$plate_no : user [$kiple_user_id] is not in auto deduct white list, payment process will not going through");
                //will sent exit request to maxpark also
                $tcp_req = \App\Http\Controllers\API\Vendor\MaxparkController::sent_normal_exit_request($plate_no,empty($kiple_user_id)?false:true,$ext_cam_id,$logs_id,$image_path,$entry_record->id,$lane_in_out_flag);
                //update vendor ticket id
                $entry_logs=\App\EntryLog::update_vendor_ticket_id_to_entry_logs($logs_id,$tcp_req);
                $ls_response = self::response_ls_dict($tcp_req);
                return $ls_response;
            }

            //check payment status from comm module port 5000
            $ticket_info = \App\Http\Controllers\API\Vendor\MaxparkController::get_ticket_info($entry_record['vendor_ticket_id']);
            log::info("payment start = get ticket info",$ticket_info);
            //payment-->means that ticket still not paid
            if($ticket_info['success'] == true && $ticket_info['data']['value'] > 0){
                //start auto deduct here
                #payment status id starting(0)
                $payment_trx_id = \App\TicketPayment::start_payment_trx($ticket_info['data']['value'],$entry_record['id']);
                $payment_log_trx_id = \App\PaymentLogs::insert_payment_trx_log($entry_record['id'],0,$payment_trx_id,$ticket_info['data']['value'],0,'');
                $parking_type = 0;//normal
                $kp_payment = self::kiple_payment($ticket_info['data']['value'],$entry_record['id'],$payment_trx_id,$parking_type,$entry_record['user_id']);

                /*
                 * if payment to kiplepay is success then we will call maxpark auth payment
                 */
                if($kp_payment){
                    $kp_ticket_id = $entry_record['id'];
                    //update payment trx for success to kiplepay(2)
                    \App\PaymentLogs::insert_payment_trx_log($kp_ticket_id,2,$kp_payment['data']['transaction_id'],$ticket_info['data']['value'],0,'');
                    //authorize payment with vendor

                    //authorization code need to sent to maxpark using 12digit number
                    $kiple_pay_trx_id = $kp_payment['data']['transaction_id'];
                    $auth_code = sprintf('%012d', $kiple_pay_trx_id);
                    #usleep(5000);
                    $eticket = $ticket_info['data']['ticket'];
                    $amount = $ticket_info['data']['value'];
                    $mp_auth_payment = \App\Http\Controllers\API\Vendor\MaxparkController::authorize_payment($eticket,$amount,$auth_code);

                    //autovoid process, because mp auth payment is failed.
                    if($mp_auth_payment == false){
                        //update payment trx for autovoid start process to kiplepay(4)
                        \App\PaymentLogs::insert_payment_trx_log($entry_record['id'],4,$payment_trx_id,$ticket_info['data']['value'],0,'');
                        //trigger auto void to kiple cloud
                        $auto_void = self::kiple_void_payment($ticket_info['data']['value'],$entry_record['id'],$parking_type,$entry_record['user_id']);
                        if($auto_void){
                            //update payment trx for autovoid success to kiplepay(5)
                            \App\PaymentLogs::insert_payment_trx_log($entry_record['id'],5,$auto_void['data']['refund_transaction_id'],$ticket_info['data']['value'],0,'');
                        }
                        else{
                            //update payment trx for autovoid failed to kiplepay(6)
                            \App\PaymentLogs::insert_payment_trx_log($entry_record['id'],6,$payment_trx_id,$ticket_info['data']['value'],0,'');
                        }
                        log::warning("autovoid finish",$ticket_info);
                    }
                    //need to update vendor ticket to payment logs here
                }
                else{
                    //update payment trx for failed to kiplepay(3)
                    \App\PaymentLogs::insert_payment_trx_log($entry_record['id'],3,$kp_payment['data']['transaction_id'],$ticket_info['data']['value'],0,'');
                }

            }
            //calling vendor to sent plate no
            $tcp_req = \App\Http\Controllers\API\Vendor\MaxparkController::sent_normal_exit_request($plate_no,empty($kiple_user_id)?false:true,$ext_cam_id,$logs_id,$image_path,$entry_record->id,$lane_in_out_flag);
            //update vendor ticket id
            $entry_logs=\App\EntryLog::update_vendor_ticket_id_to_entry_logs($logs_id,$tcp_req);
            $ls_response = self::response_ls_dict($tcp_req);

//            dd($ls_response);
            return $ls_response;
        }

        //other vendor logic will go through here
        #else if($vendor_id=='V000X'){}//example for amano
        //vendor V0004 which is for snb will using swoole socket using different thread..check check_result definition
        else{
            #return null;
            //default value for normal parking should always be LS011,to show goodbye
            #return array('status'=>false,'vendor_ticket_id'=>"",'ls_response'=>"",'check_result'=>'LS011');
            return false;
        }
    }

    /*
     * manual exit trigger by portal
     * it comes from portal lane dashboard.it will go through payment process is any.
     */
    public static function manual_request_exit($plate_no,$kiple_user_id,$ext_cam_id,$logs_id,$image_path){

        $ini_array = parse_ini_file("../cron/config.ini",true);
        $vendor_id = $ini_array['common']['VENDOR_ID'];
        $lane_in_out_flag = 1;

        if($vendor_id=='V0001'){
            //check car_in_site. it is use to get vendor_ticket_id too
            $entry_record = \App\CarInSite::check_car_in_site_by_plate_number($plate_no);
            if(!$entry_record){
                Log::warning("$plate_no : Not exist in car insite..payment process will not going through");
                //will sent exit request to maxpark also

                $tcp_req = \App\Http\Controllers\API\Vendor\MaxparkController::sent_manual_exit($plate_no,"",empty($kiple_user_id)?false:true,$ext_cam_id,$lane_in_out_flag,$logs_id,$entry_record);
                //dd($tcp_req);
                //update vendor ticket id
                $entry_logs = \App\EntryLog::update_vendor_ticket_id_to_entry_logs($logs_id,$tcp_req);
                $mp_response = self::response_mp_dict($tcp_req);
                return $mp_response;
            }

            if (VendorHubController::in_autodeduct_white_list($kiple_user_id)==false) {
                Log::warning("$plate_no : user [$kiple_user_id] is not in auto deduct white list, payment process will not going through");
                //will sent exit request to maxpark also
                $tcp_req = \App\Http\Controllers\API\Vendor\MaxparkController::sent_manual_exit($plate_no,"",empty($kiple_user_id)?false:true,$ext_cam_id,$lane_in_out_flag,$logs_id,$entry_record);
                //update vendor ticket id
                $entry_logs=\App\EntryLog::update_vendor_ticket_id_to_entry_logs($logs_id,$tcp_req);
                $mp_response = self::response_mp_dict($tcp_req);
                return $mp_response;
            }

            //check payment status from comm module port 5000
            $ticket_info = \App\Http\Controllers\API\Vendor\MaxparkController::get_ticket_info($entry_record['vendor_ticket_id']);
            log::info("payment start = get ticket info",$ticket_info);
            //payment-->means that ticket still not paid
            if($ticket_info['success'] == true && $ticket_info['data']['value'] > 0){
                //start auto deduct here
                #payment status id starting(0)
                $payment_trx_id = \App\TicketPayment::start_payment_trx($ticket_info['data']['value'],$entry_record['id']);
                $payment_log_trx_id = \App\PaymentLogs::insert_payment_trx_log($entry_record['id'],0,$payment_trx_id,$ticket_info['data']['value'],0,'');
                $parking_type = 0;//normal
                $kp_payment = self::kiple_payment($ticket_info['data']['value'],$entry_record['id'],$payment_trx_id,$parking_type,$entry_record['user_id']);

                /*
                 * if payment to kiplepay is success then we will call maxpark auth payment
                 */
                if($kp_payment){
                    $kp_ticket_id = $entry_record['id'];
                    //update payment trx for success to kiplepay(2)
                    \App\PaymentLogs::insert_payment_trx_log($kp_ticket_id,2,$kp_payment['data']['transaction_id'],$ticket_info['data']['value'],0,'');
                    //authorize payment with vendor

                    //authorization code need to sent to maxpark using 12digit number
                    $kiple_pay_trx_id = $kp_payment['data']['transaction_id'];
                    $auth_code = sprintf('%012d', $kiple_pay_trx_id);
                    #usleep(5000);
                    $eticket = $ticket_info['data']['ticket'];
                    $amount = $ticket_info['data']['value'];
                    $mp_auth_payment = \App\Http\Controllers\API\Vendor\MaxparkController::authorize_payment($eticket,$amount,$auth_code);

                    //autovoid process, because mp auth payment is failed.
                    if($mp_auth_payment == false){
                        //update payment trx for autovoid start process to kiplepay(4)
                        \App\PaymentLogs::insert_payment_trx_log($entry_record['id'],4,$payment_trx_id,$ticket_info['data']['value'],0,'');
                        //trigger auto void to kiple cloud
                        $auto_void = self::kiple_void_payment($ticket_info['data']['value'],$entry_record['id'],$parking_type,$entry_record['user_id']);
                        if($auto_void){
                            //update payment trx for autovoid success to kiplepay(5)
                            \App\PaymentLogs::insert_payment_trx_log($entry_record['id'],5,$auto_void['data']['refund_transaction_id'],$ticket_info['data']['value'],0,'');
                        }
                        else{
                            //update payment trx for autovoid failed to kiplepay(6)
                            \App\PaymentLogs::insert_payment_trx_log($entry_record['id'],6,$payment_trx_id,$ticket_info['data']['value'],0,'');
                        }
                        log::warning("autovoid finish",$ticket_info);
                    }
                    //need to update vendor ticket to payment logs here
                }
                else{
                    //update payment trx for failed to kiplepay(3)
                    \App\PaymentLogs::insert_payment_trx_log($entry_record['id'],3,$kp_payment['data']['transaction_id'],$ticket_info['data']['value'],0,'');
                }

            }
            //calling vendor to sent plate no
            $tcp_req = \App\Http\Controllers\API\Vendor\MaxparkController::sent_manual_exit($plate_no,'',empty($kiple_user_id)?false:true,$ext_cam_id,$lane_in_out_flag,$logs_id,$entry_record);
            //update vendor ticket id
            $entry_logs=\App\EntryLog::update_vendor_ticket_id_to_entry_logs($logs_id,$tcp_req);
            $mp_response = self::response_mp_dict($tcp_req);

//            dd($ls_response);
            return $mp_response;
        }

        //other vendor logic will go through here
        #else if($vendor_id=='V000X'){}//example for amano
        //vendor V0004 which is for snb will using swoole socket using different thread..check check_result definition
        else{
            #return null;
            //default value for normal parking should always be LS011,to show goodbye
            #return array('status'=>false,'vendor_ticket_id'=>"",'ls_response'=>"",'check_result'=>'LS011');
            return false;
        }
    }

    public static function kiple_payment($amount,$ticket_id,$payment_trx_id,$parking_type,$user_id){
        $ini_array = parse_ini_file("../cron/config.ini",true);
        $site_id = $ini_array['common']['SITE_ID'];
        $autopay_url = $ini_array['common']['AUTOPAY_URL'];
        $autopay_secret = $ini_array['common']['AUTOPAY_SECRET'];

      /*
        //for bypass payment to kiplepay(testing purpose)
        $array_data = '{"success":true,"code":"success","message":"Success","data":{"merchant_reference":"SIG002503543","transaction_id":8081385,"date":"2019-05-13T15:18:19+08:00","amount":"5","wallet_id":24354132,"account_balance":30.7,"enquiry_url":"https://staging.webcash.com.my/enquiry.php?ord_mercID=80006514&ord_mercref=SIG002503543&ord_totalamt=5","status":1}}';
        $array_data = json_decode($array_data,true);
        return $array_data;
        die();
      */

        #payment status id starting(1)
//        $client = new \GuzzleHttp\Client();
        $client = new \GuzzleHttp\Client([
            'verify' => false
        ]);
        $kp_ticket_id = $site_id.$parking_type.$ticket_id;
        #$kp_ticket_id = $site_id.$parking_type.$entry_record['id']."@".$payment_log_trx_id;
        $xodata = array(
            'ticket_id' => $ticket_id,
            'site_id' => $site_id,
            'user_id' => $user_id,
            'amount' => $amount/100,
            'kp_ticket_id' => $kp_ticket_id
        );
        Log::info("Start auto deduct",$xodata);
        $odata = json_encode($xodata);
        $body = \GuzzleHttp\Psr7\stream_for($odata);
        $url = $autopay_url."/api/wallet/autodeduct";
        try{
            //update payment trx for sending to kiplepay(1)
            \App\PaymentLogs::insert_payment_trx_log($ticket_id,1,$payment_trx_id,$amount,0,'');
            $res = $client->request('POST', $url, ['body' => $body, 'headers'  => [
                'Content-Type' => 'application/json','X-Application-Key'=> $autopay_secret],'connect_timeout' => 30,'timeout' => 30]);
            $data = $res->getBody();
            $array_data = json_decode($data,true);
            Log::info("kiplepayment response",$array_data);
        }//try
        catch (\Exception $f){
            log::critical($f);
            $array_data = array("success" => false);
        }//catch
        if($array_data['success'] == true){
            return $array_data;
        }
        else{
            log::warning('payment error ',$array_data);
            return false;
        }

    }

    public static function kiple_void_payment($amount,$ticket_id,$parking_type,$user_id){
        $ini_array = parse_ini_file("../cron/config.ini",true);
        $site_id = $ini_array['common']['SITE_ID'];
        $autopay_url = $ini_array['common']['AUTOPAY_URL'];
        $autopay_secret = $ini_array['common']['AUTOPAY_SECRET'];

        $client = new \GuzzleHttp\Client();
        $client = new \GuzzleHttp\Client([
            'verify' => false
        ]);
        $kp_ticket_id = $site_id.$parking_type.$ticket_id;
        $xodata = array(
            'ticket_id' => $ticket_id,
            'site_id' => $site_id,
            'user_id' => $user_id,
            'amount' => $amount/100,
            'kp_ticket_id' => $kp_ticket_id
        );
        Log::info("Start auto void",$xodata);
        $odata = json_encode($xodata);
        $body = \GuzzleHttp\Psr7\stream_for($odata);
        $url = $autopay_url."/api/wallet/autovoid";
        try{
            $res = $client->request('POST', $url, ['body' => $body, 'headers'  => [
                'Content-Type' => 'application/json','X-Application-Key'=> $autopay_secret],'connect_timeout' => 30,'timeout' => 30]);
            $data = $res->getBody();
            $array_data = json_decode($data,true);
            Log::info("kiplevoid response",$array_data);
        }//try
        catch (\Exception $f){
            log::critical($f);
            $array_data = array("success" => false);
        }//catch
        if($array_data['success'] == true){
            return $array_data;
        }
        else{
            log::warning('void error ',$array_data);
            return false;
        }

    }
    protected function snb_payment(Request $request)
    {
        $amount = $request->amount;
        $ticket_id = $request->ticket_id;
        $user_id = $request->user_id;
        //start payment
        $payment_trx_id = \App\TicketPayment::start_payment_trx($amount, $ticket_id);
        \App\PaymentLogs::insert_payment_trx_log($ticket_id, 0, $payment_trx_id, $amount, 0, '');
        $parking_type = 0;//normal

        $kp_payment = \App\Http\Controllers\API\VendorHubController::kiple_payment($amount, $ticket_id, $payment_trx_id, $parking_type, $user_id);

        if ($kp_payment) {
            //update payment trx for success to kiplepay(2)
            \App\PaymentLogs::insert_payment_trx_log($ticket_id, 2, $kp_payment['data']['transaction_id'], $amount, 0, '');
        }
        else{
            //update payment trx for failed to kiplepay(3)
            \App\PaymentLogs::insert_payment_trx_log($ticket_id, 3, $kp_payment['data']['transaction_id'], $amount, 0, '');
            $kp_payment = false;
        }
        return response()->json($kp_payment, 200);
    }

    protected function snb_void_payment(Request $request)
    {
        $amount = $request->amount;
        $ticket_id = $request->ticket_id;
        $user_id = $request->user_id;
        $payment_trx_id = $request->payment_trx_id;
        //start void
        $auto_void = self::kiple_void_payment($amount,$ticket_id,0,$user_id);
        if($auto_void){
            //update payment trx for autovoid success to kiplepay(5)
            \App\PaymentLogs::insert_payment_trx_log($ticket_id,5,$auto_void['data']['refund_transaction_id'],$amount,0,'');
        }
        else{
            //update payment trx for autovoid failed to kiplepay(6)
            \App\PaymentLogs::insert_payment_trx_log($ticket_id,6,$payment_trx_id,$amount,0,'');
        }
        log::warning("autovoid finish, ticketid = $ticket_id");
        return response()->json($auto_void, 200);
    }

    public function get_ticket_info(Request $request){
        log::info("get_ticket_info request=$request");
        $validator = \Validator::make($request->all(), [
            'ticket_id' => 'required'
        ]);
        if ($validator->fails()) {
            //return error message
            $error_code = "missing_params";
            $data = \App\Http\Controllers\ErrorCodeController::error_response('get_ticket_info',$error_code);
            $error = $validator->errors();
            log::warning("get_ticket_info missing parameter $error");
            return response()->json($data, 200);
        }
        $input = $request->all();
        //ticket id
        $ticket_id = $input['ticket_id'];

        //check vendor id
        $ini_array = parse_ini_file("../cron/config.ini",true);
        $vendor_id = $ini_array['common']['VENDOR_ID'];

        //maxpark ticket info
        if($vendor_id == 'V0001'){
            $maxpark_ticket_id = \App\CarInSite::get_vendor_ticket_id($ticket_id);
            if($maxpark_ticket_id){
                $data = \App\Http\Controllers\API\Vendor\MaxparkController::get_ticket_info($maxpark_ticket_id);
                if($data['success'] == false){
                    if($data['error'] == 'P005' || $data['error'] == 'P004'){
                        $error_code = 'paid_ticket';
                        $data = \App\Http\Controllers\ErrorCodeController::error_response('get_ticket_info',$error_code);
                    }
                    else{
                        $error_code = 'invalid_ticket';
                        $data = \App\Http\Controllers\ErrorCodeController::error_response('get_ticket_info',$error_code);
                    }
                }
            }
            else{
                //return error message
                $error_code = "invalid_ticket";
                $data = \App\Http\Controllers\ErrorCodeController::error_response('get_ticket_info',$error_code);
            }
            log::info("get_ticket_info response",$data);
            return $data;
        }
        //kiplebox ticket info
        if($vendor_id == 'V0009'){
            $data = \App\Http\Controllers\API\Vendor\KipleboxController::get_ticket_info($ticket_id);
            log::info("get_ticket_info response",$data);
            return $data;
        }
        else{
            //return error message for
            $error_code = "error_occured";
            $data = \App\Http\Controllers\ErrorCodeController::error_response('get_ticket_info',$error_code);
            log::warning("get_ticket_info cannot get info.vendor not available ",$data);
            return $data;
        }
    }

    public function external_auth_payment(Request $request){
        log::info("external_auth_payment request=$request");
        $validator = \Validator::make($request->all(), [
            'ticket_id' => 'required',
            'payment_authorization_code' => 'required',
            'payment_amount' => 'required',
        ]);
        if ($validator->fails()) {
            //return error message
            $error_code = "missing_params";
            $data = \App\Http\Controllers\ErrorCodeController::error_response('external_auth_payment',$error_code);
            $error = $validator->errors();
            log::warning("external_auth_payment missing parameter $error");
            return response()->json($data, 200);
        }
        $input = $request->all();
        //ticket id
        $payment_method = $request->has('payment_method') ? $input['payment_method']: 'kiplepay';

        $ticket_id = $input['ticket_id'];
        $payment_authorization_code = $input['payment_authorization_code'];
        $payment_amount = (integer)$input['payment_amount'];


        //check vendor id
        $ini_array = parse_ini_file("../cron/config.ini",true);
        $vendor_id = $ini_array['common']['VENDOR_ID'];
//        $data=array();
        //maxpark auth payment
        if($vendor_id == 'V0001'){
            $maxpark_ticket_id = \App\CarInSite::get_vendor_ticket_id($ticket_id);
            if($maxpark_ticket_id){
                $data = \App\Http\Controllers\API\Vendor\MaxparkController::external_auth_payment($maxpark_ticket_id,$payment_amount,$payment_authorization_code);
                $data['data']['value'] = $data['data']['value']/100;
                $data['data']['gst'] = $data['data']['gst']/100;
                if($data['success'] == false){
                    if($data['error'] == 'P005' || $data['error'] == 'P004'){
                        $error_code = 'paid_ticket';
                    }
                    elseif($data['error'] == 'P013'){
                        $error_code = 'amount_different';
                    }
                    elseif($data['error'] == 'P014'){
                        $error_code = 'used_auth_code';
                    }
                    else{
                        $error_code = 'invalid_request';
                    }
                    //for error return by vendor .maxpark
                    $data = \App\Http\Controllers\ErrorCodeController::error_response('external_auth_payment',$error_code);
                    log::warning("external_auth_payment error by maxpark");
                }
                if($data['success'] == true){
                    //save payment request
                    $payment_trx_id = \App\TicketPayment::manual_payment_trx($payment_amount,$ticket_id,$payment_authorization_code);
                }
            }
            else{
                //return error message
                $error_code = "error_occured";
                $data = \App\Http\Controllers\ErrorCodeController::error_response('external_auth_payment',$error_code);
                log::warning("external_auth_payment error.other vendor is not ready");
            }
        }

        //kiplebox auth payment
        if($vendor_id == 'V0009'){
            $subticket_id = \App\CarInSite::get_vendor_ticket_id($ticket_id);
            $data = \App\Http\Controllers\API\Vendor\KipleboxController::external_auth_payment($ticket_id,$payment_amount,$payment_authorization_code,$payment_method);
            return $data;
        }
        log::info("external_auth_payment response=",$data);
        return $data;

    }


    public static function entry_request_visitor($plate_no,$plate_no2,$image_path,$camera_id,$binded_user_info,$ext_cam_id,$lane_in_out_flag,$log_id){
        $ini_array = parse_ini_file("../cron/config.ini",true);
        $vendor_id=$ini_array['common']['VENDOR_ID'];

        //check whether need to block non kiplepark user
        if (array_key_exists("VISITOR_CONTROL_ALLOW_KIPLE_USER_ONLY",$ini_array['common'])){
            $allow_kiple_user_only=$ini_array['common']['VISITOR_CONTROL_ALLOW_KIPLE_USER_ONLY'];
        }
        else{
            $allow_kiple_user_only = 0;
        }
        if($allow_kiple_user_only == 1 && $binded_user_info[$plate_no]['binded'] == false){
            return array('status'=>false,'parking_type'=>9,'visitor_check_result'=>0,'check_result'=>'LS027','message'=>'non kiplepark visitor');
        };

        if($vendor_id=='V0001'){

//            $eticket = \App\Http\Controllers\API\Vendor\MaxparkController::push_vehicle_info_php($plate_no,$image_path,$camera_id,$binded_user_info,$ext_cam_id,$lane_in_out_flag);
            $eticket = \App\Http\Controllers\API\Vendor\MaxparkController::push_vehicle_info2($plate_no,$plate_no2,$image_path,$camera_id,$binded_user_info,$ext_cam_id,$lane_in_out_flag);
            // TEST ONLY
            // $eticket = 'TK11111111';
            $response = self::response_ls_dict($eticket);
            return $response;
        }
        // S&B
        if ($vendor_id=='V0004') {
            return array('status'=>false,'parking_type'=>9,'visitor_check_result'=>0,'check_result'=>'LS025','message'=>'vendor entry waiting');
        }
        if ($vendor_id=='V0009' && $binded_user_info[$plate_no]['binded']==true){
            $eticket = \App\Http\Controllers\API\Vendor\KipleboxController::entry_request($log_id,$binded_user_info,$plate_no,$plate_no2);
            $response = self::response_ls_dict($eticket);
            return $response;
        }
        Log::warning("[entry_request_visitor] : vendor:$vendor_id not support");
        return array('status'=>false,'parking_type'=>9,'visitor_check_result'=>0,'check_result'=>'LS092','message'=>'vendor exit not processed');
    }


    public static function auto_deduct_at_exit($car_in_site)
    {
        $ticket_info = \App\Http\Controllers\API\Vendor\MaxparkController::get_ticket_info($car_in_site->vendor_ticket_id);
        log::info("payment start = get ticket info",$ticket_info);
        //payment-->means that ticket still not paid
        if($ticket_info['success'] == true && $ticket_info['data']['value'] > 0){
            //start auto deduct here
            #payment status id starting(0)
            $payment_trx_id = \App\TicketPayment::start_payment_trx($ticket_info['data']['value'],$car_in_site->id);
            $payment_log_trx_id = \App\PaymentLogs::insert_payment_trx_log($car_in_site->id,0,$payment_trx_id,$ticket_info['data']['value'],0,'');
            $parking_type = 0;//normal
            $kp_payment = self::kiple_payment($ticket_info['data']['value'],$car_in_site->id,$payment_trx_id,$parking_type,$car_in_site->user_id);

            if($kp_payment){
                $kp_ticket_id = $car_in_site->id;
                //update payment trx for success to kiplepay(2)
                \App\PaymentLogs::insert_payment_trx_log($kp_ticket_id,2,$kp_payment['data']['transaction_id'],$ticket_info['data']['value'],0,'');
                //authorize payment with vendor

                //authorization code need to sent to maxpark using 12digit number
                $kiple_pay_trx_id = $kp_payment['data']['transaction_id'];
                $auth_code = sprintf('%012d', $kiple_pay_trx_id);
                $eticket = $ticket_info['data']['ticket'];
                $amount = $ticket_info['data']['value'];
                $mp_auth_payment = \App\Http\Controllers\API\Vendor\MaxparkController::authorize_payment($eticket,$amount,$auth_code);

                //autovoid process, because mp auth payment is failed.
                if($mp_auth_payment == false){
                    //update payment trx for autovoid start process to kiplepay(4)
                    \App\PaymentLogs::insert_payment_trx_log($kp_ticket_id,4,$payment_trx_id,$ticket_info['data']['value'],0,'');
                    //trigger auto void to kiple cloud
                    $auto_void = self::kiple_void_payment($ticket_info['data']['value'],$kp_ticket_id,$parking_type,$car_in_site->user_id);
                    if($auto_void){
                        //update payment trx for autovoid success to kiplepay(5)
                        \App\PaymentLogs::insert_payment_trx_log($kp_ticket_id,5,$auto_void['data']['refund_transaction_id'],$ticket_info['data']['value'],0,'');
                    } else{
                        //update payment trx for autovoid failed to kiplepay(6)
                        \App\PaymentLogs::insert_payment_trx_log($kp_ticket_id,6,$payment_trx_id,$ticket_info['data']['value'],0,'');
                    }
                    log::warning("autovoid finish",$ticket_info);
                }
                //need to update vendor ticket to payment logs here
            } else{
                //update payment trx for failed to kiplepay(3)
                \App\PaymentLogs::insert_payment_trx_log($car_in_site->id,3,$kp_payment['data']['transaction_id'],$ticket_info['data']['value'],0,'');
            }
        }
    }

    public static function exit_request_visitor($plate_no,$plate_no2,$kiple_user_id,$ext_cam_id,$lane_in_out_flag,$logs_id,$image_path,$car_in_site,$leave_type)
    {
        $ini_array = parse_ini_file("../cron/config.ini",true);
        $vendor_id = $ini_array['common']['VENDOR_ID'];

        // maxpark
        if($vendor_id=='V0001') {
            // try auto deduct first
            if (isset($car_in_site->vendor_ticket_id) &&
                VendorHubController::in_autodeduct_white_list($kiple_user_id)) {
                Log::info("[exit_request_visitor] : try to auto deduct");
                try {
                    self::auto_deduct_at_exit($car_in_site);
                } catch (\Exception $e) {
                    Log::info("[exit_request_visitor] : auto deduct get exception :$e");
                }
            }
            //using lpr
            if($leave_type == 0){
                $response = \App\Http\Controllers\API\Vendor\MaxparkController::sent_normal_exit_request2($plate_no,
                    $plate_no2,empty($kiple_user_id)?false:true,$ext_cam_id,$logs_id,$image_path,$car_in_site,$lane_in_out_flag);
                return self::response_ls_dict($response);
            }
            //manual leave
            if($leave_type == 1){
                $response = \App\Http\Controllers\API\Vendor\MaxparkController::sent_manual_exit_request2($plate_no,
                    $plate_no2,empty($kiple_user_id)?false:true,$ext_cam_id,$logs_id,$image_path,$car_in_site,$lane_in_out_flag);
                return self::response_mp_dict($response);
            }

            // TODO, for test only
            // $response = 'SE11111112';
        }
        // S&B
        if ($vendor_id=='V0004') {
            return array('status'=>false,'parking_type'=>9,'visitor_check_result'=>10,'check_result'=>'LS026','message'=>'vendor exit waiting');
        }

        // kiplebox
        if($vendor_id=='V0009') {
            // try auto deduct first
            if (isset($car_in_site->vendor_ticket_id) &&
                VendorHubController::in_autodeduct_white_list($kiple_user_id)) {
                Log::info("[exit_request_visitor] : try to auto deduct");
                try {
                    self::auto_deduct_at_exit($car_in_site);
                } catch (\Exception $e) {
                    Log::info("[exit_request_visitor] : auto deduct get exception :$e");
                }
            }
            //using lpr
            if($leave_type == 0){
                $response = \App\Http\Controllers\API\Vendor\KipleboxController::sent_normal_exit_request($plate_no,$logs_id,$car_in_site);
                return self::response_ls_dict($response);
            }
            //manual leave
            if($leave_type == 1){
                $response = \App\Http\Controllers\API\Vendor\KipleboxController::sent_manual_exit_request($plate_no,$logs_id,$car_in_site);
                return self::response_mp_dict($response);
            }

            // TODO, for test only
            // $response = 'SE11111112';
        }
        Log::warning("[exit_request_visitor] : vendor:$vendor_id not support");
        return array('status'=>false,'parking_type'=>9,'visitor_check_result'=>10,'check_result'=>'LS092','message'=>'vendor exit not processed');
    }

}
