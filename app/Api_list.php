<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Api_list extends Model
{
    public static function getAllRecords()
    {
        $records = DB::table('api_list')
            ->select(['id','name','return_data','http_status_code','enabled','created_at','updated_at'])
            ->get();
        return $records;
    }
    public static function getARecords($id)
    {
        $records = DB::table('api_list')
            ->select(['id','name','return_data','http_status_code','enabled','created_at','updated_at'])
            ->where('id',$id)
            ->get();
        return $records;
    }
    public static function createRecord($name,$return_data,$http_status_code,$enabled)
    {
        $result = DB::table('api_list')->insert(
            ['name' => $name,'return_data' => $return_data, 'http_status_code'=>$http_status_code,'enabled'=>$enabled,
                'created_at'  => date("Y-m-d H:i:s"),'updated_at' => date("Y-m-d H:i:s")]
        );
        if ($result!=1) {
            return false;
        }
        $id = DB::getPdo()->lastInsertId();
        return $id;
    }

    public static function updateRecord($id,$name,$return_data,$http_status_code,$enabled)
    {
        $result = DB::table('api_list')
            ->where('id', $id)
            ->update([
                    'name' => $name,
                    'return_data' => $return_data, 'http_status_code'=>$http_status_code,'enabled'=>$enabled,
                    'updated_at' => date("Y-m-d H:i:s")
                ]
            );
        if ($result!=1) {
            return false;
        }
        return true;
    }

    public static function deleteRecord($id)
    {
        $result = DB::table('api_list')
            ->where('id', $id)
            ->delete();
        if ($result!=1) {
            return false;
        }
        return true;
    }
    public static function checkUserWhitelist($user_id){
        $records = DB::table('psm_autodeduct_whitelist')
            ->where('email',$user_id)
            ->where('enable_flag',1)
            ->select(['id'])
            ->get();
        return $records;
    }
}
