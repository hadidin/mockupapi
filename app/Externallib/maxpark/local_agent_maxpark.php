<?php

/**
 * Local agent APIs for MaxPark
 */

// Helper functions
require_once  dirname(__FILE__) . DIRECTORY_SEPARATOR . 'helper.php' ;
// Result functions
require_once  dirname(__FILE__) . DIRECTORY_SEPARATOR . 'result.php' ;
// MaxPark packet functions
require_once  dirname(__FILE__) . DIRECTORY_SEPARATOR . 'maxpark_packet.php' ;
// Config settings
// require_once  dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config.php' ;
// Tcp  functions
require_once  dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tcp_socket.php' ;
// Ftp  functions
#require_once  dirname(__FILE__) . DIRECTORY_SEPARATOR . 'ftp.php' ;



//define ('SYNC_STATUS_NORMAL', $SYNC_STATUS_NORMAL);


//define('VZCENTER_PICTURE_PATH','home/kipleadmin/vzcenter_release/ubuntu64/');
define('VZCENTER_PICTURE_PATH','C:/wamp/www/kiplepark-localpsm/public/img/071524519058_clip.jpg');

/**
 * request_get_barcode_ticket : send 'get barcode ticket' request to MaxPark and get response
 *
 * @param $barcode : MaxPark barcode,20 chars
 */
function request_get_barcode_ticket($barcode){
    // pack request packet
    $result = pack_get_barcode($barcode);
    if (!is_result_success($result)) {
        _log(__FUNCTION__ . ' pack error:'.json_encode($result));
        return $result;
    }
    $packet = get_result_data_key($result,'packet');
    // get the server parameters
//    global $cfg_maxpark_tcp_servers;
//    $cfg = $cfg_maxpark_tcp_servers['barcode'];
    $cfg_maxpark_tcp_servers = config('maxpark.cfg_maxpark_tcp_servers');
    $cfg = $cfg_maxpark_tcp_servers['barcode'];

    // send packet to MaxPark
    $result = tcp_request($packet,$cfg['ip'],$cfg['port'],$cfg['recv_timeout'],$cfg['send_timeout']);
    if (!is_result_success($result)) {
        _log(__FUNCTION__ . ' request error:'.json_encode($result));
        return $result;
    }
    $response = get_result_data_key($result,'response');
    // parse the response
    $result = parse_get_ticket_response($response);
    if (!is_result_success($result)) {
        _log(__FUNCTION__ . ' parse error:'.json_encode($result));
        return $result;
    }
    $data = get_result_data($result);
    // check the ticket result
    $result2 = check_ticket($data['ticket']);
    if (!is_result_success($result2)) {
        _log(__FUNCTION__ . ' check ticket error:'.json_encode($result));
        return $result2;
    }
    return $result;
}

/**
 * request_get_eticket : send 'get eticket' request to MaxPark and get response
 *
 * @param $eticket : MaxPark eticket,8 chars
 */
function request_get_eticket($eticket){
    // pack request packet
    $result = pack_get_eticket($eticket);
    if (!is_result_success($result)) {
        _log(__FUNCTION__ . ' pack error:'.json_encode($result));
        return $result;
    }
    $packet = get_result_data_key($result,'packet');
    // get the server parameters
    $cfg_maxpark_tcp_servers = config('maxpark.cfg_maxpark_tcp_servers');
    $cfg = $cfg_maxpark_tcp_servers['barcode'];



    // send packet to MaxPark
    $result = tcp_request($packet,$cfg['ip'],$cfg['port'],$cfg['recv_timeout'],$cfg['send_timeout']);
    if (!is_result_success($result)) {
        _log(__FUNCTION__ . ' request error:'.json_encode($result));
        return $result;
    }
    $response = get_result_data_key($result,'response');
    // parse the response
    $result = parse_get_ticket_response($response);
    if (!is_result_success($result)) {
        _log(__FUNCTION__ . ' parse error:'.json_encode($result));
        return $result;
    }
    $data = get_result_data($result);
    // check the ticket result
    $result2 = check_ticket($data['ticket']);
    if (!is_result_success($result2)) {
        return $result2;
    }
    return $result;
}

