<?php

namespace App;
use DB;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class PaymentTransaction extends Model
{
    protected $table = 'psm_ticket_payment';

    protected $fillable = [
        
    ];

    public static function paymentTransactionReport($startdate, $enddate) {
        $sql = "SELECT tp.ticket_id,tp.subticket,tp.start_time,tp.`status`,tp.amount,tp.method,tp.trx_date,tp.external_ref_id,tp.is_used,tp.used_time,tp.grace_period,cis.plate_no
                FROM psm_ticket_payment tp
                LEFT JOIN psm_car_in_site cis on (tp.ticket_id = cis.id)
                WHERE tp.created_at >= '$startdate' AND tp.created_at <='$enddate'
            ";
        $result = DB::select(DB::raw($sql));
        return $result;
    }

    public static function totalPayment($startdate, $enddate) {
        $sql = "SELECT SUM(amount) total
                FROM psm_ticket_payment
                WHERE created_at >= '$startdate' AND created_at <='$enddate' 
            ";
        $result = DB::select(DB::raw($sql));
        return $result;
    }

    
}
