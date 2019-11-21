<?php

/**
 * All test cases for local agent with MaxPark
 */

// MaxPark packet functions
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'maxpark_packet.php' ;
// tcp functions
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tcp_socket.php' ;
// Local agent for maxpark functions
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'local_agent_maxpark.php' ;

function lassert($fun,$value,$desc) {
    if (!$value) {
        print(sprintf("assert error:function=%s,description:%s\r\n",$fun,$desc));
        return false;
    }
    return true;
}

function test_string_to_bytes() {
    $str = 'A01123456ABCDEFGH1234001000';
    $bytes = string_to_bytes($str);
    if (!lassert(__FUNCTION__,sizeof($bytes)==27,"length check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$bytes[0]==ord('A'),"char check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$bytes[26]==ord('0'),"char check failed")) {
        return false;
    }
    return true;
}

function test_checksum() {
    $str_cmd = 'B';
    $str_data = 'A01123456ABCDEFGH1234001000';
    $bytes = string_to_bytes($str_data);
    $value = calc_checksum(ord($str_cmd),$bytes);
    return lassert(__FUNCTION__,$value==0x28,"checsum check failed");
}


function test_checksum2() {
    $str_cmd = 'A';
    $str_data = '01123456RP123456780010000057201610011345030S000';
    $bytes = string_to_bytes($str_data);
    $value = calc_checksum(ord($str_cmd),$bytes);
    return lassert(__FUNCTION__,$value==(0x18+CHECKSUM_OFFSET),"checsum check failed");
}


function test_pack_get_barcode_check_failed(){
    $barcode = '0112345';
    $result = pack_get_barcode($barcode);
    if (!lassert(__FUNCTION__,!is_result_success($result),"packet check failed")) {
        return false;
    }
    $result = pack_get_barcode($barcode_null);
    if (!lassert(__FUNCTION__,!is_result_success($result),"packet check failed")) {
        return false;
    }
    return true;
}

function test_pack_get_barcode_success(){
    $barcode = '10933291210461381762';
    $result = pack_get_barcode($barcode);
    if (!lassert(__FUNCTION__,is_result_success($result),"packet check failed")) {
        return false;
    }
    $packet = get_result_data_key($result,'packet');
    if (!lassert(__FUNCTION__,sizeof($packet)==24,"packet check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$packet[1]==CMD_GET_TICKET,"cmd check failed")) {
        return false;
    }
    print(sprintf("checksum:0x%x,%d.\r\n",0x4C,0x4c));
    if (!lassert(__FUNCTION__,$packet[22]==0x4C,"checksum check failed")) {
        return false;
    }
    return true;
}

function test_pack_get_eticket_ticket_check_failed(){
    $eticket = '0112345';
    $result = pack_get_eticket($eticket);
    if (!lassert(__FUNCTION__,!is_result_success($result),"packet check failed")) {
        return false;
    }
    $result = pack_get_eticket($eticket2);
    if (!lassert(__FUNCTION__,!is_result_success($result),"packet check failed")) {
        return false;
    }
    return true;
}

function test_pack_get_eticket_success(){
    $eticket = '81234567';
    $result = pack_get_eticket($eticket);
    if (!lassert(__FUNCTION__,is_result_success($result),"packet check failed")) {
        return false;
    }
    $packet = get_result_data_key($result,'packet');
    if (!lassert(__FUNCTION__,sizeof($packet)==24,"packet check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$packet[1]==CMD_GET_TICKET,"cmd check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$packet[22]==0x2E,"checksum check failed")) {
        return false;
    }
    return true;
}

function test_pack_authorize_payment_ticket_check_failed(){
    $ticket = '0112345';
    $code = 'ABCDEFGH1234';
    $amount = 1000;
    $result = pack_authorize_payment($ticket,$code,$amount);
    if (!lassert(__FUNCTION__,!is_result_success($result),"packet check failed")) {
        return false;
    }
    return true;
}

function test_pack_authorize_payment_code_check_failed(){
    $ticket = '01123456';
    $code = 'ABCDEFGH123';
    $amount = 1000;
    $result = pack_authorize_payment($ticket,$code,$amount);
    if (!lassert(__FUNCTION__,!is_result_success($result),"packet check failed")) {
        return false;
    }
    return true;
}

function test_pack_authorize_payment_success(){
    $ticket = '01123456';
    $code = 'ABCDEFGH1234';
    $amount = 1000;
    $result = pack_authorize_payment($ticket,$code,$amount);
    if (!lassert(__FUNCTION__,is_result_success($result),"packet check failed")) {
        return false;
    }
    $packet = get_result_data_key($result,'packet');
    if (!lassert(__FUNCTION__,sizeof($packet)==30,"length check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$packet[1]==CMD_AUTHORIZE_PAYMENT,"cmd check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$packet[28]==0x4A,"checksum check failed")) {
        return false;
    }
    return true;
}


function test_pack_idle(){
    $result = pack_idle(false);
    if (!lassert(__FUNCTION__,is_result_success($result),"packet check failed")) {
        return false;
    }
    $packet = get_result_data_key($result,'packet');
    if (!lassert(__FUNCTION__,sizeof($packet)==16,"packet check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$packet[1]==CMD_IDLE,"cmd check failed")) {
        return false;
    }
    $result = pack_idle(true);
    if (!lassert(__FUNCTION__,is_result_success($result),"packet check failed")) {
        return false;
    }
    $packet = get_result_data_key($result,'packet');
    if (!lassert(__FUNCTION__,sizeof($packet)==16,"packet check failed 2")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$packet[1]==CMD_IDLE,"cmd check failed 2")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$packet[14]==0x24,"checksum check failed 2")) {
        return false;
    }
    return true;
}


