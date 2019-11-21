<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WhiteList extends Model
{
    protected $table = 'psm_white_list';

    public static function searchRecordByPlateNo($plate_no)
    {
        $record = DB::table('psm_white_list')
            ->select(['id','plate_no','valid_from','valid_until','username','description','enable_flag','updated_at','created_at'])
            ->where('plate_no', $plate_no)
            ->first();
        return $record;
    }

    public static function getAllRecords()
    {
        $records = DB::table('psm_white_list')
            ->select(['id','plate_no','valid_from','valid_until','username','description','enable_flag','updated_at','created_at'])
            ->get();
        return $records;
    }


    public static function createRecord($plate_no,$username,$description,$valid_from,$valid_until,$enable_flag)
    {
        $result =DB::table('psm_white_list')
            ->insert(['plate_no' => $plate_no,'username' => $username,'description' => $description,
                'valid_from' => $valid_from, 'valid_until' => $valid_until, 'enable_flag' => $enable_flag,
                'created_at'  => date("Y-m-d H:i:s"),'updated_at' => date("Y-m-d H:i:s")]
            );
        if ($result!=1) {
            return false;
        }
        $id = DB::getPdo()->lastInsertId();
        return $id;
    }

    public static function updateRecord($id,$plate_no,$username,$description,$valid_from,$valid_until,$enable_flag)
    {
        $result =DB::table('psm_white_list')
            ->where('id', $id)
            ->update(['plate_no' => $plate_no,'username' => $username,'description' => $description,
             'valid_from' => $valid_from, 'valid_until' => $valid_until, 'enable_flag' => $enable_flag,
             'updated_at' => date("Y-m-d H:i:s")]
            );
        if ($result!=1) {
            return false;
        }
        return true;
    }

    public static function deleteRecord($id)
    {
        $result =DB::table('psm_white_list')
            ->where('id', $id)
            ->delete();
        if ($result!=1) {
            return false;
        }
        return true;
    }            
}
