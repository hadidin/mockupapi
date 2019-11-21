<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Log;

class TicketPayment extends Model
{
    protected $table = 'psm_ticket_payment';

//    protected $fillable = [
//        'ticket_id','status','method','trx_date','external_ref_id'
//    ];

    public static function check_payment_info($ticket_id){
        $payment_info = self::Where('ticket_id', $ticket_id)
            ->first();
        if($payment_info){
            return $payment_info;
        }
        else{
            return false;
        }
    }

    public static function maxpark_insert_payment($maxpark_exit_info,$ticket_id){
        DB::enableQueryLog();

        $date_format = strtotime($maxpark_exit_info['payment_date']);
        $payment_date = date('Y-m-d H:i:s',$date_format);
        $amount = $maxpark_exit_info['amount'];
        $sst = $maxpark_exit_info['sst'];
        $pay_location = $maxpark_exit_info['pay_location'];
        $external_ref_id = $maxpark_exit_info['receipt'];

        //if pay not using kiplepay,insert payment information to psm_ticket_payment
        $check_payment_info = \App\TicketPayment::check_payment_info($ticket_id);
        if($check_payment_info){
            /*
             * 0=not process,
             * 1=sent to pay,
             * 2= pay succceeded,
             * 3=pay failed,
             * 4=req auto void(AV),
             * 5=response AV success,
             * 6=response AV failed
             */
            $payment_status = $check_payment_info['status'];
            //only ticket status that is not equal to success payment will replace with maxpark result return
            if($payment_status != 2){
                self::replace_to_psm_ticket_payment($ticket_id,$amount,$sst,$pay_location,$external_ref_id,$payment_date);
            }

        }
        else{
            try{
                self::replace_to_psm_ticket_payment($ticket_id,$amount,$sst,$pay_location,$external_ref_id,$payment_date);
            }
            catch (\Exception $e){
                log::warning("failed to update record to db as there are no record found $e");
            }


        }
    }

