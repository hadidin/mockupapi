<?php

/**
 * send response to the tcp client
 */
function _send_response($serv,$fd,$name,$response) {
    $xml_string = _format_as_xml($name,$response);
    _log("response to client {$fd},{$xml_string}");
    $start_msg = STX;
    $final_msg = chr(0x02).$xml_string.chr(0x03);

    $end_msg = ETX;
    $serv->send($fd,$final_msg);
    return true;
}

/**
 * S&B request main router  function
 * @param $serv : the swoole server
 * @param $fd : the tcp client id
 * @param $name : the xml root node name
 * @param $request : the xml sub nodes name/value in array
 */
function _process_sb_request($serv,$fd,$name,$request) {
    $func_name = '_process_'.$name;
    if (function_exists($func_name)) {
        return $func_name($serv,$fd,$name,$request);
    }
    _log("no process function:$func_name.");
    return false;
}

/**
 * send FaultEMV to tcp client
 */
function _response_with_FaultEMV($serv,$fd,$request) {
    $response = array();
    $code = '999';
    $response['ResponseStatus'] = 'ERROR';
    $response['ResponseCode'] = $code;
    $response['ResponseTextMessage'] = _get_code_message($code);
    return _send_response($serv,$fd,'FaultEMV',$response);
}

/**
 * process InitializationEMV request
 */
function _process_InitializationEMV($serv,$fd,$name,$request) {
    $response = _create_response($request);
    _fill_response_with_status($response,'INITIALIZED',SB_STATUS_INITIALIZED);
    return _send_response($serv,$fd,$name,$response);
}

/**
 * process ServiceEMV request
 */
function _process_ServiceEMV($serv,$fd,$name,$request) {
    $response = _create_response($request);
    $command = $request['Command'];
    if ($command=='Activate') {
        // TODO
    } else if ($command=="Deactivate") {
        // TODO
    } else {
        // TODO
    }
    _fill_response_with_status($response,'EXECUTED',SB_STATUS_COMMAND_SUCCESSFUL);
    return _send_response($serv,$fd,$name,$response);
}

/**
 * process TerminalStatusEMV request
 */
function _process_TerminalStatusEMV($serv,$fd,$name,$request) {
    $response = _create_response($request);
    _fill_response_with_status($response,'STATUS',SB_STATUS_IDLE);
    return _send_response($serv,$fd,$name,$response);
}

/**
 * process ServerStatusEMV request
 */
function _process_ServerStatusEMV($serv,$fd,$request) {
    $response = _create_response($request);
    _fill_response_with_status($response,'OK',SB_STATUS_SERVER_ONLINE);
    return _send_response($serv,$fd,$name,$response);
}

/**
 * send TerminalStatusEMV to tcp client
 */
function _response_with_TerminalStatusEMV($serv,$fd,$request,$code) {
    $response = _create_response($request);
    $response['ResponseStatus'] = 'STATUS';
    $response['ResponseCode'] = $code;
    $response['ResponseTextMessage'] = _get_code_message($code);
    return _send_response($serv,$fd,'TerminalStatusEMV',$response);
}
/**
 * send CardDataEMV to tcp client
 */
function _response_with_CardDataEMV($serv,$fd,$request,$hashed_span) {
    $response = _create_response($request);
    print_r($response);
    _fill_response_with_card_info($response,null,$hashed_span);

    #get ticket id from local psm


    print_r($response);
    /* TODO, according to the doc, the following fields need to reply
       so we need to save the drive in data in DB
        <DriveInDate>170220</DriveInDate>
        <DriveInTime>145423</DriveInTime>
        <DriveInTimeOffset>UTC+01</DriveInTimeOffset>
        <DriveInZRNumber>2010</DriveInZRNumber>
        <DriveInDeviceNumber>101</DriveInDeviceNumber>
        <DriveInDeviceType>1</DriveInDeviceType>
    */
    return _send_response($serv,$fd,'CardDataEMV',$response);
}

