<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ErrorCodeController extends Controller
{
    public static function error_response($function_name,$error_code) {
        //get error message from config file
        $aa = config('error_code.api');
        if (isset($aa["$function_name"]["$error_code"])) {
            $message = $aa["$function_name"]["$error_code"];
        }
        else{
            $message = "Unknown Error Code";
        }
        /*
         * error response format
         * all api error response will return in this format
         */
        $result = array(
            'success' => false,
            'error' => $error_code,
            'message' => $message,
        );
        return $result;
    }
    public static function success_response($data){
        /*
         * success response format
         * all api success response will return in this format
         */
        $result = array(
            'success' => true,
            'error' => 'success',
            'message' => 'success',
            'data' => $data,
        );
        return $result;
    }


}
