<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixPlateNo extends Model
{
    protected $table = 'psm_fix_plate_no';

    public static function getDesirePlateNo($plate_no)
    {
        DB::enableQueryLog();
        try{
            $desire_plate_no = DB::table('psm_fix_plate_no')
                ->select('desire_plate_no')
                ->where('plate_no', $plate_no)
                ->where('enable_flag', 1)
                ->first();
            if($desire_plate_no){
                return $desire_plate_no->desire_plate_no;
            }
            else{
                return false;
            }
        }
        catch (\Exception $e){
            Log::error("failed to get from db $e");
            return false;
        }

    }

    public static function getAllRecords()
    {
        $records = DB::table('psm_fix_plate_no')
                ->select(['id','plate_no','desire_plate_no','enable_flag','update_at'])
                ->get();
        return $records;
    }


    public static function createRecord($plate_no,$desire_plate_no,$enable_flag)
    {
        $result = DB::table('psm_fix_plate_no')->insert(
            ['plate_no' => $plate_no,'desire_plate_no' => $desire_plate_no,'enable_flag' => $enable_flag,
                'created_at'  => date("Y-m-d H:i:s"),'update_at' => date("Y-m-d H:i:s")]
        );
        if ($result!=1) {
            return false;
        }
        $id = DB::getPdo()->lastInsertId();
        return $id;
    }

    public static function updateRecord($id,$plate_no,$desire_plate_no,$enable_flag)
    {
        $result = DB::table('psm_fix_plate_no')
            ->where('id', $id)
            ->update([
                'plate_no' => $plate_no,'desire_plate_no' => $desire_plate_no,'enable_flag' => $enable_flag,
                'update_at' => date("Y-m-d H:i:s")
            ]
        );        
        if ($result!=1) {
            return false;
        }
        return true;
    }

    public static function deleteRecord($id)
    {
        $result = DB::table('psm_fix_plate_no')
            ->where('id', $id)
            ->delete();
        if ($result!=1) {
            return false;
        }
        return true;
    }            
}
