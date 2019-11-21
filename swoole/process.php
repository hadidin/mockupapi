<?php

/**
 * send response to the tcp client
 */
function _send_response($serv,$fd,$name,$response) {
    $xml_string = _format_as_xml($name,$response);
    _log("response to client {$fd},{$xml_string}");
    $serv->send($fd,$xml_string);
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
    _fill_response_with_card_info($response,null,$hashed_span);
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
            $kiple_user = true;

            if ($kiple_user==false) {
                // do not response since this is not a kiplePark user
                return false;
            }

            // 1. response with card insert status to block others
            _response_with_TerminalStatusEMV($serv,$fd,$request,SB_STATUS_CARD_INSERT);
            sleep(3);
            // 2. response the ticket
            return _response_with_CardDataEMV($serv,$fd,$request,KIPLE_TICKET_DEMO);
        }
        if ($device_type==SB_DEVICE_TYPE_EXIT) {
            // car drive out
            $purchase_date = $request['PurchaseDate'];
            $purchase_time = $request['PurchaseTime'];
            $purchase_timeoffset = $request['PurchaseTimeOffset'];
            // TODO 1. get plate_number from local database
            // 2. find the entry log
            $has_entry = true;
            if ($has_entry) {
                _response_with_TerminalStatusEMV($serv,$fd,$request,SB_STATUS_CARD_INSERT);
                // sleep 3 seconds to simulate the operation in sequence
                sleep(3);
                return _response_with_CardDataEMV($serv,$fd,$request,KIPLE_TICKET_DEMO);
            }
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
    $response['ApprovalCode'] = '11112345678901234567';
    $response['TransactionDate'] = date("ymd");
    $response['TransactionTime'] = date("His");
    $response['TransactionIdentifier'] = "12345678901234567890";
    $response['BatchID'] = '123456';
    $response['CustomerReceipt'] = 'N/A';
    $response['MerchantReceipt'] = 'N/A';
    return _send_response($serv,$fd,'TransactionEMV',$response);
}

/**
 * process AdditionalTransactionDataEMV request
 */
function _process_AdditionalTransactionDataEMV($serv,$fd,$name,$request){
    $authorization_type = $request['AuthorizationType'];
    if ($authorization_type!='Sale') {
        _log("_process_AdditionalTransactionDataEMV, authorization_type=${authorization_type} not supported");
        return false;
    }
    $device_type = $request['DeviceType'];
    if ($device_type!=SB_DEVICE_TYPE_EXIT) {
        _log("_process_AdditionalTransactionDataEMV, authorization_type=${authorization_type} not supported");
        return false;
    }
    // response one status to update the timeout
    _response_with_TerminalStatusEMV($serv,$fd,$request,SB_STATUS_AUTHORIZATION_APPROVED);
    $transaction_amount = floatval($request['TransactionAmount']);
    $surcharge_amount = floatval($request['SurchargeAmount']);
    $total_amount = $transaction_amount+$surcharge_amount;
    if ($total_amount<=0) {
        return _response_with_TransactionEMV($serv,$fd,$request);
    }
    // TODO,auto deduct;
    return _response_with_TransactionEMV($serv,$fd,$request);
}

/**
 * process RemoveCardEMV request
 */
function _process_RemoveCardEMV($serv,$fd,$name,$request){
    _response_with_TerminalStatusEMV($serv,$fd,$request,SB_STATUS_CARD_REMOVED);
    $response = _create_response($request);
    _fill_response_with_card_info($response,KIPLE_ACCOUNT_DEMO,KIPLE_TICKET_DEMO);
    _fill_response_with_status($response,'CANCELED',SB_STATUS_CANCELED);



    $response="<TransactionEMV>
<MerchantTransactionID>16076</MerchantTransactionID>
<ZRNumber>2055</ZRNumber>
<DeviceNumber>205</DeviceNumber>
<DeviceType>2</DeviceType>
<TerminalID>Term01</TerminalID>
<ResponseStatus>CANCELED</ResponseStatus>
<ResponseCode>203</ResponseCode>
<ResponseTextMessage>Transaction canceled after removvvce card</ResponseTextMessage>
</TransactionEMV>";

    $response_status = $request['ResponseStatus'];
    if ($response_status=='ACCEPTED') {
        return _send_response($serv,$fd,$name,$response);
    }
    if ($response_status=='REFUSED') {
        _log("_response remove card".$response);
        return _send_response($serv,$fd,$name,$response);
    }
    _log("_process_RemoveCardEMV, response_status=${response_status} not supported");
    return false;
}


