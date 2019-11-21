<?php

/**
 * FTP APIs
 */

// Helper functions
require_once  dirname(__FILE__) . DIRECTORY_SEPARATOR . 'helper.php' ;
// Result functions
require_once  dirname(__FILE__) . DIRECTORY_SEPARATOR . 'result.php' ;




//dd($ftp_config_dict);
//
//
////$ftp_config=json_encode($ftp_config,0);
////
////var_dump(json_decode($ftp_config));
////var_dump(json_decode($ftp_config, true));
//
//
//dd($ftp_config_dict);




/**
 * ftp_make_dir : make dir for $file_path
 */
function ftp_make_dir($ftp_conn,$file_path) {
    $path_arr = explode('/',$file_path);
    $file_name = array_pop($path_arr);
    $path_div = count($path_arr);
    
    foreach($path_arr as $val) {
        if(@ftp_chdir($ftp_conn,$val) === FALSE){
            $tmp = @ftp_mkdir($ftp_conn,$val);
            if($tmp == FALSE){
                return false;
            }
            @ftp_chdir($ftp_conn,$val);
        }
    }
    for($i=1;$i<=$path_div;$i++){
        @ftp_cdup($ftp_conn);
    }
    return true;
}
/**
 * ftp_upload : upload a file to remote via ftp
 */
function ftp_upload($ftp_host,$ftp_port,$timeout = 3,$ftp_usr,$ftp_password,$remote_path,$remote_file,$local_file,$ftp_mode) {
    $ftp_conn = @ftp_connect($ftp_host,$ftp_port,$timeout);
    if ($ftp_conn === false) {
        _log('ftp connect error:'."$ftp_host : $ftp_port : $ftp_usr : $ftp_password");
        return result_ftp_error("ftp connect error to $ftp_host:$ftp_port");
    }
    $ret = @ftp_login($ftp_conn,$ftp_usr,$ftp_password);
    if (!$ret) {
        return result_ftp_error('ftp login error');
    }

    //get pssive from config file.bjpc is active, vsq is passive
    if($ftp_mode == 'passive'){
        $ret = @ftp_pasv($ftp_conn,TRUE);
        if (!$ret){
            return result_ftp_error('ftp passiv failed');
        }
    }

    /*
    if (!ftp_make_dir($ftp_conn,$remote_path)) {
        return result_ftp_error('ftp make dir error');
    } 
    */  
    // @ftp_chdir($ftp_conn,$remote_path);
    _log('remote file is :'.$remote_file);
    _log('local file is :'.$local_file);
    if (!@ftp_put($ftp_conn,$remote_file,$local_file,FTP_BINARY)) {
        _log('ftp upload failed:'.json_encode(error_get_last()));
        return result_ftp_error('ftp upload error');
    }
    @ftp_close($ftp_conn);
    return result_success();
}
/*
function test_cases() {
    print_r(ftp_upload('172.16.1.69',21,10,'hadi','hadi123','vehicle_plate','2019025CN011234567890123456.png','/Users/carycui/Desktop/test.png'));
}

function main() {
    date_default_timezone_set("Asia/Kuala_Lumpur");
    error_reporting(1);
    test_cases();
}
main();
*/

