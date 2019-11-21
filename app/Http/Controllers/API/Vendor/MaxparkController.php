<?php

namespace App\Http\Controllers\API\Vendor;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use App\Externallib;
use Matrix\Exception;

class MaxparkController extends Controller
{
    public static function get_ticket_info($ticket_id){
        //request from maxpark comm module port 5000.it is use before process for auto deduct.if ticket already paid then it will bypass auto deduct
        try{
            require_once app_path().'/Externallib/maxpark/local_agent_maxpark.php';
            #$ticket_id=substr($ticket_id,2,8);
            $req = request_get_eticket($ticket_id);
            Log::info("$ticket_id : Response from maxpark comm module 5000 for get_ticket_info = ",$req);
            return $req;
        }
        catch (\Exception $e){
            //catch any error if failed to get from maxpark
            Log::error("$ticket_id : Response from maxpark comm module 5000 for get_ticket_info = $e");
            return false;
        }
    }

    public static function external_auth_payment($ticket,$amount,$auth_code){
        try{
            require_once app_path().'/Externallib/maxpark/local_agent_maxpark.php';
            $amount_sent_to_maxpark = $amount * 100;
            $req = request_authorise_ticket($ticket,$auth_code,$amount_sent_to_maxpark);
            Log::info("Response from maxpark comm module 5000 for external_auth_payment  $ticket, ",$req);
            return $req;
        }
        catch (\Exception $e){
            //catch any error if failed to get from maxpark
            Log::error("$ticket : Response from maxpark comm module 5000 for external_auth_payment = $e");
            return false;
        }
    }

    public static function authorize_payment($ticket,$amount,$auth_code){
        try{
            require_once app_path().'/Externallib/maxpark/local_agent_maxpark.php';
            $req = request_authorise_ticket($ticket,$auth_code,$amount);
            Log::info("Response from maxpark comm module for payment 5000  $ticket, ",$req);
            if($req['data']['status'] == 'S000'){
                return $req['data'];
            }
            else{
                log::error('Failed to authorize payment. Code ='.$req['data']['status']);
                return false;
            }

        }
        catch (\Exception $e){
            //catch any error if failed to get from maxpark
            Log::error("$ticket : Response from maxpark comm module 5000 = $e");
            return false;
        }
    }

    public static function push_vehicle_info_php($plate_no,$image_path,$camera_id,$binded_user_info,$ext_cam_id,$lane_in_out_flag){
        //push plate no and car picture to maxpark. it use on normal parking car entry
        $ini_array = parse_ini_file("../cron/config.ini",true);
        $vzcenter_path=$ini_array['common']['VZCENTER_PATH'];
        try{
            require_once app_path().'/Externallib/maxpark/local_agent_maxpark.php';
            $req=request_license_plate_detect($ext_cam_id,$plate_no,$image_path,$binded_user_info==null?false:true,$vzcenter_path,$lane_in_out_flag);
            Log::info("$plate_no : Response from maxpark comm module 5005 = ",$req);
            if($req['success'] == false){
                #dd("aaa");
                //failed to get data from maxpark.check log.ftp upload.this will rmapping user to see DENIED
                return "TX11111111";
            }
            return $req['data']['status'];
        }
        catch (\Exception $e){
            //catch any error if failed to get from maxpark
            Log::error("$plate_no : Response from maxpark comm module 5005 = $e");
            return false;
        }
    }

    public static function push_vehicle_info2($plate_no,$plate_no2,$image_path,$camera_id,$binded_user_info,$ext_cam_id,$lane_in_out_flag){
        //push plate no and car picture to maxpark. it use on normal parking car entry
        $ini_array = parse_ini_file("../cron/config.ini",true);
        $vzcenter_path=$ini_array['common']['VZCENTER_PATH'];
        try{
            require_once app_path().'/Externallib/maxpark/local_agent_maxpark.php';
            $req=request_license_plate_detect2($ext_cam_id,$plate_no,$plate_no2,$image_path,$binded_user_info==null?false:true,$vzcenter_path,$lane_in_out_flag);
            Log::info("$plate_no : Response from maxpark comm module 5005 = ",$req);
            if($req['success'] == false){
                #dd("aaa");
                //failed to get data from maxpark.check log.ftp upload.this will rmapping user to see DENIED
                return "TX11111111";
            }
            return $req['data']['status'];
        }
        catch (\Exception $e){
            //catch any error if failed to get from maxpark
            Log::error("$plate_no : Response from maxpark comm module 5005 = $e");
            return false;
        }
    }    

