<?php

/**
 * a helper class for runner check
 */
class Runner {    
    protected $_name = '';
    protected $_auto_clean = false;
        
    public function __construct ($name) {
        $this->_name = $name;
    }
    
    public function __destruct() {
        if ($this->_auto_clean) {
            $this->clean();            
        }
    }
    
    public function exists(){
        $pid_file = sys_get_temp_dir() . '/' . $this->_name . '.run.pid';
        if(file_exists($pid_file)){
            $pid = file_get_contents($pid_file);
            if(file_exists('/proc/')){
                if(file_exists('/proc/'.$pid)){
                    return true;
                }
            }else{
                if (function_exists('pcntl_getpriority')) {
                    @$r = pcntl_getpriority($pid);
                    if($r>0){
                        return true;
                    }
                }
            }
        }
        if(function_exists('posix_getpid')){
            $pid = posix_getpid();
        }else{
            return null;
        }
        file_put_contents($pid_file, $pid);
        return false;
    }
    
    public function autoClean() {
        $this->_auto_clean = true;
    }

    public function clean(){
        $pid_file = sys_get_temp_dir() . '/' . $this->_name . '.run.pid';
        if(file_exists($pid_file)){
            unlink($pid_file);
        }
    }
}

/**
 * log function
 */
function _log($str) {
    $log_file_name = dirname(__FILE__) . DIRECTORY_SEPARATOR."sync_to_cloud.log";
	$info = date("Y-m-d H:i:s") . "|" . $str . "\n";
	print($info);
	file_put_contents($log_file_name, $info, FILE_APPEND);
}
/**
 * retry interval when cloud server is not availiable
 */
define ('CLOUD_NOT_AVAILIABLE_WAIT',1000000*1);
/**
 * sync interal for next batch records
 */
define ('DATABASE_SYNC_NEXT_WAIT',100);
/**
 * database connect retry interval
 */
define ('DATABASE_RETRY_WAIT',1000000*1);

/**
 * sync status define
 */

$ini_array = parse_ini_file("config.ini",true);

$SYNC_STATUS_NORMAL=$ini_array['sync_to_cloud']['SYNC_STATUS_NORMAL'];
$SYNC_STATUS_DONE=$ini_array['sync_to_cloud']['SYNC_STATUS_DONE'];
$SYNC_STATUS_FAILED=$ini_array['sync_to_cloud']['SYNC_STATUS_FAILED'];
$KP_TOKEN=$ini_array['sync_to_cloud']['KP_TOKEN'];




$SITE_ID=$ini_array['common']['SITE_ID'];
$DB_NAME=$ini_array['common']['DB_NAME'];
$DB_HOST=$ini_array['common']['DB_HOST'];
$DB_USER=$ini_array['common']['DB_USER'];
$DB_PSWD=$ini_array['common']['DB_PSWD'];
$KP_CLOUD=$ini_array['common']['KP_CLOUD'];
$LPRLA_URL=$ini_array['common']['LPRLA_URL'];
$VENDOR_ID=$ini_array['common']['VENDOR_ID'];



define ('SYNC_STATUS_NORMAL', $SYNC_STATUS_NORMAL);
define ('SYNC_STATUS_DONE', $SYNC_STATUS_DONE);
define ('SYNC_STATUS_FAILED', $SYNC_STATUS_FAILED);
define ('SITE_ID' , $SITE_ID);
define ('DB_NAME' , $DB_NAME);
define ('DB_HOST' , $DB_HOST);
define ('DB_USER' , $DB_USER);
define ('DB_PSWD' , $DB_PSWD);
define ('KP_CLOUD' , $KP_CLOUD);
define ('KP_TOKEN' , $KP_TOKEN);
define ('LPRLA_URL' , $LPRLA_URL);
define ('VENDOR_ID' , $VENDOR_ID);

/**
 * connec to database according to the config paramters
 * 
 * @return mixed, database instance or false
 */
function _connect_to_db($config) {
    $db = mysqli_init();
    if (isset($config['ssl'])) {
        $ssl_cfg = $config['ssl'];
        $result = $db->ssl_set($ssl_cfg['key'],$ssl_cfg['cert'],$ssl_cfg['ca'],$ssl_cfg['capath'],$ssl_cfg['cipher']);
        if (!$result) {
            _log("database ssl set failed:".$db->error);
            return false;
        }
    }
    _log("database connect to:".$config['host']);
    $result = $db->real_connect($config['host'],$config['username'],$config['password'],$config['name']);        
    if (!$result) {
        _log("database connect failed:".$db->error);
        return false;
    }
    $db->set_charset('utf8');            
    return $db;
}

