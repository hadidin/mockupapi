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



$ini_array = parse_ini_file("config.ini",true);

$SYNC_STATUS_NORMAL=$ini_array['sync_db']['SYNC_STATUS_NORMAL'];
$SYNC_STATUS_DONE=$ini_array['sync_db']['SYNC_STATUS_DONE'];
$SYNC_STATUS_FAILED=$ini_array['sync_db']['SYNC_STATUS_FAILED'];

$DB_TABLE=$ini_array['sync_db']['DB_TABLE'];


$MSDB_HOST=$ini_array['sync_db']['MSDB_HOST'];
$MSDB_USER=$ini_array['sync_db']['MSDB_USER'];
$MSDB_PSWD=$ini_array['sync_db']['MSDB_PSWD'];
$MSDB_NAME=$ini_array['sync_db']['MSDB_NAME'];

$SITE_ID=$ini_array['common']['SITE_ID'];
$DB_NAME=$ini_array['common']['DB_NAME'];
$DB_HOST=$ini_array['common']['DB_HOST'];
$DB_USER=$ini_array['common']['DB_USER'];
$DB_PSWD=$ini_array['common']['DB_PSWD'];
$KP_CLOUD=$ini_array['common']['KP_CLOUD'];

define ('SYNC_STATUS_NORMAL', $SYNC_STATUS_NORMAL);
define ('SYNC_STATUS_DONE', $SYNC_STATUS_DONE);
define ('SYNC_STATUS_FAILED', $SYNC_STATUS_FAILED);

define ('DB_TABLE', $DB_TABLE);

define ('MSDB_HOST', $MSDB_HOST);
define ('MSDB_USER', $MSDB_USER);
define ('MSDB_PSWD', $MSDB_PSWD);
define ('MSDB_NAME', $MSDB_NAME);

define ('SITE_ID' , $SITE_ID);
define ('DB_NAME' , $DB_NAME);
define ('DB_HOST' , $DB_HOST);
define ('DB_USER' , $DB_USER);
define ('DB_PSWD' , $DB_PSWD);
define ('KP_CLOUD' , $KP_CLOUD);



function _log($str) {
    $log_file_name = dirname(__FILE__) . DIRECTORY_SEPARATOR."sync_db.log";
	$info = date("Y-m-d H:i:s") . "|" . $str . "\n";
	print($info);
	file_put_contents($log_file_name, $info, FILE_APPEND);
}

/**
 * sync remote records and save to local database
 */