/**
 * process TransactionEMV request
 */
function _process_TransactionEMV($serv,$fd,$name,$request){
    $authorization_type = $request['AuthorizationType'];
    _log("_process_TransactionEMV, authorization_type=${authorization_type}");
    if ($authorization_type=='Identification') {
        $device_type = $request['DeviceType'];
        _log("_process_TransactionEMV, device_type=${device_type}");
        if ($device_type==SB_DEVICE_TYPE_ENTRY) {
            // car drive in
            // 0. check with cloud, this is a kiplePark user or not
            #sleep(1);
            $terminal_id = $request['TerminalID'];
            $purchase_date = $request['PurchaseDate'];
            $purchase_time = $request['PurchaseTime'];
            $purchase_time_offset = $request['PurchaseTimeOffset'];
            $kiple_user=_get_entry_log($terminal_id,$purchase_date,$purchase_time,$purchase_time_offset,$device_type);
            $kiple_user_flag = $kiple_user['valid_kp_user'];

            // if season parking return cancelled user
            $check_result = $kiple_user['check_result'];

            _log('kiplepark binded user info'.json_encode($kiple_user));

            //no need to response if not binded kiplepark user
            if ($kiple_user_flag==false) {
                // do not response since this is not a kiplePark user
                _log("_process_TransactionEMV, stop here to snb thread. because it not binded kiplepark user");
                return false;
            }


            #$visitor_whitelist = in_autodeduct_white_list($kiple_user['entry_log_info']['kp_user_id']);
			$visitor_whitelist = _check_auto_deduct_whitelist_from_db($kiple_user['entry_log_info']['kp_user_id']);
            if ($visitor_whitelist==false) {
                // do not response since this is not a whitelist user for auto deduct
                _log("_process_TransactionEMV, stop here to snb thread. because user is not in auto deduct whitelist");
                return false;
            }

            //check if repeated entry then return false
            $check_repeated_entry = _get_kp_trx_id($kiple_user['entry_log_info']['plate_no']);
            if($check_repeated_entry){
                _log("_process_TransactionEMV, reject because repeated entry");
                _response_with_TransactionEMV_Cancel_user($serv,$fd,$request,201);
                //LS019 - repeated entry. DUPLICATE PLATE
                _update_check_result($kiple_user['id'],19);
                return false;
            }


            // generate hashedspan aka kiplebox ticket id
            $merchant_trx_id = $request['MerchantTransactionID'];
            $kp_ticket_id = _generate_kp_trx_id($kiple_user['entry_log_info']['id'],$merchant_trx_id);

//            no need to response for season pass user
            if(empty($kp_ticket_id)){
                _log("_process_TransactionEMV, generate ticket id failed");
                // 4=reject on identifiction because season pass user
                _response_with_TransactionEMV_Cancel_user($serv,$fd,$request,201);

                //LS018 - DENIED REFER OPERATOR
                _update_check_result($kiple_user['entry_log_info']['id'],18);
                return false;
            }


            // 1. response with card insert status to block others
            _response_with_TerminalStatusEMV($serv,$fd,$request,SB_STATUS_CARD_INSERT);
            #sleep(1);

//            // 2a. if check result is duplicate entry reply cancelled user to block the user
//            if ($kiple_user['entry_log_info']['check_result'] == '4') {
//                // 3=accept by vendor and reject on remove card by ours ex repeated_entry
//                _update_vendor_check_result($kiple_user['id'],3);
//                return _response_with_TransactionEMV_Block($serv,$fd,$request,SB_STATUS_APPROVAL_REJECT_TRX);
//            }


            // cache the ticket data for RemoveCardEMV case
            _term_cache_set($terminal_id,'kp_ticket_id',$kp_ticket_id);
            _term_cache_set($terminal_id,'psm_entry_log_id',$kiple_user['entry_log_info']['id']);

            // 2b. response the ticket
            return _response_with_CardDataEMV($serv,$fd,$request,$kp_ticket_id);
        }
        if ($device_type==SB_DEVICE_TYPE_EXIT) {
            // car drive out
            // TODO 1. get plate_number from local database
            // 2. find the entry log
            #sleep(1);
            $terminal_id = $request['TerminalID'];
            $purchase_date = $request['PurchaseDate'];
            $purchase_time = $request['PurchaseTime'];
            $purchase_time_offset = $request['PurchaseTimeOffset'];

            // get car info from entry log
            $kiple_user=_get_entry_log($terminal_id,$purchase_date,$purchase_time,$purchase_time_offset,$device_type);
            $kiple_user_flag = $kiple_user['valid_kp_user'];

            _log("kiple_user data =".json_encode($kiple_user));

            #if season parking return cancelled user
            #if season parking return cancelled user
            $check_result = $kiple_user['check_result'];
            print_r($kiple_user);


            #get hashedspan aka kiplebox ticket id
            $kp_ticket_id = _get_kp_trx_id($kiple_user['entry_log_info']['plate_no']);

//            if($check_result == 6 || $check_result == 9){
//                #4=reject on identifiction because season pass user
//                _update_vendor_check_result($kiple_user['id'],4);
//                return _response_with_TransactionEMV_Cancel_user($serv,$fd,$request,201);
//            }



            if ($kiple_user_flag==false) {
                // do not response since this is not a kiplePark user
                return false;
            }


            // 1. response with card insert status to block others
            _response_with_TerminalStatusEMV($serv,$fd,$request,SB_STATUS_CARD_INSERT);
            #sleep(1);

            // 2a. if check result is duplicate entry reply cancelled user to block the user
            if ($kiple_user['entry_log_info']['check_result'] == '4') {
                #3=accept by vendor and reject on remove card by ours ex repeated_entry
                _update_vendor_check_result($kiple_user['entry_log_info']['id'],3);
                return _response_with_TransactionEMV_Block($serv,$fd,$request,SB_STATUS_APPROVAL_REJECT_TRX);
            }
            // cache the ticket data for RemoveCardEMV case
            _term_cache_set($terminal_id,'kp_ticket_id',$kp_ticket_id);
            _term_cache_set($terminal_id,'psm_entry_log_id',$kiple_user['entry_log_info']['id']);
            _term_cache_set($terminal_id,'kp_user_id',$kiple_user['entry_log_info']['kp_user_id']);

            // 2b. response the ticket
            #return _response_with_CardDataEMV($serv,$fd,$request,KIPLE_TICKET_DEMO);
            return _response_with_CardDataEMV($serv,$fd,$request,$kp_ticket_id);

        }

    }
    return false;
}