function test_parse_get_ticket_response_normal() {
    // STX | “G” | “10933291210461381762” | “01223344” | “1607011300” | “1607011435” | “002500” | 0x4B | ETX
    $response = array();
    $response[] = STX;
    $response[] = ord('G');
    $response = array_merge($response,string_to_bytes('10933291210461381762'));
    $response = array_merge($response,string_to_bytes('01223344'));
    $response = array_merge($response,string_to_bytes('1607011300'));
    $response = array_merge($response,string_to_bytes('1607011435'));
    $response = array_merge($response,string_to_bytes('002500'));
    $response[] = 0x4B;
    $response[] = ETX;
    $result = parse_get_ticket_response($response);
    if (!lassert(__FUNCTION__,is_result_success($result),"response check failed")) {
        print_r($result);
        return false;
    }
    return true;
}


function test_parse_get_ticket_response_error() {
    // STX | “G” | “10933291210461381762” | “ERORB001” | “0000000000” | “0000000000” | “000000” | 0x35 | ETX
    $response = array();
    $response[] = STX;
    $response[] = ord('G');
    $response = array_merge($response,string_to_bytes('10933291210461381762'));
    $response = array_merge($response,string_to_bytes('ERORB001'));
    $response = array_merge($response,string_to_bytes('0000000000'));
    $response = array_merge($response,string_to_bytes('0000000000'));
    $response = array_merge($response,string_to_bytes('000000'));
    $response[] = 0x35;
    $response[] = ETX;
    $result = parse_get_ticket_response($response);
    if (!lassert(__FUNCTION__,is_result_success($result),"response check failed")) {
        print_r($result);
        return false;
    }
    return true;
}

function test_parse_get_ticket_response_check_failed() {
    // STX | “G” | “10933291210461381762” | “ERORB001” | “0000000000” | “0000000000” | “000000” | 0x35 | ETX
    $response = array();
    $response[] = STX;
    $response[] = ord('G');
    $response = array_merge($response,string_to_bytes('10933291210461381762'));
    $response = array_merge($response,string_to_bytes('ERORB001'));
    $response = array_merge($response,string_to_bytes('0000000000'));
    $response = array_merge($response,string_to_bytes('0000000000'));
    $response = array_merge($response,string_to_bytes('000000'));
    $response[] = 0x35;
    $response[] = ETX;
    $result = parse_get_ticket_response($response);
    if (!lassert(__FUNCTION__,is_result_success($result),"response check failed")) {
        print_r($result);
        return false;
    }

    $response[0] = 0x11;
    $result = parse_get_ticket_response($response);
    if (!lassert(__FUNCTION__,!is_result_success($result),"STX check failed")) {
        return false;
    }

    $response[0] = STX;
    $response[1] = STX;
    $result = parse_get_ticket_response($response);
    if (!lassert(__FUNCTION__,!is_result_success($result),"CMD check failed")) {
        return false;
    }

    $response[1] = ord('G');;
    $length = sizeof($response);
    $response[$length-2]=0x23;
    $result = parse_get_ticket_response($response);
    if (!lassert(__FUNCTION__,!is_result_success($result),"CHECKSUM check failed")) {
        return false;
    }

    $response[$length-2]=0x35;
    $response[$length-1]=0x11;
    $result = parse_get_ticket_response($response);
    if (!lassert(__FUNCTION__,!is_result_success($result),"ETX check failed")) {
        return false;
    }

    $response[]=0x35;
    $result = parse_get_ticket_response($response);
    if (!lassert(__FUNCTION__,!is_result_success($result),"length check failed")) {
        return false;
    }
    return true;
}