    protected static function replace_to_psm_ticket_payment($ticket_id,$amount,$sst,$pay_location,$external_ref_id,$payment_date){

        DB::enableQueryLog();
        DB::statement("REPLACE INTO psm_ticket_payment SET ticket_id=$ticket_id,status=2,method='maxpark',amount=$amount,sst='$sst',
                  pay_location='$pay_location',external_ref_id='$external_ref_id',trx_date='$payment_date'");
//        dd(DB::getQueryLog());
    }

    public static function start_payment_trx($amount,$ticket_id){
        DB::enableQueryLog();
        $payment_date = date('Y-m-d H:i:s');
        $amount = $amount;
        $query = "replace into psm_ticket_payment set ticket_id='$ticket_id', status=0, method='kiplepay', 
            amount=$amount, trx_date= '$payment_date', external_ref_id='', updated_at=NOW()";
        DB::statement($query);
        $id = DB::getPdo()->lastInsertId();
        return $id;

    }

    public static function manual_payment_trx($amount,$ticket_id,$external_ref_id){
        DB::enableQueryLog();
        $payment_date = date('Y-m-d H:i:s');
        $amount = $amount*100;
        $external_ref_id = (int)$external_ref_id;
        $query = "replace into psm_ticket_payment set ticket_id='$ticket_id', status=2, method='kiplepay', 
            amount=$amount, trx_date= '$payment_date', external_ref_id='$external_ref_id', updated_at=NOW()";
        DB::statement($query);
        $id = DB::getPdo()->lastInsertId();
        return $id;

    }

    public static function getSummaryAmount($startTime,$endTime) {
        // TODO, create index in created_at or ticket_id,
        // status_id , 2 : pay succeeded, 4, voiding, 6,void failed
        $sql = "SELECT SUM(amount) AS total, SUM(IF(method='kiplepay',amount,0)) as kiple
            FROM psm_ticket_payment 
            WHERE created_at>='{$startTime}' AND created_at <'$endTime' AND status IN (2,4,6)";
        $result = DB::select($sql);
        return $result[0];
    }
    
    protected static function getDtFormatString($dt) {
        $dtArray = [
            'day'   => '%Y-%m-%d',
            'week'   => '%Y%-%V',
            'month'   => '%Y-%m',
            'year'   => '%Y',
            'dayhour'   => '%H',
            'weekday'   => '%w'
        ];
        if (!isset($dtArray[$dt])){
            $dt='day';
        }
        return $dtArray[$dt];
    } 

    public static function getAmount($startTime,$endTime,$dt,$cutOffTime) {
        // status_id , 2 : pay succeeded, 4: voiding, 6:void failed        
        $dtFormat =  TicketPayment::getDtFormatString($dt);
        $sql = "SELECT DATE_FORMAT(a.calc_date,'{$dtFormat}') AS dt, SUM(a.amount) AS total, SUM(IF(a.method='kiplepay',a.amount,0)) AS ewallet 
            FROM (
                SELECT ADDTIME(created_at,'-{$cutOffTime}') AS calc_date,method,amount
                FROM psm_ticket_payment 
                WHERE created_at >='{$startTime}' AND created_at <'{$endTime}' AND STATUS IN (2,4,6)
            ) a
            GROUP BY dt";
        if ($dt=='dayhour') {
            $sql = "SELECT DATE_FORMAT(created_at,'%H') AS dt, SUM(amount) AS total, SUM(IF(method='kiplepay',amount,0)) as ewallet
                FROM psm_ticket_payment 
                WHERE created_at>='{$startTime}' AND created_at <'$endTime' AND status IN (2,4,6)
                GROUP BY dt";
        }   
        $result = DB::select($sql);
        return $result;
    }

    public static function insert_kiplebox_payment_ticket($ticket_id,$subticket,$entry_time,$start_time,$grace_period){
        try {
            $result = DB::table('psm_ticket_payment')->insert(
                [
                    'ticket_id' => $ticket_id,
                    'subticket' => $subticket,
                    'status' => 0,
                    'start_time' => $start_time,
                    'entry_time' => $entry_time,
                    'grace_period' => $grace_period,
                    'created_at' => date("Y-m-d H:i:s")
                ]
            );
            if ($result!=1) {
                return false;
            }
            return DB::getPdo()->lastInsertId();
        } catch (\Exception $e) {
            Log::error("[insert_kiplebox_payment_ticket] get exception:$e");
        }
        return false;
    }

    public static function get_ticket_details($subticket_id){
        $payment_info = self::Where('subticket', $subticket_id)->get();
        if($payment_info){
            return $payment_info;
        }
        else{
            return false;
        }
    }
    public static function get_previous_amount_paid($ticket_id){
        $sql = "SELECT sum(amount) as amount from psm_ticket_payment where ticket_id=$ticket_id and amount > 0";
        $result = DB::select($sql);
        return $result;
    }
    public static function update_kiplebox_payment($subticket,$parking_fee,$payment_date,$auth_code,$payment_method,$amount){
        DB::enableQueryLog();
        $date = date("Y-m-d H:i:s");
        DB::table('psm_ticket_payment')
            ->where('subticket', $subticket)
            ->update(
                [
                    'status' => '2',
                    'amount' => $amount,
                    'parking_fee' => $parking_fee,
                    'trx_date' => $payment_date,
                    'updated_at' => $date,
                    'method' => $payment_method,
                    'external_ref_id' => $auth_code
                ]
            );
        //get id of ticket_payment to return and save a a receipt number
        $sql = "SELECT id from psm_ticket_payment where subticket=$subticket";
        $result = DB::select($sql);
        return $result;
    }
    public static function get_latest_subticket($ticket_id){
        $sql = "SELECT subticket from psm_ticket_payment where ticket_id=$ticket_id order by id desc limit 1";
        $result = DB::select($sql);
        return $result;
    }
    public static function ticket_used($car_in_site_id){
        $date = date("Y-m-d H:i:s");
        DB::table('psm_ticket_payment')
            ->where('ticket_id', $car_in_site_id)
            ->where('status', 0)
            ->update(
                [
                    'is_used' => 1,
                    'used_time' => $date,
                    'updated_at' => $date,
                ]
            );
    }
    public static function check_used_auth($auth_code){
        $auth_code = (int)$auth_code;
        $subticket = self::Where('external_ref_id', $auth_code)->select('subticket')->get();
        if($subticket){
            return $subticket;
        }
        else{
            return false;
        }
    }
    public static function get_all_subticket_list($car_in_site_id){
        $date = date("Y-m-d H:i:s");
        $data = DB::table('psm_ticket_payment')
            ->where('ticket_id', $car_in_site_id)
            ->select('status','method','parking_fee','amount','trx_date','external_ref_id','subticket','start_time','entry_time','grace_period')
            ->orderBy('id','desc')
            ->get();
        return $data;
    }
}