    public static function sent_normal_exit_request($plate_no,$kiple_user,$ext_cam_id,$logs_id,$image_path,$car_in_site_id,$lane_in_out_flag){
        //on exit for normal parking, sent car info for maxpark to process exit.
        $ini_array = parse_ini_file("../cron/config.ini",true);
        $vzcenter_path=$ini_array['common']['VZCENTER_PATH'];
        try{
            require_once app_path().'/Externallib/maxpark/local_agent_maxpark.php';
            $req = request_license_plate_detect($ext_cam_id,$plate_no,$image_path,$kiple_user,$vzcenter_path,$lane_in_out_flag);

            Log::info("$plate_no : Response from maxpark comm module 5005 = ",$req);
            //ld response return by maxpark
            $ld_response = $req['data']['status'];
            $ld_code = substr($ld_response,0,2);

            //ticket already paid and valid to exit.maxpark should open barrier
            if($ld_code == 'SE'){
                $publisher = $req['data']['publisher'];
                try{
                    $receipt = substr($publisher,0,12);
                    $amount = substr($publisher,12,6);
                    $sst = substr($publisher,18,4);
                    $payment_date = substr($publisher,22,12);
                    $pay_location = substr($publisher,34,10);
                    $maxpark_exit_info = array(
                        'receipt' => $receipt,
                        'ld_response' => $ld_response,
                        'amount' => $amount,
                        'sst' => $sst,
                        'payment_date' => $payment_date,
                        'pay_location' => $pay_location,
                        'all_info' => $publisher,
                    );
                    //update payment information to psm_ticket_payment
//                    $ticket_id = $entry_record['id'];
                    $ticket_id = $car_in_site_id;
                    \App\TicketPayment::maxpark_insert_payment($maxpark_exit_info,$ticket_id);
                    Log::info("$plate_no : payment info return by maxpark = ",$maxpark_exit_info);
                }
                catch (\Exception $e){
                    Log::notice("$plate_no : No payment info return by maxpark");
                }

                if($publisher != ""){

                }

                //update car out..because ticket is paid return by vendor
                \App\CarInSite::normal_parking_car_out($logs_id,$plate_no);

                return $ld_response;
            }
            //others than ticket paid response.
            //5.2.2c.7 TUxxxxxxxx = Non Season vehicle. Ticket not Paid Error. (Exit Only)
            //5.2.2c.8 TGxxxxxxxx = Non Season vehicle. Exceeded Grace Period Error. (Exit Only)
            //5.2.2c.9 TCxxxxxxxx = Non Season vehicle. Ticket already used Error. (Exit Only)
            //5.2.2c.10 TXxxxxxxxx = Non Season vehicle. Other Error. (Exit and Entry)
            return $ld_response;
        }
        catch (\Exception $e){
            Log::error("$plate_no : Response from maxpark comm module 5005 = $e");
            return false;
        }
    }

    public static function sent_normal_exit_request2($plate_no,$plate_no2,$kiple_user,$ext_cam_id,$logs_id,$image_path,$car_in_site,$lane_in_out_flag){
        //on exit for normal parking, sent car info for maxpark to process exit.
        $ini_array = parse_ini_file("../cron/config.ini",true);
        $vzcenter_path=$ini_array['common']['VZCENTER_PATH'];
        try{
            require_once app_path().'/Externallib/maxpark/local_agent_maxpark.php';
            $req = request_license_plate_detect2($ext_cam_id,$plate_no,$plate_no2,$image_path,$kiple_user,$vzcenter_path,$lane_in_out_flag);

            Log::info("$plate_no : Response from maxpark comm module LG command = ",$req);
            //ld response return by maxpark
            $ld_response = $req['data']['status'];
            $ld_code = substr($ld_response,0,2);

            //ticket already paid and valid to exit.maxpark should open barrier
            if($ld_code == 'SE'){
                $publisher = $req['data']['publisher'];
                try{
                    $receipt = substr($publisher,0,12);
                    $amount = substr($publisher,12,6);
                    $sst = substr($publisher,18,4);
                    $payment_date = substr($publisher,22,12);
                    $pay_location = substr($publisher,34,10);
                    $maxpark_exit_info = array(
                        'receipt' => $receipt,
                        'ld_response' => $ld_response,
                        'amount' => $amount,
                        'sst' => $sst,
                        'payment_date' => $payment_date,
                        'pay_location' => $pay_location,
                        'all_info' => $publisher,
                    );
                    //update payment information to psm_ticket_payment
                    $ticket_id = $car_in_site->id;
                    \App\TicketPayment::maxpark_insert_payment($maxpark_exit_info,$ticket_id);
                    Log::info("$plate_no : payment info return by maxpark = ",$maxpark_exit_info);
                }
                catch (\Exception $e){
                    Log::notice("$plate_no : No payment info return by maxpark");
                }

                if($publisher != ""){

                }
                return $ld_response;
            }
            //others than ticket paid response.
            //5.2.2c.7 TUxxxxxxxx = Non Season vehicle. Ticket not Paid Error. (Exit Only)
            //5.2.2c.8 TGxxxxxxxx = Non Season vehicle. Exceeded Grace Period Error. (Exit Only)
            //5.2.2c.9 TCxxxxxxxx = Non Season vehicle. Ticket already used Error. (Exit Only)
            //5.2.2c.10 TXxxxxxxxx = Non Season vehicle. Other Error. (Exit and Entry)
            return $ld_response;
        }
        catch (\Exception $e){
            Log::error("$plate_no : Response from maxpark comm module LG command = $e");
            return false;
        }
    }

