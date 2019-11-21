<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\EntryLog;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    private $currentPath;
    public function __construct()
    {
        #$this->middleware('auth');
        $this->currentPath= Route::getFacadeRoot()->current()->uri();
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
	#dd("aaa");
        return view('api_list');
    }

    public function allRecords(Request $request){
        $records = \App\Api_list::getAllRecords();
        $result = array(
            'code' => 0,
            'message' => 'success',
            'data' => $records,
        );
//        log::error("eeee $records");
//        dd($records);
        return response()->json($result);
    }
    public function createRecord(Request $request) {

        $last_id = \App\Api_list::createRecord($request->name,$request->return_data,$request->http_status_code,$request->enable_flag);
        if ($last_id==false) {
            $result = array(
                'code' => 1,
                'message' => 'Create record failed.',
            );
            return response()->json($result);
        }
        $result = array(
            'code' => 0,
            'message' => 'success',
            'data' => array(
                'last_id' => $last_id,
            ),
        );
        return response()->json($result);
    }



    public function updateRecord(Request $request) {

        $result = \App\Api_list::updateRecord($request->id,$request->name,$request->return_data,$request->http_status_code,$request->enable_flag);
        if ($result==false) {
            $result = array(
                'code' => 1,
                'message' => 'Update record failed.',
            );
            return response()->json($result);
        }
        $result = array(
            'code' => 0,
            'message' => 'success'
        );
        return response()->json($result);
    }

    /**
     *
     */
    public function deleteRecord(Request $request) {
        $permission = 'delete_auto_deduct_whitelist';
        if (!$this->hasPermission($request,$permission)){
            $result = array(
                'code' => 1,
                'message' => 'No permission.',
            );
            return response()->json($result);
        }
        $validator = Validator::make($request->all(), [
            'id' => 'bail|required',
        ]);
        if ($validator->fails()) {
            $result = array(
                'code' => 1,
                'message' => $validator->errors()->first(),
            );
            return response()->json($result);
        }
        $result = \App\AutodeductWhitelist::deleteRecord($request->id);
        if ($result==false) {
            $result = array(
                'code' => 1,
                'message' => 'Delete record failed.',
            );
            return response()->json($result);
        }
        $result = array(
            'code' => 0,
            'message' => 'success'
        );
        return response()->json($result);
    }

    public static function generate_response($id) {
        $records = \App\Api_list::getARecords($id);
        $text = $records[0]->return_data;
        $http_code = $records[0]->http_status_code;

        $json_text = str_replace(array("\n", "\r"," "), '', $text);
        $json_payload = json_decode($json_text, true);
        return response($json_payload, $http_code);
    }
}