/**
 * send TransactionEMV to tcp client
 */
function _response_with_TransactionEMV($serv,$fd,$request) {
    $response = _create_response($request);
    _fill_response_with_card_info($response,KIPLE_ACCOUNT_DEMO,KIPLE_TICKET_DEMO);
    _fill_response_with_status($response,'AUTHORIZED',SB_STATUS_APPROVAL);

    $response['HashedEpan'] = $request['kp_ticket_id'];

    $response['ApprovalCode'] = '11112345678901234567';
    $response['TransactionDate'] = date("ymd");
    $response['TransactionTime'] = date("His");
    $response['TransactionIdentifier'] = "0010040050";#webcash trx id
    $response['BatchID'] = '4';
    $response['CustomerReceipt'] = 'N/A';
    $response['MerchantReceipt'] = 'N/A';
    return _send_response($serv,$fd,'TransactionEMV',$response);
}

function _response_with_TransactionEMV_Declined($serv,$fd,$request) {
    $response = _create_response($request);
    _fill_response_with_card_info($response,KIPLE_ACCOUNT_DEMO,KIPLE_TICKET_DEMO);
    _fill_response_with_status($response,'REFUSED',SB_STATUS_DECLINED_PAYMENT);

    $response['HashedEpan'] = $request['kp_ticket_id'];
    return _send_response($serv,$fd,'TransactionEMV',$response);
}

