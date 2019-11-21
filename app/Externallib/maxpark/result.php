<?php

/**
 * Function return result APIs
 */

define('ERROR_SUCCESS','success');
define('ERROR_PARAMETER_VALIDATE','error_paramter_validate');
define('ERROR_SOCKET','error_socket');
define('ERROR_FTP','error_ftp');
define('ERROR_RESPONSE_PARSE','error_response_parse');

/**
 * Generate the sucess result
 */
function result_success() {
    $result = array(
        'success' => true,
        'error' => ERROR_SUCCESS,
        'message' => 'succces',
    );
    return $result;
}

/**
 * Generate the sucess result with data
 * 
 * @param $data : the data array object
 */
function result_success_data($data) {
    $result = array(
        'success' => true,
        'error' => ERROR_SUCCESS,
        'message' => 'succces',
        'data' => $data,
    );
    return $result;
}

/**
 * Generate the result with error and message
 * 
 * @param $error : string, the error code
 * @param $message : string,the error message
 */
function result_error($error,$message) {
    $result = array(
        'success' => false,
        'error' => $error,
        'message' => $message,
    );
    return $result;
}

/**
 * Generate the paramter validate error result with error message
 * 
 * @param $message : the error message
 */
function result_parameter_validate_error($message) {
    $result = array(
        'success' => false,
        'error' => ERROR_PARAMETER_VALIDATE,
        'message' => $message,
    );
    return $result;
}

/**
 * Generate the socket error result with error message
 * 
 * @param $message : the error message
 */
function result_socket_error($message) {
    $result = array(
        'success' => false,
        'error' => ERROR_SOCKET,
        'message' => $message,
    );
    return $result;
}

/**
 * Generate the ftp error result with error message
 * 
 * @param $message : the error message
 */
function result_ftp_error($message) {
    $result = array(
        'success' => false,
        'error' => ERROR_FTP,
        'message' => $message,
    );
    return $result;
}

/**
 * Generate the response parse error result with error message
 * 
 * @param $message : the error message
 */
function result_response_parse_error($message) {
    $result = array(
        'success' => false,
        'error' => ERROR_RESPONSE_PARSE,
        'message' => $message,
    );
    return $result;
}

/**
 * Check result is success or not
 * 
 * @param $result : the $result array
 */
function is_result_success($result) {
    return $result['success'];
}

/**
 * Get the error code from the result 
 *
 * @param $result : the $result array
 */
function get_result_error_code($result) {
    return $result['errror'];
}

/**
 * Get the error message from the result 
 * 
 * @param $result : the $result array
 */
function get_result_error_message($result) {
    return $result['message'];
}

/**
 * Get the data array from the result in success case
 * 
 * @param $result : the $result array
 */
function get_result_data($result) {
    return $result['data'];
}

/**
 * Get the specified value in data array from the result in success case
 
 * @param $result : the $result array
 * @param $key : the key in data array
 * 
 */
function get_result_data_key($result,$key) {
    $data = get_result_data($result);
    return $data[$key];
}
