<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AutoDeductWhitelistController extends Controller

{
    private $currentPath;
    public function __construct(){
        $this->middleware('auth');
        $this->currentPath= Route::getFacadeRoot()->current()->uri();
    }

    protected function hasPermission(Request $request,$permission) {
        $user = $request->user();
        if (empty($user)) {
            return false;
        }
        $permissions = $user->getAllPermissions();
        foreach($permissions as $p) {
            if ($p->name==$permission) {
                return true;
            }
        }
        return false;
    }

    public function index(Request $request){
        return view('auto_deduct_whitelist');
    }
    /**
     * search function, called by ajax
     */
    public function allRecords(Request $request){
        $records = \App\AutodeductWhitelist::getAllRecords();
        $result = array(
            'code' => 0,
            'message' => 'success',
            'data' => $records,
        );
        return response()->json($result);
    }
    /**
     *
     */
    public function createRecord(Request $request) {
        $permission = 'add_auto_deduct_whitelist';
        if (!$this->hasPermission($request,$permission)){
            $result = array(
                'code' => 1,
                'message' => 'No permission.',
            );
            return response()->json($result);
        }
        $validator = Validator::make($request->all(), [
            'email' => 'bail|required',
            'enabled' => 'bail|boolean',
        ]);
        if ($validator->fails()) {
            $result = array(
                'code' => 1,
                'message' => $validator->errors()->first(),
            );
            return response()->json($result);
        }
        if (!filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            $result = array(
                'code' => 1,
                'message' => "invalid email format",
            );
            return response()->json($result);
        }
        $last_id = \App\AutodeductWhitelist::createRecord($request->email,$request->enable_flag);
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
    /**
     * update the entry log record review flag/ plate number
     */
    public function updateRecord(Request $request) {
        $permission = 'edit_auto_deduct_whitelist';
        if (!$this->hasPermission($request,$permission)){
            $result = array(
                'code' => 1,
                'message' => 'No permission.',
            );
            return response()->json($result);
        }
        $validator = Validator::make($request->all(), [
            'id' => 'bail|required',
            'email' => 'bail|required',
            'enabled' => 'bail|boolean',
        ]);
        if ($validator->fails()) {
            $result = array(
                'code' => 1,
                'message' => $validator->errors()->first(),
            );
            return response()->json($result);
        }
        $result = \App\AutodeductWhitelist::updateRecord($request->id,$request->email,$request->enable_flag);
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

}