/**
 * get unsynced records from local database
 */
function _get_unsynced_records($local_db,$limits=20) {
    $table = 'psm_entry_log';
    $status = SYNC_STATUS_NORMAL;
//    $last_synd_id =

    $myfile = fopen("last_sync_cloud_id.txt", "r") or die("Unable to open file!");
    $last_sync_id = fgets($myfile);
    fclose($myfile);

    if(VENDOR_ID == 'V0004'){
        $vendor_check_result="AND pel.vendor_check_result <> 0";
    }
    else{
        $vendor_check_result="";
    }

    $sql_query = "SELECT
pel.id,pcis.id AS ticket_id,pel.check_result,pel.lane_id,pel.camera_sn,pel.in_out_flag,pel.plate_no,pel.is_success,pel.create_time,pel.car_color,pel.is_season_subscriber,pel.leave_type,pel.sync_status,
pcis.user_id,pcis.auto_deduct_flag,pcis.parking_type,pcis.locked_flag,pcis.season_holder_id
FROM psm_entry_log AS pel
LEFT JOIN psm_car_in_site AS pcis ON pcis.entry_id = pel.id OR pcis.exit_id = pel.id
WHERE pel.id > $last_sync_id AND pel.sync_status = 0 AND pel.check_result IN(4,5,6,7,8,9,10,11,99) $vendor_check_result
ORDER BY pel.id ASC LIMIT $limits";


    $records = array();
    $result = $local_db->query($sql_query);
    if (!$result) {
        _log("[{$__METHOD__}] sql error: {$local_db->error},{$local_db->error}");
        if (false==$local_db->ping()) {
            _log("[{$__METHOD__}] local database ping failed");
            return false;
        }
        return $records;
    }
    while($row = $result->fetch_assoc()){
        $records[] = $row;
    }
    $result->free();
    return $records;
}
/**
 * update records sync status
 */
function _update_records_sync_status($local_db,$records,$new_status) {
    $in_ids_str = '(';
    foreach($records as $row) {
        $in_ids_str .= $row['id'];
        $in_ids_str .= ',';
    }
    $in_ids_str = substr($in_ids_str,0,-1);
    $in_ids_str .= ')';
    $table = 'psm_entry_log';
    date_default_timezone_set("Asia/Kuala_Lumpur");
    $datenow = date("Y-m-d H:i:s");

    $sql_update = "UPDATE $table SET sync_status = '$new_status',datetime_sync_kp_cloud = '$datenow' WHERE id IN $in_ids_str";
//    echo $sql_update;die;
    $result = $local_db->query($sql_update);
    _log(json_encode($result));
}

/**
 * send records to cloud server via http client
 */
function _send_to_cloud($config,$records) {
    $post_data = array(
//        'parkting_site_id' => $config['parking_site_id'],
        'vendor_id' => VENDOR_ID,
        'data' => $records);
    $post_data=json_encode($post_data);
    _log("_send_to_cloud data".$post_data);
//    $post_data = $records;
//    print_r($post_data);die;
//    $token = _validate_cloud_token(KP_CLOUD,KP_USERNAME,KP_PSWD,KP_TOKEN);
    $token = KP_TOKEN;
    $last_id_sync = $records[0]['id'];

    $header = array(
        "x-api-key:$token",
        "Content-Type:application/json",
        "Accept:application/json"
    );

    #update token to ini file
    $ini_array = parse_ini_file("config.ini",true);
    $ini_array["sync_to_cloud"]["KP_TOKEN"]=$token;
//    put_ini_file($ini_array,'cron/config.ini');




    //TODO, we can use another http client to send data
    $curl = curl_init();
    $url = $config['url'];
//    print_r($url);die;
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,FALSE);
    curl_setopt($curl, CURLOPT_POST, TRUE);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
//    curl_setopt($curl, CURLOPT_HEADER, $headers);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);

