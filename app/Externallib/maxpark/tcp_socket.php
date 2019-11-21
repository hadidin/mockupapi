<?php

/**
 * TCP socket APIs
 */

// Helper functions
require_once  dirname(__FILE__) . DIRECTORY_SEPARATOR . 'helper.php' ;
// Result functions
require_once  dirname(__FILE__) . DIRECTORY_SEPARATOR . 'result.php' ;

/**
 * get_last_socket_error : get the last socket error with code and string
 * 
 */
function get_last_socket_error() {
    $err_code = socket_last_error();
    $err_msg = socket_strerror($err_code);
    return "code:$err_code,msg:$err_msg";
}

/**
 * tcp_request : send request via tcp
 * 
 * @param $req_data : request data in bytes
 * @param $ip : tcp server ip address
 * @param $port : tcp server port
 * @param $recv_timeout : socket recv timeout, in seconds, default value is 10s
 * @param $sent_timeout : socket send timeout, in seconds, default value is 10s
 * 
 * @return $result in array. $result['success'] indicates success or not
 */
function tcp_request($req_data,$ip,$port,$recv_timeout=10,$sent_tiemout=10) {
    $socket = @socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
    if ($socket === false) {
        return result_socket_error(get_last_socket_error());
    }
    $result = result_success();
    do {
        @socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array("sec" => $recv_timeout, "usec" => 0));
        @socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array("sec" => $recv_timeout, "usec" => 0));
        if(@socket_connect($socket,$ip,$port) === false){
            $result = result_socket_error("connect ($ip:$port) error:".get_last_socket_error());
            break;
        }
        $msg = bytes_to_string($req_data);
        $written_len = @socket_write($socket,$msg,strlen($msg));
        if ($written_len === false) {
            $result = result_socket_error('write error:'.get_last_socket_error());
            break;
        }
        $resp_msg = @socket_read($socket,256,PHP_BINARY_READ);
        if ($resp_msg === false) {
            $result = result_socket_error('read error:'.get_last_socket_error());
            break;
        }
        $data = array (
            'response' => string_to_bytes($resp_msg),
        );
        $result = result_success_data($data);
    } while (0);
    @socket_close($socket);
    return $result;
 }