function _sync_remote_records($remote_cfg,$last_sync_time,$local_db,$local_table_name) {
    // build sql server paramters
    $server_name = $remote_cfg['host'];
    $connection_info = array(
        "UID" => $remote_cfg['username'], 
        "PWD" => $remote_cfg['password'], 
        "Database"=>$remote_cfg['database'],
        'ReturnDatesAsStrings'=>true
    ); 
    _log("start to sync with remote server:$server_name");
    // connect to sql server
    $conn = sqlsrv_connect($server_name,$connection_info); 
    if ($conn === false ) {
        $errs = json_encode(sqlsrv_errors());
        _log("connect to remote failed:$errs");
        return false;
    }
    // due to the SQLServer date time has mill-seconds, but MySQL doesn't have (2019-04-01 12:12:12 765 => 2019-04-01 12:12:13)
    // so we just sub 1 seconds the last sync time, and sync
    $time = strtotime($last_sync_time);
    $time = $time-1;
    $last_sync_time =  date('Y-m-d H:i:s',$time);
    // build the stored procedure sql statement
    $sql_sp = "begin declare @return_value int; exec @return_value = dbo.spKiple_GetSeasonParkerListByLatestDate";
    if ($last_sync_time) {
        $sql_sp .= " @last_sync_time = '$last_sync_time'; end";
    } else {
        $sql_sp .= "; end";
    }
    _log("stored procedure is :$sql_sp");
    // call the stored procedure
    $stmt = sqlsrv_query($conn,$sql_sp);
 
    if($stmt === false){
        $errs = json_encode(sqlsrv_errors());
        _log("execute stored procedure failed:$errs");
        sqlsrv_close($conn);
        return false;
    }
    $sync_count = 0;            
    while($row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC)){
        $customer_id = $row['uCustomerID'];
        $season_card_no = $row['season_card_no'];
        $user_name = addslashes($row['user_name']);
        $plate_no1 = $row['VehicleNo1'];
        $plate_no1 = str_replace(" ","",$plate_no1);
        $plate_no2 = $row['VehicleNo2'];
        $plate_no2 = str_replace(" ","",$plate_no2);
        $plate_no3 = $row['VehicleNo3'];
        $plate_no3 = str_replace(" ","",$plate_no3);
        $valid_from = $row['valid_from'];
        $valid_until = $row['valid_until'];
        $vip = $row['VIP'];
        $active_flag = $row['active_flag'];
	$delete_flag = $row['delete_flag'];
	$bill_to_company = $row['Bill_To_Company'];
	$access_category = $row['AccessCategory'];
        $last_modified_time = $row['last_modified_time'];
        // build the replace sql statement 
        $sql_rp = "REPLACE INTO `{$local_table_name}` SET `card_id`='$season_card_no'";
        $sql_rp .= ",`customer_id`='$customer_id',`user_name`='$user_name'";
        $sql_rp .= ",`plate_no1`='$plate_no1',`plate_no2`='$plate_no2',`plate_no3`='$plate_no3'";
        $sql_rp .= ",`valid_from`='$valid_from',`valid_until`='$valid_until'";
        $sql_rp .= ",`vip`=$vip,`active_flag`=$active_flag,`delete_flag`=$delete_flag";
	$sql_rp .= ",`updated_at`='$last_modified_time'";
	$sql_rp .= ",`access_category`='$access_category'";
	$sql_rp .= ",`bill_to_company`='$bill_to_company'";

        // update to local database
        $rp_result = $local_db->query($sql_rp);
        if (!$rp_result) {
            _log("Write to lcoal database failed:{$sql_rp}, reason:".$local_db->error);
            break;
        }
        usleep(1);
        $sync_count = $sync_count+1;
    }
    _log("sync total count:{$sync_count}");
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
    return true;
}

/**
 * get the last modified time from the local database
 */
function _get_local_last_modified($db,$table) {
    $sql_query = "SELECT `id`,`updated_at` FROM `{$table}` ORDER BY `updated_at` DESC LIMIT 1";
    $query_result = $db->query($sql_query);
    $row = null;
    if ($query_result) {
        $row = $query_result->fetch_assoc();
        $query_result->free();
    }
    if($row && $row['updated_at']){
        return $row['updated_at'];
    }
    return null;
}

/**
 * sync database
 */
function sync_db() {
    // make sure only one instance is running at the same time
    $runner = new Runner(__METHOD__.'runner');
    $is_exist = $runner->exists();
    if ($is_exist) {
	    _log('runner check exist.');
        return;
    }
    $runner->autoClean();

    // remote SQL server config parmaters
    $remote_server_cfg = array (
        "host" => MSDB_HOST,
        "username" => MSDB_USER,
        "password" => MSDB_PSWD,
        "database" => MSDB_NAME
    );

    // local MySQL server config parmaters
    $local_server_cfg = array (
        "host" => DB_HOST,
        "username" => DB_USER,
        "password" => DB_PSWD,
        "database" => DB_NAME,
        "table" => DB_TABLE // sync table name
    );

    // connect to local database first
    _log("start to connect local database server:{$local_server_cfg['host']}");
    $local_db = mysqli_init();
    $ret = $local_db->real_connect($local_server_cfg['host'],$local_server_cfg['username'],$local_server_cfg[password],$local_server_cfg['database']);        
    if ($ret == false) {
        _log("local database connect failed:".$local_db->error);
        return false;
    }
    $local_db->set_charset('utf8');
    // get last sync time from local database
    $last_sync_time = _get_local_last_modified($local_db,$local_server_cfg['table']);
    // sync with remote server
    $result = _sync_remote_records($remote_server_cfg,$last_sync_time,$local_db,$local_server_cfg['table']);
    // close the local databse connection
    $local_db->close();
    return $result;
}

function main() {
    date_default_timezone_set("Asia/Kuala_Lumpur");
    error_reporting(1); 
    sync_db();
}

//phpinfo();


main();