//    var_dump($curl);die;
    $data = curl_exec($curl);
    if (curl_errno($curl)) {
        _log("_send_to_cloud get error:".curl_error($curl));
        curl_close($curl);
        return false;
    }
    curl_close($curl);
    // TODO, modify this check accroding to real server response
    _log($data);
    $response = json_decode($data,true);
    if ($response['status']=='success') {
        $fp = fopen('last_sync_cloud_id.txt', 'w');
        fwrite($fp, $last_id_sync);
        fclose($fp);
        return true;
    }
    return false;
}

function put_ini_file($config, $file, $has_section = false, $write_to_file = true){
    $fileContent = '';
    if(!empty($config)){
        foreach($config as $i=>$v){
            if($has_section){
                $fileContent .= "[".$i."]\n\r" . put_ini_file($v, $file, false, false);
            }
            else{
                if(is_array($v)){
                    foreach($v as $t=>$m){
                        $fileContent .= $i."[".$t."] = ".(is_numeric($m) ? $m : '"'.$m.'"') . "\n\r";
                    }
                }
                else $fileContent .= $i . " = " . (is_numeric($v) ? $v : '"'.$v.'"') . "\n\r";
            }
        }
    }

    if($write_to_file && strlen($fileContent)) return file_put_contents($file, $fileContent, LOCK_EX);
    else return $fileContent;
}


function _validate_cloud_token($token_url,$username,$pswd,$token){
    $curl = curl_init();
    $url = LPRLA_URL . "/api/get_cloud_token";
//    print_r($url);die;
    $post_data = array(
                        'token_url' => $token_url,
                        'username' => $username,
                        'pswd' => $pswd,
                        'token' => $token
                      );

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,FALSE);
    curl_setopt($curl, CURLOPT_POST, TRUE);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($curl, CURLOPT_HEADER, FALSE);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    $data = curl_exec($curl);
    if (curl_errno($curl)) {
//        _log("_send_to_cloud get error:".curl_error($curl));
        curl_close($curl);
        return false;
    }
    curl_close($curl);

    $response = json_decode($data,true);
    $token=$response["token"];
    return $token;
}

function _sync_records_looper($local_db,$cloud_config) {
    while (1) {
        $records = _get_unsynced_records($local_db,20);
//        print_r($records);die;
//        echo "here";die;
        if ($records===false) {
            // database disconnected
            break;
        }
        $count = count($records);
        if ($count>0) {
            _log("_sync_records_looper,count={$count}");
            for($a=0;$a<count($records);$a++){
                $records[$a]["kp_ticket_id"]=SITE_ID.$records[$a]["parking_type"].$records[$a]["ticket_id"];
            }
            while (1) {
                // cloud may be not availiable, need to retry here

                if (_send_to_cloud($cloud_config,$records)) {
//                    echo "herex";die;
                    _update_records_sync_status($local_db,$records,SYNC_STATUS_DONE);
                    break;
                }
                usleep(CLOUD_NOT_AVAILIABLE_WAIT);
            }
        }
        unset($records);
        usleep(DATABASE_SYNC_NEXT_WAIT);
    }
}

/**
 * sync to cloud
 */
function sync_cloud_looper($dummy) {
    // make sure only one instance is running at the same time
    $runner = new Runner(__METHOD__.'runner');
    $is_exist = $runner->exists();
    if ($is_exist) {
	    _log('runner check exist.');
        return;
    }
    $runner->autoClean();
    // TODO, modify this config
    $local_database_config = array(
        'host' => DB_HOST,
        'username' => DB_USER,
        'password' => DB_PSWD,
        'name' => DB_NAME,
    );
    // TODO, modify this config
    $cloud_server_config = array(
        'url' => KP_CLOUD.'/api/lpr/transaction_logs?site_id='.SITE_ID.'',
        'parking_site_id' => SITE_ID,
    );
    while (1) {        
        // connec to local database first
        $local_db = _connect_to_db($local_database_config);
        if ($local_db==false) {
            // wait some time to retry again
            usleep(DATABASE_RETRY_WAIT);
            continue;
        }
        _sync_records_looper($local_db,$cloud_server_config);
        $local_db->close();
    }
}

function main() {
    date_default_timezone_set("Asia/Kuala_Lumpur");
    error_reporting(1);
    sync_cloud_looper(1);
}

main();