/**
 * request_authorise_ticket : send authorize ticket request to MaxPark
 *
 * @param $ticket : the ticket string, 8 chars
 * @param $code : authorize code , 12 chars
 * @param $amount : the parking fee, in sen
 *
 */
function request_authorise_ticket($ticket,$code,$amount){
    $result = pack_authorize_payment($ticket,$code,$amount);
    if (!is_result_success($result)) {
        _log(__FUNCTION__ . ' pack error:'.json_encode($result));
        return $result;
    }
    $packet = get_result_data_key($result,'packet');
    // get the server parameters
    $cfg_maxpark_tcp_servers = config('maxpark.cfg_maxpark_tcp_servers');
    $cfg = $cfg_maxpark_tcp_servers['barcode'];
    // send packet to MaxPark
    $result = tcp_request($packet,$cfg['ip'],$cfg['port'],$cfg['recv_timeout'],$cfg['send_timeout']);
    if (!is_result_success($result)) {
        _log(__FUNCTION__ . ' request error:'.json_encode($result));
        return $result;
    }
    $response = get_result_data_key($result,'response');
    // parse the response
    $result = parse_authorize_payment_response($response);
    if (!is_result_success($result)) {
        _log(__FUNCTION__ . ' parse error:'.json_encode($result));
        return $result;
    }

    $data = get_result_data($result);
    #dd($data);
    // check the ticket result
    $result2 = check_auth_response_code($data['status']);
    #dd($result2);
    if (!is_result_success($result2)) {
        #dd($result2);
        return $result2;
    }
    return $result;
}
/**
 * get the picture local file path
 *
 * @param @picture_url : the picture url post by vzcenter;
 */
function get_picture_local_file($vzcenter_path,$picture_url) {
    /*
     full url : result/bb3fb8a4-ff933e92/2019-01-31/21/210011251940_full.jpg
     --->
     /home/kipleadmin/vzcenter_release/ubuntu64/result/bb3fb8a4-ff933e92/2019-01-31/21/210011251940_full.jpg
     */

    $fullpath=$vzcenter_path.$picture_url; #for ubuntu live environment
    #$fullpath=VZCENTER_PICTURE_PATH;  #for windows os developement
    _log("local picture to be sent= ".$fullpath);
    return $fullpath ;
}
/**
 * request_license_plate_detect :  send license plate detect request to MaxPark
 *
 * @param $camera_num : camera number, max 4 chars
 * @param $plate_num : license plate number, max 16 chars
 * @param $picture_url : the full plate picture url
 * @param $is_kiple_user : the plate number is belongs to a kiple user or not
 */
function request_license_plate_detect($camera_num,$plate_num,$picture_url,$is_kiple_user,$vzcenter_path,$lane_in_out_flag){
    // update image to ftp server first
    $cfg_maxpark_ftp_servers = config('maxpark.cfg_maxpark_ftp_servers');
    $cfg = $cfg_maxpark_ftp_servers['lpr'];


    try{
        $upload_ftp_flag = $cfg['upload_ftp_flag'];
    }
    catch (\Exception $e){
        $upload_ftp_flag = 1;
    }

    /*
     * entry only.for exit no need to upload picture.. and if flag ftp upload true only will upload picture
     * if this ftp module failed to upload picture, we still send request exit to maxpark becasue maxpark already update
     * the logic to accept payment on APS without having car picture as long as user enter the correct plate number.
     */
    if($lane_in_out_flag == 0 && $upload_ftp_flag == 1){
        $remote_filename = get_ftp_upload_image_filename($camera_num,$plate_num);
        $local_filename = get_picture_local_file($vzcenter_path,$picture_url);
        include(app_path().'/Externallib/maxpark/ftp.php');
        $result = ftp_upload($cfg['host'],$cfg['port'],$cfg['timeout'],$cfg['username'],$cfg['password'],
            $cfg['remote_path'],$remote_filename,$local_filename,$cfg['mode']);
        if (!is_result_success($result)) {
            _log(__FUNCTION__ . ' ftp error:'.json_encode($result));
        }
    }

    // pack the license plate detect packet
    $result = pack_license_plate_detect($camera_num,$plate_num,$is_kiple_user?'KiplePark@':'');
    if (!is_result_success($result)) {
        _log(__FUNCTION__ . ' pack error:'.json_encode($result));
        return $result;
    }
    $packet = get_result_data_key($result,'packet');
    // get the server parameters
    $cfg_maxpark_tcp_servers = config('maxpark.cfg_maxpark_tcp_servers');
    $cfg = $cfg_maxpark_tcp_servers['lpr'];
    // send packet to MaxPark
    // print_r($cfg);die;
    $result = tcp_request($packet,$cfg['ip'],$cfg['port'],$cfg['recv_timeout'],$cfg['send_timeout']);

    if (!is_result_success($result)) {
        _log(__FUNCTION__ . ' request error:'.json_encode($result));
        return $result;
    }
    $response = get_result_data_key($result,'response');
    // parse the response
    $result = parse_license_plate_detect_response($response);
    if (!is_result_success($result)) {
        _log(__FUNCTION__ . ' parse error:'.json_encode($result));
        return $result;
    }
    return $result;
}

