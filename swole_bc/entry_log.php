<?php

/**
 * get entry log from database table
 */
function _get_entry_log($terminal_id,$purchase_date,$purchase_time,$purchase_time_offset,$device_type){
    $conn = null;
    $car_info = array(
        'valid_kp_user' => false,
        'entry_log_info' => null,
        'check_result' => null,
    );
    do {
        // connect to mysql
        $conn=@mysqli_connect(DB_HOST,DB_USER,DB_PSWD,DB_NAME);
        if (@mysqli_connect_errno()) {
            _log("_get_entry_log, connect mysql failed : " . mysqli_connect_error());
            break;
        }
        // query camera
        $sql_query="SELECT camera_sn from psm_lane_config where ext_cam_ref_id = '$terminal_id'";
        $query_result = @mysqli_query($conn,$sql_query);
        if ($query_result==false) {
            _log("_get_entry_log, query camera failed : " . mysqli_connect_error());
            break;
        }
        $data = $query_result->fetch_assoc();
        $camera_sn = $data['camera_sn'];
        @mysqli_free_result($query_result);
        
        // query entry log 
        $date_now = date("Y-m-d H:i:s");
        $time_to_check_minus=date("Y-m-d H:i:s", (strtotime(date($date_now)) - 1));
        $time_to_check_plus=date("Y-m-d H:i:s", (strtotime(date($date_now)) + 5));

        $check_result = SB_CHECK_RESULT_TO_REPLY;
    
        $sql_query="SELECT a.id,a.plate_no,a.camera_sn,a.kp_user_id,a.check_result 
            FROM psm_entry_log a
            WHERE a.camera_sn='$camera_sn' AND a.check_result IN($check_result)
            AND a.create_time between '$time_to_check_minus' and '$time_to_check_plus' and a.vendor_check_result=0 limit 1";
        
        _log("_get_entry_log, query entry log : " . $sql_query);
        #_log("_db access " . DB_HOST.":".DB_USER.":".DB_PSWD.":".DB_NAME);

        for ($i=0;$i<SB_IDENTIFICATION_TIMER;$i++) {
            $query_result = @mysqli_query($conn,$sql_query);
            #_log("_get_entry_log, query entry log : " . json_encode($query_result));
            if ($query_result) {
                $row = $query_result->fetch_assoc();
                @mysqli_free_result($query_result);
                if(count($row) > 0){
                    $car_info['check_result'] = $row['check_result'];
                    $car_info['entry_log_info'] = $row;
                    $car_info['valid_kp_user'] = empty($row['kp_user_id'])?false:true;
                    break;
                }
            }
            usleep(50000);
        }
    } while (0);
    // close my connection
    if ($conn) {
        $conn->close();
    }
    return $car_info;
}

function _generate_kp_trx_id($entry_log_id,$merchant_trx_id){
    $conn = null;
    $last_id = null;
    do  {
        $conn=mysqli_connect(DB_HOST,DB_USER,DB_PSWD,DB_NAME);
        if (@mysqli_connect_errno()) {
            _log("_generate_kp_trx_id, connect mysql failed : " . mysqli_connect_error());
            break;
        }
        $sql_query="SELECT id,plate_no,kp_user_id,kp_locked_flag, kp_auto_deduct_flag from psm_entry_log where id=$entry_log_id";
        $query_result = @mysqli_query($conn,$sql_query);
        if ($query_result==false) {
            _log("_generate_kp_trx_id, query : $sql_query, failed :" . mysqli_error($conn) );
            break;
        }
        $row = $query_result->fetch_assoc();
        @mysqli_free_result($query_result);

        $datenow=date("Y-m-d H:i:s");
        $sql = "INSERT INTO psm_car_in_site 
                SET entry_id=$row[id], parking_type=0, plate_no='$row[plate_no]', user_id='$row[kp_user_id]', locked_flag=$row[kp_locked_flag], auto_deduct_flag=$row[kp_auto_deduct_flag],
                 is_left=0, created_at='$datenow', vendor_ticket_id='$merchant_trx_id'";

        if (@mysqli_query($conn, $sql)==false) {
            _log("_generate_kp_trx_id, insert : $sql, failed :" . mysqli_error($conn) );
            break;
        }
        $last_id = @mysqli_insert_id($conn);
    } while(0);
    // close my connection
    if ($conn) {
        $conn->close();
    }
    return $last_id;
}

function _get_kp_trx_id($plate_no){
    $conn=mysqli_connect(DB_HOST,DB_USER,DB_PSWD,DB_NAME);

    $sql_query="SELECT id from psm_car_in_site where plate_no='$plate_no' and is_left=0 limit 1";
    _log("Query to get ticket id = ".$sql_query);
    $query_result = mysqli_query($conn,$sql_query);
    $row = $query_result->fetch_assoc();
    //print_r($row);
    $conn->close();

    return $row['id'];
}


function _car_out($entry_log_id,$ticket_id){

    // open mysql connection
    $conn=mysqli_connect(DB_HOST,DB_USER,DB_PSWD,DB_NAME);
    $date_now=date('Y-m-d H:i:s');
    $sql_query2="update psm_car_in_site set is_left=1,exit_id=$entry_log_id, updated_at='$date_now' where id= $ticket_id and is_left=0";
    mysqli_query($conn,$sql_query2);
    $conn->close();
    return true;
}

function _update_visitor_check_result($psm_entry_log_id,$visitor_check_result,$check_result){
    // open mysql connection
    $conn=mysqli_connect(DB_HOST,DB_USER,DB_PSWD,DB_NAME);
    $date_now=date('Y-m-d H:i:s');
    if($visitor_check_result == 1){

    }
    $sql_query2="update psm_entry_log set visitor_check_result=$visitor_check_result, check_result=$check_result where id= $psm_entry_log_id";
    print($sql_query2);
    mysqli_query($conn,$sql_query2);
    $conn->close();
    return true;

}


function remove_ticket_id($ticket_id){
    $conn=mysqli_connect(DB_HOST,DB_USER,DB_PSWD,DB_NAME);
    $sql_query="delete from psm_car_in_site where id = $ticket_id and is_left=0";
    $query_result = mysqli_query($conn,$sql_query);
    $conn->close();

}


function _update_vendor_check_result($id,$status){
    $conn = mysqli_connect(DB_HOST,DB_USER,DB_PSWD,DB_NAME);
    #update vendor check result
    mysqli_query($conn,"UPDATE psm_entry_log set vendor_check_result = $status where id = $id");
    $conn->close();
}

function _update_check_result($id,$status){
    $conn = mysqli_connect(DB_HOST,DB_USER,DB_PSWD,DB_NAME);
    #update vendor check result
    mysqli_query($conn,"UPDATE psm_entry_log set check_result = $status where id = $id");
    $conn->close();
}

function _check_auto_deduct_whitelist_from_db($user_id) {
    $conn=mysqli_connect(DB_HOST,DB_USER,DB_PSWD,DB_NAME);
    $sql_query="SELECT count(*) as valid from psm_autodeduct_whitelist where email='$user_id' and enable_flag=1 order by id desc limit 1";
    #_log("Query to get psm_autodeduct_whitelist id = ".$sql_query);
    $query_result = mysqli_query($conn,$sql_query);
    $row = $query_result->fetch_assoc();
    #_log("checking whitelist result ".json_encode($row));
    mysqli_free_result($query_result);
    $conn->close();

    if($row['valid'] > 0){
		_log("is in whitelist ".$user_id);
        return true;
    }
	_log("not in whitelist ".$user_id);
    return false;
}