/**
 * process AdditionalTransactionDataEMV request
 */

function _response_with_TransactionEMV_Block($serv,$fd,$request) {
    $response = _create_response($request);
    _fill_response_with_status($response,'CANCELED',SB_STATUS_APPROVAL_REJECT_TRX);
    $response['ResponseTextMessage'] = 'Transaction canceled by terminal user repeated entry';

    _send_response($serv,$fd,'TransactionEMV',$response);
    return false;
}

function _response_with_TransactionEMV_Cancel_user($serv,$fd,$request) {
    $response = _create_response($request);
    _fill_response_with_status($response,'CANCELED',201);
    #$response['ResponseTextMessage'] = 'Transaction canceled by kiplepark check result';

    _send_response($serv,$fd,'TransactionEMV',$response);
    return false;
}


function _process_AdditionalTransactionDataEMV($serv,$fd,$name,$request){
//    $authorization_type = $request['AuthorizationType'];
//    if ($authorization_type!='Sale') {
//        _log("_process_AdditionalTransactionDataEMV, authorization_type=${authorization_type} not supported");
//        return false;
//    }
    $terminal_id = $request['TerminalID'];
    $device_type = $request['DeviceType'];
    if ($device_type!=SB_DEVICE_TYPE_EXIT) {
        _log("_process_AdditionalTransactionDataEMV, device_type=${device_type} not supported");
        return false;
    }
    // response one status to update the timeout
    #sleep(1);
    _response_with_TerminalStatusEMV($serv,$fd,$request,SB_STATUS_AUTHORIZATION_APPROVED);
    #sleep(1);
    $transaction_amount = floatval($request['TransactionAmount']);
    #$surcharge_amount = floatval($request['SurchargeAmount']);

    //get ticket id from car_in_site_table
    $kp_ticket_id = _term_cache_get($terminal_id,'kp_ticket_id');
    $request['kp_ticket_id'] = $kp_ticket_id;

    //get psm_entry_log_id
    $psm_entry_log_id = _term_cache_get($terminal_id,'psm_entry_log_id');

    $total_amount = $transaction_amount;
    if ($total_amount<=0) {
        _response_with_TransactionEMV($serv,$fd,$request);

        //update car out
        _car_out($psm_entry_log_id,$kp_ticket_id);

        //update visitor_check_result
        _update_visitor_check_result($psm_entry_log_id,14,11);

        //update check result so we can start sync to the cloud
        _update_vendor_check_result($psm_entry_log_id,1);

        return false;
    }

    // auto deduct;
    #$user_id = _term_cache_get['user_id'];
    $user_id = _term_cache_get($terminal_id,'kp_user_id');
    $auto_deduct = _process_sent_auto_deduct($user_id,$kp_ticket_id,$request,$total_amount);
    #$auto_deduct = false;
    // if payment success only _response_with_TransactionEMV to snb
    if($auto_deduct == true){
        _response_with_TransactionEMV($serv,$fd,$request);

        //update car out
        _car_out($psm_entry_log_id,$kp_ticket_id);

        //update visitor_check_result
        _update_visitor_check_result($psm_entry_log_id,14,11);

        //update check result so we can start sync to the cloud
        _update_vendor_check_result($psm_entry_log_id,1);

        return false;
    }
    else{
        _response_with_TransactionEMV_Declined($serv,$fd,$request);
        return false;
    }

//    return _response_with_TransactionEMV($serv,$fd,$request);
}

/**
 * process RemoveCardEMV request
 */