/**
 * request_license_plate_detects :  send two license plate detect request to MaxPark
 *
 * @param $camera_num : camera number, max 4 chars
 * @param $plate_num : license plate number, max 16 chars
 * @param $plate_num2 : license plate number2, max 16 chars
 * @param $picture_url : the full plate picture url
 * @param $is_kiple_user : the plate number is belongs to a kiple user or not
 */
function request_license_plate_detect2($camera_num,$plate_num,$plate_num2,$picture_url,$is_kiple_user,$vzcenter_path,$lane_in_out_flag){
    // update image to ftp server first
    $cfg_maxpark_ftp_servers = config('maxpark.cfg_maxpark_ftp_servers');
    $cfg = $cfg_maxpark_ftp_servers['lpr'];


    try{
        $upload_ftp_flag = $cfg['upload_ftp_flag'];
    }
    catch (\Exception $e){
        $upload_ftp_flag = 1;
    }

    /*
     * entry only.for exit no need to upload picture.. and if flag ftp upload true only will upload picture
     * if this ftp module failed to upload picture, we still send request exit to maxpark becasue maxpark already update
     * the logic to accept payment on APS without having car picture as long as user enter the correct plate number.
     */
    if($lane_in_out_flag == 0 && $upload_ftp_flag == 1){
        $remote_filename = get_ftp_upload_image_filename($camera_num,$plate_num);
        $local_filename = get_picture_local_file($vzcenter_path,$picture_url);
        include(app_path().'/Externallib/maxpark/ftp.php');
        $result = ftp_upload($cfg['host'],$cfg['port'],$cfg['timeout'],$cfg['username'],$cfg['password'],
            $cfg['remote_path'],$remote_filename,$local_filename,$cfg['mode']);
        if (!is_result_success($result)) {
            _log(__FUNCTION__ . ' ftp error:'.json_encode($result));
        }
    }

    // pack the license plate detect packet
    $result = pack_license_plate_detect2($camera_num,$plate_num,$plate_num2,$is_kiple_user?'KiplePark@':'');
    if (!is_result_success($result)) {
        _log(__FUNCTION__ . ' pack error:'.json_encode($result));
        return $result;
    }
    $packet = get_result_data_key($result,'packet');
    // get the server parameters
    $cfg_maxpark_tcp_servers = config('maxpark.cfg_maxpark_tcp_servers');
    $cfg = $cfg_maxpark_tcp_servers['lpr'];
    // send packet to MaxPark
    // print_r($cfg);die;
    $result = tcp_request($packet,$cfg['ip'],$cfg['port'],$cfg['recv_timeout'],$cfg['send_timeout']);

    if (!is_result_success($result)) {
        _log(__FUNCTION__ . ' request error:'.json_encode($result));
        return $result;
    }
    $response = get_result_data_key($result,'response');
    // parse the response
    $result = parse_license_plate_detect_response($response);
    if (!is_result_success($result)) {
        _log(__FUNCTION__ . ' parse error:'.json_encode($result));
        return $result;
    }
    return $result;
}
/**
 * request_check_online : check MaxPark server is online or not
 *
 */