    public static function sent_manual_exit_request2($plate_no,$plate_no2,$kiple_user,$ext_cam_id,$logs_id,$image_path,$car_in_site,$lane_in_out_flag){
        //on exit for normal parking, sent car info for maxpark to process exit.
        $ini_array = parse_ini_file("../cron/config.ini",true);
        $vzcenter_path=$ini_array['common']['VZCENTER_PATH'];
        try{
            require_once app_path().'/Externallib/maxpark/local_agent_maxpark.php';
//            $req = request_license_plate_detect2($ext_cam_id,$plate_no,$plate_no2,$image_path,$kiple_user,$vzcenter_path,$lane_in_out_flag);
            $req=request_manual_license_plate($ext_cam_id,$plate_no,$image_path,$kiple_user==null?false:true,$vzcenter_path,$lane_in_out_flag);

            Log::info("$plate_no : Response from maxpark comm module MP command = ",$req);
            //ld response return by maxpark
            $mp_response = $req['data']['status'];
            $mp_code = substr($mp_response,0,2);


            //ticket already paid
            if($mp_code == 'TK'){
                $ticket_id = $car_in_site->id;
                self::update_payment_information($plate_no,$req,$ticket_id,$mp_response);
            }
            //exceed grace period
            if($mp_code == 'TG'){
                $ticket_id = $car_in_site->id;
                self::update_payment_information($plate_no,$req,$ticket_id,$mp_response);
            }

            //others than ticket paid response.
            //5.2.2c.7 TUxxxxxxxx = Non Season vehicle. Ticket not Paid Error. (Exit Only)
            //5.2.2c.8 TGxxxxxxxx = Non Season vehicle. Exceeded Grace Period Error. (Exit Only)
            //5.2.2c.9 TCxxxxxxxx = Non Season vehicle. Ticket already used Error. (Exit Only)
            //5.2.2c.10 TXxxxxxxxx = Non Season vehicle. Other Error. (Exit and Entry)
            return $mp_response;
        }
        catch (\Exception $e){
            Log::error("$plate_no : Response from maxpark comm module MP command = $e");
            return false;
        }
    }

    public static function sent_manual_exit($plate_no,$image_path,$binded_user_info,$ext_cam_id,$lane_in_out_flag,$logs_id,$entry_record){
        //push plate no and car picture to maxpark. it use on normal parking car entry
        $ini_array = parse_ini_file("../cron/config.ini",true);
        $vzcenter_path=$ini_array['common']['VZCENTER_PATH'];
        Log::info("$plate_no : Manual Exit =>Request from maxpark comm module 5005 = $ext_cam_id");
        try{
            require_once app_path().'/Externallib/maxpark/local_agent_maxpark.php';
            $req=request_manual_license_plate($ext_cam_id,$plate_no,$image_path,$binded_user_info==null?false:true,$vzcenter_path,$lane_in_out_flag);
            Log::info("$plate_no : Manual Exit =>Response from maxpark manual exit comm 5005 = ",$req);

            //ld response return by maxpark
            $mp_response = $req['data']['status'];
            $mp_code = substr($mp_response,0,2);

            //ticket already paid
            if($mp_code == 'TK'){
                $ticket_id = $entry_record['id'];
                self::update_payment_information($plate_no,$req,$ticket_id,$mp_response);
            }
            //exceed grace period
            if($mp_code == 'TG'){
                $ticket_id = $entry_record['id'];
                self::update_payment_information($plate_no,$req,$ticket_id,$mp_response);
            }

            if($req['success'] == false){
                #dd("aaa");
                //failed to get data from maxpark.check log.ftp upload.this will rmapping user to see DENIED
                return "XX00000000";
            }

            //update car out..if we success to sent to maxpark then need to clear car in site
            \App\CarInSite::normal_parking_car_out($logs_id,$plate_no);

            //update leave type as manual leave
            \App\EntryLog::update_leave_type($logs_id,1);

            return $req['data']['status'];
        }
        catch (\Exception $e){
            //catch any error if failed to get from maxpark
            Log::warning("$plate_no : Manual Exit =>Response from maxpark comm module 5005 = $e");
            return false;
        }
    }

    public static function update_payment_information($plate_no,$req,$ticket_id,$mp_response){
        try{
            $receipt = $req['data']['payment']['receipt'];
            $amount = $req['data']['payment']['value'];
            $sst = $req['data']['payment']['gst'];
            $payment_date = $req['data']['payment']['pdate'];
            $pay_location = $req['data']['payment']['payloc'];
            $maxpark_exit_info = array(
                'receipt' => $receipt,
                'ld_response' => $mp_response,
                'amount' => $amount,
                'sst' => $sst,
                'payment_date' => $payment_date,
                'pay_location' => $pay_location
            );

            //update payment information to psm_ticket_payment
            \App\TicketPayment::maxpark_insert_payment($maxpark_exit_info,$ticket_id);
        }
        catch (\Exception $e){
            log::info("$plate_no = Cannot get payment information from maxpark");
        }
    }
//request_manual_license_plate
}