function _process_RemoveCardEMV($serv,$fd,$name,$request){
    // this sleep is for simulator;
    #sleep(1);

    _log("_process_RemoveCardEMV : ".$request);

    $terminal_id = $request['TerminalID'];
    $device_type = $request['DeviceType'];
    $response_status = $request['ResponseStatus'];

    $response = _create_response($request);
    _fill_response_with_status($response,'CANCELED',SB_STATUS_CANCELED);

    // get the entry log id and ticket id by terminal id
    $psm_entry_log_id = _term_cache_get($terminal_id,'psm_entry_log_id');
    $kp_ticket_id = _term_cache_get($terminal_id,'kp_ticket_id');

    _log("_term_cache_get psm_entry_log_id: ".$psm_entry_log_id);
    _log("_term_cache_get ticket_id: ".$kp_ticket_id);
    

    $response['HashedEpan'] = $kp_ticket_id;
    $result  = _send_response($serv,$fd,TransactionEMV,$response);

    if ($response_status=='ACCEPTED') {
        if ($device_type == SB_DEVICE_TYPE_EXIT) {
            _car_out($psm_entry_log_id,$kp_ticket_id);

            //update visitor_check_result
            _update_visitor_check_result($psm_entry_log_id,14,11);

        }
        if ($device_type == SB_DEVICE_TYPE_ENTRY) {
            //update visitor_check_result
            _update_visitor_check_result($psm_entry_log_id,1,10);
        }
        _update_vendor_check_result($psm_entry_log_id,1);
    }   
    if ($response_status=='REFUSED') {
        // need to remove card as vendor reject
        if ($device_type == SB_DEVICE_TYPE_ENTRY) {
            $kp_ticket_id = remove_ticket_id($kp_ticket_id);
        }
        _update_vendor_check_result($psm_entry_log_id,2);
    }
    _log("_process_RemoveCardEMV, response_status=${response_status}, device_type={$device_type} not supported");
    return $result;
}

function _process_sent_auto_deduct($user_id,$kp_ticket_id,$request,$amount){

    $header = array(
        "Content-Type:application/json",
        "Accept:application/json"
    );

    $post_data = array(
        'ticket_id' => $kp_ticket_id,
        'user_id' => $user_id,
        'amount' => $amount*100
    );
    $post_data=json_encode($post_data);

    //TODO, we can use another http client to send data
    $ini_array = parse_ini_file("../cron/config.ini",true);
    $lprla_url = $ini_array["common"]["LPRLA_URL"];
    $curl = curl_init();
    $url = $lprla_url.'/api/payment/snb_payment';
//    print_r($url);die;

    _log("_send_to_cloud to url:".$url);
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
    _log('Autodeduct result'.$data);
    $response = json_decode($data,true);

    //needed to modify response here
    if ($response['success']==true) {
        return true;
    }
    return false;
}


/**
 * process TerminalStatusEMV request
 */
function _process_TransactionCancelEMV($serv,$fd,$name,$request) {
    $response = _create_response($request);
    _fill_response_with_status($response,'CANCELED',SB_STATUS_CANCELED_ACCEPTED);
    _send_response($serv,$fd,$name,$response);
    _fill_response_with_status($response,'CANCELED',SB_STATUS_CANCELED_BY_MERCHANT);
    return _send_response($serv,$fd,'TransactionEMV',$response);
}


$_g_term_cache = array( );

function _term_cache_set($term,$key,$value) {
    global $_g_term_cache;

    if (empty($_g_term_cache[$term])) {
        $_g_term_cache[$term] = array();
    }
    $_g_term_cache[$term][$key] = $value;
}

function _term_cache_get($term,$key) {
    global $_g_term_cache;
    
    if (empty($_g_term_cache[$term])) {
        return null;
    }
    return $_g_term_cache[$term][$key];
}

function in_autodeduct_white_list($user_id) {
    $white_list = array(
        'risbergbexel@gmail.com' => 1,
        'khaleghi.meisam@gmail.com' => 1,
        'eelarmns@gmail.com' => 1,
        'hadi@grr.la' => 1,
        'tommy.hou@kiplepay.com' => 1,
        'mkha24@yahoo.com' => 1,
        'angfwuyang@gmail.com' => 1
    );
    if (isset($white_list[$user_id]) && $white_list[$user_id]==1) {
        return true;
    }
    return false;
}