function request_check_online(){
    $result = pack_idle(true);
    if (!is_result_success($result)) {
        _log(__FUNCTION__ . ' pack error:'.json_encode($result));
        return $result;
    }
    $packet = get_result_data_key($result,'packet');
    // get the server parameters
    $cfg_maxpark_tcp_servers = config('maxpark.cfg_maxpark_tcp_servers');
    $cfg = $cfg_maxpark_tcp_servers['barcode'];
    // send packet to MaxPark
    $result = tcp_request($packet,$cfg['ip'],$cfg['port'],$cfg['recv_timeout'],$cfg['send_timeout']);
    if (!is_result_success($result)) {
        _log(__FUNCTION__ . ' request error:'.json_encode($result));
        return $result;
    }
    $response = get_result_data_key($result,'response');
    // parse the response
    $result = parse_idle_response($response);
    if (!is_result_success($result)) {
        _log(__FUNCTION__ . ' parse error:'.json_encode($result));
        return $result;
    }
    $cmd = get_result_data_key($result,'cmd');
    if ($cmd==CMD_SYNCHRONIZE_TIME) {
        // TODO , synchronize time
        _log(__FUNCTION__ . ' need to synchronize:'.get_result_data_key('value'));
    }
    return $result;
}

/**
 * request_manual_license_plate :  send license plate detect request to MaxPark manually
 *
 * @param $camera_num : camera number, max 4 chars
 * @param $plate_num : license plate number, max 16 chars
 * @param $picture_url : the full plate picture url
 * @param $is_kiple_user : the plate number is belongs to a kiple user or not
 */
function request_manual_license_plate($camera_num,$plate_num,$picture_url,$is_kiple_user,$vzcenter_path,$lane_in_out_flag){
    _log(__FUNCTION__ . ' pack request_manual_license_plate:');

    // update image to ftp server first
    $cfg_maxpark_ftp_servers = config('maxpark.cfg_maxpark_ftp_servers');
    $cfg = $cfg_maxpark_ftp_servers['lpr'];

    try{
        $upload_ftp_flag = $cfg['upload_ftp_flag'];
    }
    catch (\Exception $e){
        $upload_ftp_flag = 1;
    }

    /*
     * entry only.for exit no need to upload picture.. and if flag ftp upload true only will upload picture
     * if this ftp module failed to upload picture, we still send request exit to maxpark becasue maxpark already update
     * the logic to accept payment on APS without having car picture as long as user enter the correct plate number.
     */
    if($lane_in_out_flag == 0 && $upload_ftp_flag == 1){
        $remote_filename = get_ftp_upload_image_filename($camera_num,$plate_num);
        $local_filename = get_picture_local_file($vzcenter_path,$picture_url);
        include(app_path().'/Externallib/maxpark/ftp.php');
        $result = ftp_upload($cfg['host'],$cfg['port'],$cfg['timeout'],$cfg['username'],$cfg['password'],
            $cfg['remote_path'],$remote_filename,$local_filename,$cfg['mode']);
        if (!is_result_success($result)) {
            _log(__FUNCTION__ . ' ftp error:'.json_encode($result));
        }
    }

    // pack the manual license plate message packet
    $result = pack_manual_license_plate_message($camera_num,$plate_num,$is_kiple_user?'KiplePark@':'');
    if (!is_result_success($result)) {
        _log(__FUNCTION__ . ' pack error:'.json_encode($result));
        return $result;
    }
    $packet = get_result_data_key($result,'packet');
    _log('request_manual_license_plate : '.bin2hex(bytes_to_string($packet)));
    // get the server parameters
    $cfg_maxpark_tcp_servers = config('maxpark.cfg_maxpark_tcp_servers');
    $cfg = $cfg_maxpark_tcp_servers['lpr_manual_leave'];
    // send packet to MaxPark
    $result = tcp_request($packet,$cfg['ip'],$cfg['port'],$cfg['recv_timeout'],$cfg['send_timeout']);

    if (!is_result_success($result)) {
        _log(__FUNCTION__ . ' request error:'.json_encode($result));
        return $result;
    }
    $response = get_result_data_key($result,'response');
    // parse the response
    $result = parse_manual_license_plate_response($response);
    if (!is_result_success($result)) {
        _log(__FUNCTION__ . ' parse error:'.json_encode($result));
        return $result;
    }
    return $result;
}