function test_parse_authorize_payment_response() {
    //  STX | “A” | “01123456” | “RP12345678” | “001000” | “0057” | “201610011345” |“030” | “S000” | 0x18 | ETX
    $response = array();
    $response[] = STX;
    $response[] = ord('A');
    $response = array_merge($response,string_to_bytes('01123456'));
    $response = array_merge($response,string_to_bytes('RP12345678'));
    $response = array_merge($response,string_to_bytes('001000'));
    $response = array_merge($response,string_to_bytes('0057'));
    $response = array_merge($response,string_to_bytes('201610011345'));
    $response = array_merge($response,string_to_bytes('030'));
    $response = array_merge($response,string_to_bytes('S000'));
    $response[] = 0x18+CHECKSUM_OFFSET;
    $response[] = ETX;
    $result = parse_authorize_payment_response($response);
    if (!lassert(__FUNCTION__,is_result_success($result),"response check failed")) {
        print_r($result);
        return false;
    }
    $data = get_result_data($result);
    if (!lassert(__FUNCTION__,$data['ticket']=='01123456',"ticket check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$data['receipt']=='RP12345678',"ticket check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$data['value']==1000,"value check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$data['gst']==57,"gst check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$data['pdate']=='201610011345',"pdate check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$data['grace']==30,"grace check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$data['status']=='S000',"status check failed")) {
        return false;
    }

    return true;
}

function test_parse_authorize_payment_response_failed() {
    //  STX | “A” | “01123456” | “RP12345678” | “001000” | “0057” | “201610011345” |“030” | “S000” | 0x18 | ETX
    $response = array();
    $response[] = STX;
    $response[] = ord('A');
    $response = array_merge($response,string_to_bytes('01123456RP123456780010000057201610011345030S000'));
    $response[] = 0x18+CHECKSUM_OFFSET;
    $response[] = ETX;
    $result = parse_authorize_payment_response($response);
    if (!lassert(__FUNCTION__,is_result_success($result),"response check failed")) {
        print_r($result);
        return false;
    }

    $response[0] = 0x11;
    $result = parse_authorize_payment_response($response);
    if (!lassert(__FUNCTION__,!is_result_success($result),"STX check failed")) {
        return false;
    }

    $response[0] = STX;
    $response[1] = STX;
    $result = parse_authorize_payment_response($response);
    if (!lassert(__FUNCTION__,!is_result_success($result),"CMD check failed")) {
        return false;
    }
    $response[1] = ord('A');
    $length = sizeof($response);
    $response[$length-2]=0x23;
    $result = parse_authorize_payment_response($response);
    if (!lassert(__FUNCTION__,!is_result_success($result),"CHECKSUM check failed")) {
        return false;
    }

    $response[$length-2]=0x18;
    $response[$length-1]=0x11;
    $result = parse_authorize_payment_response($response);
    if (!lassert(__FUNCTION__,!is_result_success($result),"ETX check failed")) {
        return false;
    }

    $response[]=0x35;
    $result = parse_authorize_payment_response($response);
    if (!lassert(__FUNCTION__,!is_result_success($result),"length check failed")) {
        return false;
    }
    return true;
}

