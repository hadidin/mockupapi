<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use DateTime;

class PaymentLogs extends Model
{
    protected $table = 'psm_payment_logs';

    public static function insert_payment_trx_log($ticket_id,$status_id,$external_ref_id,$amount,$sst,$payment_loc){
        DB::enableQueryLog();
        $date_now = date("Y-m-d H:i:s");
        DB::table('psm_payment_logs')->insert(
            [
                'ticket_id' => $ticket_id,
                'status_id' => $status_id,
                'external_ref_id' => $external_ref_id,
                'amount' => $amount,
                'sst' => $sst,
                'payment_loc' => $payment_loc,
                'trx_date' => $date_now,
                'external_ref_id' => $external_ref_id
            ]
        );
        $id = DB::getPdo()->lastInsertId();

        DB::table('psm_ticket_payment')
            ->where('ticket_id',$ticket_id)
            ->update(
            [
                'status' => $status_id,
                'amount' => $amount,
                'sst' => $sst,
                'pay_location' => $payment_loc,
                'trx_date' => date('Y-m-d H:i:s'),
                'external_ref_id' => $external_ref_id
            ]
        );

        return $id;

    }

}