function test_parse_license_plate_detect_response() {
    //  STX | “LS” | “N001” | “ WAX1234” | “SE00000000” | “000” | 0x27 | ETX
    $response = array();
    $response[] = STX;
    $response = array_merge($response,string_to_bytes('LSN001         WAX1234SE00000000000'));
    $response[] = 0x2C;
    $response[] = ETX;
    $result = parse_license_plate_detect_response($response);
    print_r($result);
    if (!lassert(__FUNCTION__,$result['success'],"response check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$result['data']['camera_num']=='N001',"camera num check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$result['data']['plate_num']=='WAX1234',"plate num check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$result['data']['status']=='SE00000000',"status check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$result['data']['size']==0,"size check failed")) {
        return false;
    }
    return true;
}

function test_parse_license_plate_detect_response2() {
    //  STX | “LS” | “N001” | “ WAX1234” | “SE00000000” | “000” | 0x27 | ETX
    $response = array();
    $response[] = STX;
    $response = array_merge($response,string_to_bytes('LSN001         WAX1234SE00000000010KiplePark@'));
    $response[] = 0x1E+CHECKSUM_OFFSET;
    $response[] = ETX;
    $result = parse_license_plate_detect_response($response);
    print_r($result);
    if (!lassert(__FUNCTION__,$result['success'],"response check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$result['data']['camera_num']=='N001',"camera num check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$result['data']['plate_num']=='WAX1234',"plate num check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$result['data']['status']=='SE00000000',"status check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$result['data']['size']==10,"size check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$result['data']['publisher']=='KiplePark@',"size check failed")) {
        return false;
    }
    return true;
}

function test_parse_license_plate_detect_response_failed() {
    //  STX | “LS” | “N001” | “ WAX1234” | “SE00000000” | “000” | 0x27 | ETX
    $response = array();
    $response[] = STX;
    $response = array_merge($response,string_to_bytes('LSN001         WAX1234SE00000000000'));
    $response[] = 0x2C;
    $response[] = ETX;
    $result = parse_license_plate_detect_response($response);
    if (!lassert(__FUNCTION__,$result['success'],"response check failed")) {
        return false;
    }
    $response[0] = 0x11;
    $result = parse_license_plate_detect_response($response);
    if (!lassert(__FUNCTION__,$result['success']==false,"STX check failed")) {
        return false;
    }

    $response[0] = STX;
    $response[1] = STX;
    $result = parse_license_plate_detect_response($response);
    if (!lassert(__FUNCTION__,$result['success']==false,"CMD check failed")) {
        return false;
    }
    $response[1] = ord('L');
    $length = sizeof($response);
    $response[$length-2]=0x23;
    $result = parse_license_plate_detect_response($response);
    if (!lassert(__FUNCTION__,$result['success']==false,"CHECKSUM check failed")) {
        return false;
    }

    $response[$length-2]=0x2C;
    $response[$length-1]=0x11;
    $result = parse_license_plate_detect_response($response);
    if (!lassert(__FUNCTION__,$result['success']==false,"ETX check failed")) {
        return false;
    }

    $response = array();
    $result = parse_license_plate_detect_response($response);
    if (!lassert(__FUNCTION__,$result['success']==false,"length check failed")) {
        return false;
    }
    return true;
}

function test_parse_idle_response() {
    //   STX | “T” | “201601271345” | 0x56 | ETX
    $response = array();
    $response[] = STX;
    $response[] = ord('T');
    $response = array_merge($response,string_to_bytes('201601271345'));
    $response[] = 0x56;
    $response[] = ETX;
    $result = parse_idle_response($response);
    if (!lassert(__FUNCTION__,is_result_success($result),"response check failed")) {
        print_r($result);
        return false;
    }
    $data = get_result_data($result);
    if (!lassert(__FUNCTION__,$data['value']=='201601271345',"value check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$data['cmd']==ord('T'),"response command check failed")) {
        return false;
    }
    return true;
}

function test_parse_idle_response2() {
    //   STX | “$” | “000000000000” | 0x24 | ETX
    $response = array();
    $response[] = STX;
    $response[] = ord('$');
    $response = array_merge($response,string_to_bytes('000000000000'));
    $response[] = 0x24;
    $response[] = ETX;
    $result = parse_idle_response($response);
    if (!lassert(__FUNCTION__,is_result_success($result),"response check failed")) {
        print_r($result);
        return false;
    }
    $data = get_result_data($result);
    if (!lassert(__FUNCTION__,$data['value']=='000000000000',"value check failed")) {
        return false;
    }
    return true;
}

function test_parse_idle_response_failed() {
    //   STX | “$” | “000000000000” | 0x24 | ETX
    $response = array();
    $response[] = STX;
    $response[] = ord('$');
    $response = array_merge($response,string_to_bytes('000000000000'));
    $response[] = 0x24;
    $response[] = ETX;
    $result = parse_idle_response($response);
    print_r($result);
    if (!lassert(__FUNCTION__,$result['success'],"response check failed")) {
        return false;
    }

    $response[0] = 0x11;
    $result = parse_idle_response($response);
    if (!lassert(__FUNCTION__,$result['success']==false,"STX check failed")) {
        return false;
    }

    $response[0] = STX;
    $response[1] = STX;
    $result = parse_idle_response($response);
    if (!lassert(__FUNCTION__,$result['success']==false,"CMD check failed")) {
        return false;
    }
    $response[1] = ord('$');
    $length = sizeof($response);
    $response[$length-2]=0x23;
    $result = parse_idle_response($response);
    if (!lassert(__FUNCTION__,$result['success']==false,"CHECKSUM check failed")) {
        return false;
    }

    $response[$length-2]=0x24;
    $response[$length-1]=0x11;
    $result = parse_idle_response($response);
    if (!lassert(__FUNCTION__,$result['success']==false,"ETX check failed")) {
        return false;
    }

    $response[]=0x35;
    $result = parse_idle_response($response);
    if (!lassert(__FUNCTION__,$result['success']==false,"length check failed")) {
        return false;
    }
    return true;
}

function test_pack_license_plate_detect(){
    $camera_num = 'N01';
    $plate_num = 'WAX1234';
    $publisher = '';
    $result = pack_license_plate_detect($camera_num,$plate_num,$publisher);
    if (!lassert(__FUNCTION__,is_result_success($result),"response check failed")) {
        print_r($result);
        return false;
    }
    $packet = get_result_data_key($result,'packet');
    if (!lassert(__FUNCTION__,sizeof($packet)==28,"packet check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$packet[1]==CMD_LICENSE_PLATE_DETECTED_1,"cmd check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$packet[2]==CMD_LICENSE_PLATE_DETECTED_2,"cmd check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$packet[26]==0x3D,"checksum check failed")) {
        return false;
    }
    return true;
}

function test_pack_license_plate_detect2(){
    $camera_num = 'N001';
    $plate_num = 'BCW5678';
    $publisher = 'KiplePark@';
    $result = pack_license_plate_detect($camera_num,$plate_num,$publisher);
    if (!lassert(__FUNCTION__,is_result_success($result),"response check failed")) {
        print_r($result);
        return false;
    }
    $packet = get_result_data_key($result,'packet');
    if (!lassert(__FUNCTION__,sizeof($packet)==38,"packet check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$packet[1]==CMD_LICENSE_PLATE_DETECTED_1,"cmd check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$packet[2]==CMD_LICENSE_PLATE_DETECTED_2,"cmd check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$packet[36]==0x2F,"checksum check failed")) {
        return false;
    }
    return true;
}


function test_pack_license_plate_detect_failed(){
    $camera_num = '00N001';
    $plate_num = 'BCW5678';
    $publisher = 'KiplePark@';
    $result = pack_license_plate_detect($camera_num,$plate_num,$publisher);
    if (!lassert(__FUNCTION__,!is_result_success($result),"camera check failed")) {
        print_r($result);
        return false;
    }
    $camera_num = 'N001';
    $plate_num = '12345678901234567BCW5678';
    $result = pack_license_plate_detect($camera_num,$plate_num,$publisher);
    if (!lassert(__FUNCTION__,!is_result_success($result),"plate number check failed")) {
        print_r($result);
        return false;
    }
    $plate_num = 'BCW5678';
    $publisher = 'KiplePark@12312312312312312';
    $result = pack_license_plate_detect($camera_num,$plate_num,$publisher);
    if (!lassert(__FUNCTION__,!is_result_success($result),"publisher check failed")) {
        print_r($result);
        return false;
    }
    return true;
}

function test_tcp_request() {
    $ip = '172.16.52.144';
    $port = '9501';
    $camera_num = 'N01';
    $plate_num = 'WAX1234';
    $publisher = '';
    $result = pack_license_plate_detect($camera_num,$plate_num,$publisher);
    if (!lassert(__FUNCTION__,is_result_success($result),"pack failed")) {
        return false;
    }
    $packet = get_result_data_key($result,'packet');
    $result = tcp_request($packet,$ip,$port);
    if (!lassert(__FUNCTION__,is_result_success($result),"tcp_request failed")) {
        return false;
    }
    $response = get_result_data_key($result,'response');
    $result = parse_license_plate_detect_response($response);
    if (!lassert(__FUNCTION__,is_result_success($result),"parse response failed")) {
        print_r($result);
        return false;
    }
    return true;
}

function test_request_get_barcode_ticket() {
    $result = request_get_barcode_ticket('12345678901234567890');
    print_r($result);
    if (!lassert(__FUNCTION__,$result['success'],"request_get_barcode_ticket failed")) {
        return false;
    }
    return true;
}

function test_request_authorise_ticket() {
    $result = request_authorise_ticket('01123457','123456789012',3000);
    print_r($result);
    if (!lassert(__FUNCTION__,$result['success'],"request_authorise_ticket failed")) {
        return false;
    }
    return true;
}

function test_request_get_eticket() {
    $result = request_get_eticket('12345678');
    print_r($result);
    if (!lassert(__FUNCTION__,$result['success'],"request_get_eticket failed")) {
        return false;
    }
    return true;
}


function test_request_check_online() {
    $result = request_check_online();
    print_r($result);
    if (!lassert(__FUNCTION__,$result['success'],"request_check_online failed")) {
        return false;
    }
    return true;
}


function test_request_license_plate_detect() {
    $result = request_license_plate_detect('EN01','ABC123','',true);
    print_r($result);
    if (!lassert(__FUNCTION__,$result['success'],"request_license_plate_detect failed")) {
        return false;
    }
    return true;
}



function test_pack_manual_license_plate_message(){
    $camera_num = 'X001';
    $plate_num = 'WA1234K';
    $publisher = '';
    $result = pack_manual_license_plate_message($camera_num,$plate_num,$publisher);
    if (!lassert(__FUNCTION__,is_result_success($result),"response check failed")) {
        print_r($result);
        return false;
    }
    $packet = get_result_data_key($result,'packet');
    if (!lassert(__FUNCTION__,sizeof($packet)==28,"packet check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$packet[1]==CMD_MANUAL_LICENSE_PLATE_MESSAGE_1,"cmd check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$packet[2]==CMD_MANUAL_LICENSE_PLATE_MESSAGE_2,"cmd check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$packet[26]==0x28,"checksum check failed")) {
        return false;
    }
    $camera_num = 'N001';
    $plate_num = 'BDD5678';
    $publisher = 'KiplePark@';
    $result = pack_manual_license_plate_message($camera_num,$plate_num,$publisher);
    if (!lassert(__FUNCTION__,is_result_success($result),"response check failed")) {
        print_r($result);
        return false;
    }
    $packet = get_result_data_key($result,'packet');
    if (!lassert(__FUNCTION__,sizeof($packet)==38,"packet check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$packet[1]==CMD_MANUAL_LICENSE_PLATE_MESSAGE_1,"cmd check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$packet[2]==CMD_MANUAL_LICENSE_PLATE_MESSAGE_2,"cmd check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$packet[36]==0x3B,"checksum check failed")) {
        return false;
    }
    return true;
}

function test_pack_manual_license_plate_message_none(){
    $camera_num = 'X001';
    $plate_num = '_NONE_';
    $publisher = '';
    $result = pack_manual_license_plate_message($camera_num,$plate_num,$publisher);
    if (!lassert(__FUNCTION__,is_result_success($result),"response check failed")) {
        print_r($result);
        return false;
    }
    $packet = get_result_data_key($result,'packet');
    if (!lassert(__FUNCTION__,sizeof($packet)==28,"packet check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$packet[1]==CMD_MANUAL_LICENSE_PLATE_MESSAGE_1,"cmd check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$packet[2]==CMD_MANUAL_LICENSE_PLATE_MESSAGE_2,"cmd check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$packet[23]==ord('9'),"size 1 check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$packet[24]==ord('9'),"size 2 check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$packet[25]==ord('9'),"size 3 check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$packet[26]==82,"checksum check failed")) {
        return false;
    }
    return true;
}

function test_parse_manual_license_plate_response() {
    $response = array();
    $response[] = STX;
    $response = array_merge($response,string_to_bytes('MSX002         WAX1234TU34567890000'));
    $response[] = 0x2D;
    $response[] = ETX;
    $result = parse_manual_license_plate_response($response);
    print_r($result);
    if (!lassert(__FUNCTION__,$result['success'],"response check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$result['data']['camera_num']=='X002',"camera num check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$result['data']['plate_num']=='WAX1234',"plate num check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$result['data']['status']=='TU34567890',"status check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$result['data']['size']==0,"size check failed")) {
        return false;
    }
    return true;
}

function test_parse_manual_license_plate_response2() {
    $response = array();
    $response[] = STX;
    $response = array_merge($response,string_to_bytes('MSX002         WAX1234TU34567890044RS81000012340005000000201901011533    APM100'));
    $response[] = 0x47;
    $response[] = ETX;
    print_r($response);
    $result = parse_manual_license_plate_response($response);
    print_r($result);
    if (!lassert(__FUNCTION__,$result['success'],"response check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$result['data']['camera_num']=='X002',"camera num check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$result['data']['plate_num']=='WAX1234',"plate num check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$result['data']['status']=='TU34567890',"status check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$result['data']['size']==44,"size check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$result['data']['payment']['pdate']=='201901011533',"payment date check failed")) {
        return false;
    }
    if (!lassert(__FUNCTION__,$result['data']['payment']['value']==500,"payment value check failed")) {
        return false;
    }
    return true;
}


function execute_test_cases(){
    $cases = array(
        /*
        'test_string_to_bytes' => 0,
        'test_checksum' => 0,
        'test_checksum2' => 0,
        'test_pack_get_barcode_check_failed' => 0,
        'test_pack_get_barcode_success' => 0,
        'test_pack_get_eticket_ticket_check_failed' => 0,
        'test_pack_get_eticket_success' => 0,
        'test_pack_authorize_payment_ticket_check_failed' =>0,
        'test_pack_authorize_payment_code_check_failed' =>0,
        'test_pack_authorize_payment_success' =>0,
        'test_pack_license_plate_detect' => 0,
        'test_pack_license_plate_detect2' => 0,
        'test_pack_license_plate_detect_failed' => 0,
        'test_pack_idle' => 0,
        'test_parse_get_ticket_response_normal' => 0,
        'test_parse_get_ticket_response_error' => 0,
        'test_parse_get_ticket_response_check_failed' => 0,
        'test_parse_authorize_payment_response' => 0,
        'test_parse_authorize_payment_response_failed' =>0,
        'test_parse_idle_response' => 0,
        'test_parse_idle_response2' => 0,
        'test_parse_idle_response_failed' =>0,
        'test_parse_license_plate_detect_response' => 0,
        'test_parse_license_plate_detect_response2' => 0,
        'test_tcp_request'=> 0,
        'test_request_authorise_ticket' => 0,
        'test_request_get_barcode_ticket' => 0,
        'test_request_get_eticket' => 0,
        'test_request_check_online' => 0,
        'test_request_license_plate_detect' => 0,
        'test_pack_manual_license_plate_message' => 0,
        'test_parse_manual_license_plate_response' => 0,
        'test_parse_manual_license_plate_response2' => 0,
        */
        'test_pack_manual_license_plate_message_none' => 0,
    );
    $total = 0;
    $failed=  0;
    foreach($cases as $case=>$value) {
        $func_name = $case;
        if (function_exists($func_name)) {
            $total ++;
            print("start to execute test case : $func_name\r\n");
            $v = $func_name();
            if (!$v) {
                $failed++;
            }
            $cases[$case] = $v?1:0;
        }
    }
    print_r($cases);
    print(sprintf("total cases:%d,failed:%d\r\n",$total,$failed));
}

function main() {
    date_default_timezone_set("Asia/Kuala_Lumpur");
    error_reporting(1);
    execute_test_cases();
}

main();
