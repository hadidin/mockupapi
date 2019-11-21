<?php

/**
 * MaxPark request packet pack and response parse functions
 */

// Helper functions
require_once  dirname(__FILE__) . DIRECTORY_SEPARATOR . 'helper.php' ;
// Result functions
require_once  dirname(__FILE__) . DIRECTORY_SEPARATOR . 'result.php' ;

define('STX', 0x02);
define('ETX', 0x03);
define ('CHECKSUM_OFFSET', 0x20);

define('CAMERA_NUMBER_MAX_LENGTH',4);
define('PLATE_NUMBER_MAX_LENGTH',16);

define('CMD_GET_TICKET',ord('G')); // 0x47
define('CMD_AUTHORIZE_PAYMENT', ord('A'));
define('CMD_IDLE',ord('$'));
define('CMD_SYNCHRONIZE_TIME',ord('T'));
define('CMD_LICENSE_PLATE_DETECTED_1',ord('L'));
define('CMD_LICENSE_PLATE_DETECTED_2',ord('D'));
// 2019-07-06
define('CMD_DUAL_LICENSE_PLATE_DETECTED_1',ord('L'));
define('CMD_DUAL_LICENSE_PLATE_DETECTED_2',ord('G'));

define('CMD_LICENSE_PLATE_STATUS_1',ord('L'));
define('CMD_LICENSE_PLATE_STATUS_2',ord('S'));
// 2019-04-29
define('CMD_MANUAL_LICENSE_PLATE_MESSAGE_1',ord('M'));
define('CMD_MANUAL_LICENSE_PLATE_MESSAGE_2',ord('P'));
define('CMD_MANUAL_LICENSE_PLATE_STATUS_1',ord('M'));
define('CMD_MANUAL_LICENSE_PLATE_STATUS_2',ord('S'));

define('PLATE_NONE','_NONE_');

/**
 * calc_checksum : calculate command/data checksum
 *
 * @param $cmd : 1 byte command
 * @param $data : data bytes in array
 *
 * @return 1 byte for checksum value
 */
function calc_checksum($cmd,$data){
    $xor_value = 0;
    $xor_value = $xor_value ^ $cmd;
    foreach($data as $byte) {
        $xor_value = $xor_value ^ $byte;
    }
    /*
      If value is less than 0x20 then add 0x20
      Eg If xor of CMD and DATA results in 0x0A then CHECKSUM = 0x2A
    */
    if ($xor_value < CHECKSUM_OFFSET) {
        $xor_value = $xor_value + CHECKSUM_OFFSET;
    }
    return $xor_value;
}

/**
 * calc_checksum2 : calculate command/data checksum function 2
 *
 * @param $cmd1 : first byte of command
 * @param $cmd2 : second byte of command
 * @param $data : data bytes in array
 *
 * @return 1 byte for checksum value
 */
function calc_checksum2($cmd1,$cmd2,$bytes){
    $xor_value = 0;
    $xor_value = $xor_value ^ $cmd1;
    $xor_value = $xor_value ^ $cmd2;
    foreach($bytes as $byte) {
        $xor_value = $xor_value ^ $byte;
    }
    /*
      If value is less than 0x20 then add 0x20
      Eg If xor of CMD and DATA results in 0x0A then CHECKSUM = 0x2A
    */
    if ($xor_value < CHECKSUM_OFFSET) {
        $xor_value = $xor_value + CHECKSUM_OFFSET;
    }
    return $xor_value;
}

function get_ticket_error_message($err_code) {
    $err_msgs = array(
        'B001' => 'Invalid Barcode – Barcode is not a valid Maxpark parking barcode or is from a different site',
        'C010' => 'Command Checksum Error – Checksum on command is incorrect',
        'D002' => 'Mismatched Data – Data on barcode does not match data in system database',
        'D003' => 'Invalid Checksum – Checksum on barcode is incorrect',
        'D008' => 'Invalid Ticket – Ticket # not found in system database',
        'D009' => 'Database Error – Unable to access system database',
        'D011' => 'No data found for the requested date',
        'D012' => 'Invalid Message Format – Message is not in correct format',
        'D016' => 'Ticket Checksum Error – Error in printed ticket checksum (CALE system only)',
        'P004' => 'Paid Ticket – Payment already received for this ticket, and is still within Exit Grace Period',
        'P005' => 'Used Ticket – Transaction already completed for this ticket (ie Vehicle has already left site',
        'P006' => 'Used UID – UID has already been registered by the system',
        'P007' => 'Overpayment – Discount value is more than the value of the ticket',
        'P013' => 'Underpayment – Payment Authorization value is less than Parking Fee',
        'P014' => 'Used Authorization Code – Payment Authorization Code already used before',
        'P015' => 'Paid Ticket but Ticket already expired (ie Past exit Grace Period)',
        'E999' => 'Unknown Error – Any other error condition that prevents the system from calculating the ticket value',
    );
    if (isset($err_msgs[$err_code])) {
        return $err_msgs[$err_code];
    }
    return "Unknown Error Code : $err_code";
}

/**
 * Check the ticket is success or not from MaxPark response
 *
 * @param $ticket : the ticket string, 8 chars
 *
 */
function check_ticket($ticket) {
    if (strlen($ticket)!=8) {
        return result_parameter_validate_error('ticket length != 8');
    }
    $start = substr($ticket,0,4);
    if ($start == 'EROR') {
        $error = substr($ticket,4,4);
        $message = get_ticket_error_message($error);
        return result_error($error,$message);
    }
    return result_success();
}
/**
 * Check the ticket is success or not from MaxPark response
 *
 * @param $ticket : the ticket string, 8 chars
 *
 */
function check_auth_response_code($status) {

    if ($status != 'S000') {
        $message = get_ticket_error_message($status);
        return result_error($status,$message);
    }
    return result_success();
}



/**
 * pack_get_barcode : pack 'get ticket by barcode' packet
 * command example : STX | “G” | “10933291210461381762” | CHECKSUM | ETX
 *
 * @param $barcode : 20 ASCII characters
 *
 * @return $result array : the result with error code, message and data
 */
function pack_get_barcode($barcode) {
    if (strlen($barcode)!=20) {
        return result_parameter_validate_error('barcode length !=20');
    }
    $packet = array();
    $packet[] = STX;
    $packet[] = CMD_GET_TICKET;
    $data_bytes = string_to_bytes($barcode);
    $packet = array_merge($packet,$data_bytes);
    $checksum = calc_checksum(CMD_GET_TICKET,$data_bytes);
    $packet[] = $checksum;
    $packet[] = ETX;
    $data = array (
        'packet' => $packet,
    );
    return result_success_data($data);
}

/**
 * pack_get_eticket : pack 'get ticket by eticket' packet
 * command example : STX | “G” | “ETICKET12345678     ” | CHECKSUM | ETX
 *
 * @param $eticket : 8-Digit e-Ticket
 *
 * @return $result array : the result with error code, message and data
 */
function pack_get_eticket($eticket){
    if (strlen($eticket)!=8) {
        return result_parameter_validate_error('eticket length !=8');
    }
    $packet = array();
    $packet[] = STX;
    $packet[] = CMD_GET_TICKET;
    /*
    DATA = e-Ticket Information.(20 ASCII characters)
    Command example : “ETICKET” (Fixed) + 8-Digit e-Ticket + “      ” (5x Spaces)
    */
    $ticket_str = "ETICKET";
    $ticket_str.= $eticket;
    $ticket_str.= '     ';
    $data_bytes = string_to_bytes($ticket_str);
    $packet = array_merge($packet,$data_bytes);
    $checksum = calc_checksum(CMD_GET_TICKET,$data_bytes);
    $packet[] = $checksum;
    $packet[] = ETX;
    $data = array (
        'packet' => $packet,
    );
    return result_success_data($data);
}

/*
 * parse_get_ticket_response : parse the get barcode/eticket ticket response
 * the response format is : STX | CMD | DATA | CHECKSUM | ETX |
 * CMD = Fixed byte = “G” or 0x47
 * DATA = ODATA + TICKET + ENTRY + EXIT + VALUE
 *  ODATA (20 chars) = Original barcode data that was received by the system
 *  TICKET (8 Chars) = Ticket number derived from ODATA
 *  ENTRY (10 chars) = Entry Date derived from ODATA in format: “YYMMDDHHNN”
 *  EXIT (10 chars) = Exit date (ie date of calculation) in format: “YYMMDDHHNN”
 *  VALUE (6 chars) = Calculated Parking Fee of ticket based on ENTRY and EXIT,
 *  value will be left padded with zeros Eg If Value = 3000, then system will send “003000”
 *
 * response examples: STX | “G” | “10933291210461381762” | “01223344” | “1607011300” | “1607011435” | “002500” | 0x4B | ETX
 * Original barcode data =  10933291210461381762
 * Ticket Number = 01223344
 * Entry Time = 1300 (1pm) on 1st July 16
 * Fee Calculation Time = 1435 (2.35pm) on 1st July 16
 * Calculated Fee = 2500
 * Response with barcode invalid: STX | “G” | “10933291210461381762” | “ERORB001” | “0000000000” | “0000000000” | “000000” | 0x35 | ETX
 * If there is an error calculating the Ticket Value, the TICKET data field will contain the
 * letters “EROR” plus the error code. All other fields will be set to “0”
 *
 * @param $response :  the response bytes from MaxPark Server
 *
 * @return $result :  the result array with success,error,message and data
 *
*/
function parse_get_ticket_response($response) {
    // check total length
    if (sizeof($response)<58){
        return result_response_parse_error('response length error :' . sizeof($response));
    }
    // check STX
    if ($response[0]!=STX) {
        return result_response_parse_error('response STX error');
    }
    // check command
    if ($response[1]!=CMD_GET_TICKET) {
        return result_response_parse_error('response CMD error');
    }
    // check checksum
    $data = array_slice($response,2,54);
    $checksum = calc_checksum(CMD_GET_TICKET,$data);
    if ($checksum!=$response[56]) {
        return result_response_parse_error('response CHECKSUM error');
    }
    // check ETX
    if ($response[57]!=ETX) {
        return result_response_parse_error('response ETX error');
    }
    $rdata = array();
    // get barcode or eticket
    $barcode_data = array_slice($data,0,20);
    $barcode_resp = bytes_to_string($barcode_data);
    $rdata['odata'] = $barcode_resp;
    // get ticket
    $ticket_data = array_slice($data,20,8);
    $ticket_str = bytes_to_string($ticket_data);
    $rdata['ticket'] = $ticket_str;
    // entry time
    $entry_data = array_slice($data,28,10);
    $entry_str = bytes_to_string($entry_data);
    $rdata['entry'] = $entry_str;
    // exit time
    $exit_data = array_slice($data,38,10);
    $exit_str = bytes_to_string($exit_data);
    $rdata['exit'] = $exit_str;
    // value, as parking fee
    $value_data = array_slice($data,48,6);
    $value_str = bytes_to_string($value_data);
    $rdata['value'] = intval($value_str);
    return result_success_data($rdata);
}

/**
 * pack_authorize_payment : pack 'authorize payment' packet
 * command example : STX | “A” | “01123456” | “ABCDEFGH1234” | “001000” | CHECKSUM | ETX
 * DATA = Ticket Number (8 ASCII characters), Payment Authorization Code (12 Chars), Payment Amount (6 Chars)
 *
 * @param $ticket : ticket , 8 ascii characters
 * @param $code : payment authorization code, 12 chars
 * @param $amount : payment amout, in sen
 *
 * @return $result :  the result array with success,error,message and data
 */
function pack_authorize_payment($ticket,$code,$amount){
    if (strlen($ticket)!=8) {
        return result_parameter_validate_error('eticket length !=8');
    }
    if (strlen($code)!=12) {
        return result_parameter_validate_error('code length !=12');
    }
    $packet = array();
    $packet[] = STX;
    $packet[] = CMD_AUTHORIZE_PAYMENT;
    $data_str = $ticket;
    $data_str .= $code;
    $data_str .= sprintf('%06d',$amount);
    $data_bytes = string_to_bytes($data_str);
    $packet = array_merge($packet,$data_bytes);
    $checksum = calc_checksum(CMD_AUTHORIZE_PAYMENT,$data_bytes);
    $packet[] = $checksum;
    $packet[] = ETX;
    $data = array (
        'packet' => $packet,
    );
    return result_success_data($data);
}

/**
 * parse_authorize_payment_response : parse authorize payment response from MaxPark
 *
 * @param $response : the response in bytes
 *
 * @return $result :  the result array with success,error,message and data
 */
function parse_authorize_payment_response($response) {
    // check total length
    if (sizeof($response)<51){
        return result_response_parse_error('response length error :' . sizeof($response));
    }
    // check STX
    if ($response[0]!=STX) {
        return result_response_parse_error('response STX error');
    }
    // check command
    if ($response[1]!=CMD_AUTHORIZE_PAYMENT) {
        return result_response_parse_error('response CMD error');
    }
    // check checksum
    $data = array_slice($response,2,47);
    $checksum = calc_checksum(CMD_AUTHORIZE_PAYMENT,$data);
    if ($checksum!=$response[49]) {
        return result_response_parse_error('response CHECKSUM error');
    }
    // check ETX
    if ($response[50]!=ETX) {
        return result_response_parse_error('response ETX error');
    }
    $rdata = array();
    // get ticket
    $ticket_data = array_slice($data,0,8);
    $rdata['ticket'] = bytes_to_string($ticket_data);
    // get receipt
    $receipt_data = array_slice($data,8,10);
    $rdata['receipt'] = bytes_to_string($receipt_data);
    // value, as parking fee, in sen
    $value_data = array_slice($data,18,6);
    $rdata['value'] = intval(bytes_to_string($value_data));
    // gst, as parking fee gst
    $gst_data = array_slice($data,24,4);
    $rdata['gst'] = intval(bytes_to_string($gst_data));
    // pdate, as payment datatime
    $pdate_data = array_slice($data,28,12);
    $rdata['pdate'] = bytes_to_string($pdate_data);
    // grace period,
    $grace_data = array_slice($data,40,3);
    $rdata['grace'] = intval(bytes_to_string($grace_data));
    // status
    $statu_data = array_slice($data,43,4);
    $rdata['status'] = bytes_to_string($statu_data);
    return result_success_data($rdata);
}

/**
 *
 * DATA = CAMNUM + PLATE + SIZE + PUBLISHER
 * CAMNUM (4 Chars) : LPR Camera that detected the Vehicle License Plate. If less than 4 characters, it should be left padded with spaces.
 * PLATE (16 Chars) : License Plate of vehicle. If less than 16 characters, it should be left padded with spaces.
 * SIZE (3 Digits) : Size of the Publisher data to follow. This is only used if the vehicle is also registered as a QR App user. If set to “000” then it is assumed that the vehicle is not a QR App registered user. If the value is less than 3 digits then it should be left padded with “0”’s
 * PUBLISHER (10 Chars) = Publisher of the QR App. Omit if vehicle does not have a registered QR App
 *
 * @param string $type : packet type, 'manual' is for manual message,others are auto detect
 * @param string $camera_number : camera number
 * @param string $plate_number : plate number
 * @param string $publisher : set to 'KiplePark@' if the publisher is a kilePark user

 * @return array $result :  the result array with success,error,message and data
 */
function _packet_license_plate($type,$camera_number,$plate_number,$publisher){
    $packet = array();
    $packet[] = STX;
    $packet[] = CMD_LICENSE_PLATE_DETECTED_1;
    $packet[] = CMD_LICENSE_PLATE_DETECTED_2;
    if ($type=='manual'){
        $packet[1] = CMD_MANUAL_LICENSE_PLATE_MESSAGE_1;
        $packet[2] = CMD_MANUAL_LICENSE_PLATE_MESSAGE_2;
    }
    if (strlen($camera_number)>CAMERA_NUMBER_MAX_LENGTH) {
        return result_parameter_validate_error('camera number length'.CAMERA_NUMBER_MAX_LENGTH);
    }
    if (strlen($plate_number)>PLATE_NUMBER_MAX_LENGTH) {
        return result_parameter_validate_error('plate number length'.PLATE_NUMBER_MAX_LENGTH);
    }
    $data_str = string_padding_space($camera_number,CAMERA_NUMBER_MAX_LENGTH);
    $data_str .= string_padding_space($plate_number,PLATE_NUMBER_MAX_LENGTH);
    /*
        If the “SIZE” field is set to “999” during entry, it means that the LPR is unable to
        detect the Plate # or there is some error during processing.
        In this case the BTD will require the Driver to press for a physical ticket.
        When the SIZE is set to “999”, the PUBLISHER field is omitted
    */
    if ($plate_number==PLATE_NONE) {
        $data_str .= sprintf('%03d',999);
    } else {
        $publisher_len = strlen($publisher);
        if ($publisher_len>10) {
            return result_parameter_validate_error('publisher length > 10');
        }
        $data_str .= sprintf('%03d',$publisher_len);
        if ($publisher_len>0) {
            $data_str .= $publisher;
        }
    }
    $data_bytes = string_to_bytes($data_str);
    $packet = array_merge($packet,$data_bytes);
    $checksum = calc_checksum2($packet[1],$packet[2],$data_bytes);
    $packet[] = $checksum;
    $packet[] = ETX;
    $data = array(
        'packet' => $packet,
    );
    return result_success_data($data);
}
/**
 * pack_license_plate_detect : pack 'license plate number detect' packet
 * DATA = CAMNUM + PLATE + SIZE + PUBLISHER
 * CAMNUM (4 Chars) : LPR Camera that detected the Vehicle License Plate. If less than 4 characters, it should be left padded with spaces.
 * PLATE (16 Chars) : License Plate of vehicle. If less than 16 characters, it should be left padded with spaces.
 * SIZE (3 Digits) : Size of the Publisher data to follow. This is only used if the vehicle is also registered as a QR App user. If set to “000” then it is assumed that the vehicle is not a QR App registered user. If the value is less than 3 digits then it should be left padded with “0”’s
 * PUBLISHER (10 Chars) = Publisher of the QR App. Omit if vehicle does not have a registered QR App
 *
 * @param string $camera_number : camera number
 * @param string $plate_number : plate number
 * @param string $publisher : set to 'KiplePark@' if the publisher is a kilePark user
 *
 * @return array $result :  the result array with success,error,message and data
 */
function pack_license_plate_detect($camera_number,$plate_number,$publisher) {
    return _packet_license_plate('auto',$camera_number,$plate_number,$publisher);
}

function pack_license_plate_detect2($camera_number,$plate_number,$plate_number2,$publisher) {
    $packet = array();
    $packet[] = STX;
    $packet[] = CMD_DUAL_LICENSE_PLATE_DETECTED_1;
    $packet[] = CMD_DUAL_LICENSE_PLATE_DETECTED_2;

    if (strlen($camera_number)>CAMERA_NUMBER_MAX_LENGTH) {
        return result_parameter_validate_error('camera number length'.CAMERA_NUMBER_MAX_LENGTH);
    }
    if (strlen($plate_number)>PLATE_NUMBER_MAX_LENGTH) {
        return result_parameter_validate_error('plate number length'.PLATE_NUMBER_MAX_LENGTH);
    }
    if (strlen($plate_number2)>PLATE_NUMBER_MAX_LENGTH) {
        return result_parameter_validate_error('plate number2 length'.PLATE_NUMBER_MAX_LENGTH);
    }
    $data_str = string_padding_space($camera_number,CAMERA_NUMBER_MAX_LENGTH);
    $data_str .= string_padding_space($plate_number,PLATE_NUMBER_MAX_LENGTH);
    $data_str .= string_padding_space($plate_number2,PLATE_NUMBER_MAX_LENGTH);
    /*
        If the “SIZE” field is set to “999” during entry, it means that the LPR is unable to
        detect the Plate # or there is some error during processing.
        In this case the BTD will require the Driver to press for a physical ticket.
        When the SIZE is set to “999”, the PUBLISHER field is omitted
    */
    if ($plate_number==PLATE_NONE) {
        $data_str .= sprintf('%03d',999);
    } else {
        $publisher_len = strlen($publisher);
        if ($publisher_len>10) {
            return result_parameter_validate_error('publisher length > 10');
        }
        $data_str .= sprintf('%03d',$publisher_len);
        if ($publisher_len>0) {
            $data_str .= $publisher;
        }
    }
    $data_bytes = string_to_bytes($data_str);
    $packet = array_merge($packet,$data_bytes);
    $checksum = calc_checksum2($packet[1],$packet[2],$data_bytes);
    $packet[] = $checksum;
    $packet[] = ETX;
    $data = array(
        'packet' => $packet,
    );
    return result_success_data($data);
}

/**
 * parse_license_plate_detect_response
 *
 * STX | "LS' | DATA | CHECK_SUM | ETX
 *
 * where DATA is:
 * DATA = CAMNUM + PLATE + STATUS + SIZE + PUBLISHER
 * CAMNUM (4 Chars) = LPR Camera that detected the Vehicle License Plate. This will be the same as in the received “LD” message.
 * PLATE (16 Chars) = License Plate of vehicle. This will be the same as in the received “LD” message.
 * STATUS (10 Chars) = Status of the License Plate. This will have the following meaning:
 *    SN00000000 = Season Entry OK
 *    SX00000000 = Season Exit OK
 *    SA00000000 = Season, Anti Passback Error
 *    SE00000000 = Season, Expired Registration Error
 *    SB00000000 = Season, Blacklisted Registration Error
 *    TKxxxxxxxx = Non Season vehicle. xxxxxxxx will be the Ticket Number
 * SIZE (3 Digits) = Size of the Publisher data to follow. This is only used if the vehicle
 * PUBLISHER (10 Chars) = Publisher of the QR App. Omit if vehicle does not have a registered QR App
 *
 * @param $response : the response in bytes
 *
 * @return $result :  the result array with success,error,message and data
 */
function parse_license_plate_detect_response($response) {
    $response_size = sizeof($response);
    // check total length
    if ($response_size<38){
        return result_response_parse_error("response length ($response_size) < 38");
    }
    // check STX
    if ($response[0]!=STX) {
        return result_response_parse_error('response STX error');
    }
    // check command
    if ($response[1]!=CMD_LICENSE_PLATE_STATUS_1) {
        return result_response_parse_error('response CMD error');
    }
    if ($response[2]!=CMD_LICENSE_PLATE_STATUS_2) {
        return result_response_parse_error('response CMD 2 error');
    }
    // check checksum
    $data = array_slice($response,3,$response_size-5);
    $checksum = calc_checksum2(CMD_LICENSE_PLATE_STATUS_1,CMD_LICENSE_PLATE_STATUS_2,$data);
    if ($checksum!=$response[$response_size-2]) {
        return result_response_parse_error('response CHECKSUM error');
    }
    // check ETX
    if ($response[$response_size-1]!=ETX) {
        return result_response_parse_error('response ETX error');
    }
    $rdata = array();
    // get camera number
    $camera_num_data = array_slice($data,0,4);
    $rdata['camera_num'] = str_replace(' ','',bytes_to_string($camera_num_data));
    // get plate_number
    $plate_num_data = array_slice($data,4,16);
    $rdata['plate_num'] = str_replace(' ','',bytes_to_string($plate_num_data));
    // get status
    $status_data = array_slice($data,20,10);
    $rdata['status'] = bytes_to_string($status_data);
    // get publisher size
    $size_data = array_slice($data,30,3);
    $publisher_size = intval(bytes_to_string($size_data));
    $rdata['size'] = $publisher_size;
    // get publisher
    if ($publisher_size>0) {
        $publisher_data = array_slice($data,33,$publisher_size);
        $rdata['publisher'] = bytes_to_string($publisher_data);
    } else {
        $rdata['publisher']='';
    }
    return result_success_data($rdata);
}

/**
 * check the plate number detect response status
 *  TZ00000000 = Command Rejected – No vehicle detected. Barrier is NOT raised
 *  TKxxxxxxxx = Paid and within Grace Period. xxxxxxxx will be the Ticket Number
 *  TFxxxxxxxx = Ticket not Found.
 *  TUxxxxxxxx = Ticket not Paid.
 *  TGxxxxxxxx = Exceeded Grace Period..
 *  TCxxxxxxxx = Ticket already used.
 *  TXxxxxxxxx = Other Error.
 *  TD00000000 = Non Season vehicle. Duplicate Plate. (Entry Only)
 */
function check_plate_status($status) {
    $code = substr($status,0,2);
    if ($code == 'TK') {
        $data = array(
            'ticket' => substr($status,2,8),
        );
        return result_success_data($data);
    }
    $message = "Unknown Code:$status";
    $err_msgs = array(
        'TZ' => 'Command Rejected – No vehicle detected. Barrier is NOT raised',
        'TF' => 'Ticket not Found',
        'TU' => 'Ticket not Paid',
        'TG' => 'Exceeded Grace Period',
        'TC' => 'Ticket already used',
        'TD' => 'Duplicate Plate',
        'TX' => 'Other Error',
    );
    if (isset($err_msgs[$code])) {
        $message = $err_msgs[$code];
    }
    return result_error($status,$message);
}

/**
 * pack_manual_license_plate_message : pack 'Manual License Plate Message' packet
 * DATA = CAMNUM + PLATE + SIZE + PUBLISHER
 * CAMNUM (4 Chars) : LPR Camera that detected the Vehicle License Plate. If less than 4 characters, it should be left padded with spaces.
 * PLATE (16 Chars) : License Plate of vehicle. If less than 16 characters, it should be left padded with spaces.
 * SIZE (3 Digits) : Size of the Publisher data to follow. This is only used if the vehicle is also registered as a QR App user. If set to “000” then it is assumed that the vehicle is not a QR App registered user. If the value is less than 3 digits then it should be left padded with “0”’s
 * PUBLISHER (10 Chars) = Publisher of the QR App. Omit if vehicle does not have a registered QR App
 *
 * @param $camera_number : camera number
 * @param $plate_number : plate number
 * @param $publisher : set to 'KiplePark@' if the publisher is a kilePark user
 *
 * @return $result :  the result array with success,error,message and data
 */
function pack_manual_license_plate_message($camera_number,$plate_number,$publisher) {
    return _packet_license_plate('manual',$camera_number,$plate_number,$publisher);
}

/**
 * parse_manual_license_plate_response parse the manual license plate message response packet
 *
 * STX | 'MS' | DATA | CHECK_SUM | ETX
 *
 * where DATA is:
 * DATA = CAMNUM + PLATE + STATUS + SIZE + PAYMENT
 * CAMNUM (4 Chars) = LPR Camera that detected the Vehicle License Plate. This will be the same as in the received “LD” message.
 * PLATE (16 Chars) = License Plate of vehicle. This will be the same as in the received “LD” message.
 * STATUS (10 Chars) = Status of the License Plate. This will have the following meaning:
 *  TZ00000000 = Command Rejected – No vehicle detected. Barrier is NOT raised
 *  TKxxxxxxxx = Paid and within Grace Period. xxxxxxxx will be the Ticket Number
 *  TFxxxxxxxx = Ticket not Found.
 *  TUxxxxxxxx = Ticket not Paid.
 *  TGxxxxxxxx = Exceeded Grace Period..
 *  TCxxxxxxxx = Ticket already used.
 *  TXxxxxxxxx = Other Error.
 * SIZE (3 Digits) = Size of the PAYMENT data to follow. If set to “000” it means that no payment data was found.
 * PAYMENT (44 Chars) = Payment details (If any). This will have the following format:
 *   RECEIPT + VALUE + GST + PDATE + PAYLOC
 *   RECEIPT (12 Chars) = Receipt Number for the Payment.
 *   VALUE (6 Chars) = Parking Fee for the ticket in sen (ie Payment Value)
 *   GST (4 Chars) = GST value calculated by parking system (included in VALUE).
 *   PDATE (12 Chars) = Date / Time of payment in format: “YYYYMMDDHHNN”
 *   PAYLOC (10 Chars) = Location where payment was made
 *
 * @param array $response : the response in bytes
 *
 * @return array $result :  the result array with success,error,message and data
 */
function parse_manual_license_plate_response($response) {
    $response_size = sizeof($response);
    _log('parse_manual_license_plate_response : '.bin2hex(bytes_to_string($response)));
    // check total length
    if ($response_size<38){
        return result_response_parse_error("response length ($response_size) < 38");
    }
    // check STX
    if ($response[0]!=STX) {
        return result_response_parse_error('response STX error');
    }
    // check command
    if ($response[1]!=CMD_MANUAL_LICENSE_PLATE_STATUS_1) {
        return result_response_parse_error('response CMD error');
    }
    if ($response[2]!=CMD_MANUAL_LICENSE_PLATE_STATUS_2) {
        return result_response_parse_error('response CMD 2 error');
    }
    // check checksum
    $data = array_slice($response,3,$response_size-5);
    $checksum = calc_checksum2(CMD_MANUAL_LICENSE_PLATE_STATUS_1,CMD_MANUAL_LICENSE_PLATE_STATUS_2,$data);
    // print(" check sum is :$checksum \r\n");
    if ($checksum!=$response[$response_size-2]) {
        return result_response_parse_error('response CHECKSUM error');
    }
    // check ETX
    if ($response[$response_size-1]!=ETX) {
        return result_response_parse_error('response ETX error');
    }
    $rdata = array();
    // get camera number
    $camera_num_data = array_slice($data,0,4);
    $rdata['camera_num'] = str_replace(' ','',bytes_to_string($camera_num_data));
    // get plate_number
    $plate_num_data = array_slice($data,4,16);
    $rdata['plate_num'] = str_replace(' ','',bytes_to_string($plate_num_data));
    // get status
    $status_data = array_slice($data,20,10);
    $rdata['status'] = bytes_to_string($status_data);
    // get payment size
    $size_data = array_slice($data,30,3);
    $payment_size = intval(bytes_to_string($size_data));
    $rdata['size'] = $payment_size;
    $rdata['payment_data']='';
    $rdata['payment']=array();
    // get payment
    if ($payment_size>0) {
        $payment_data = array_slice($data,33,$payment_size);
        $rdata['payment_data'] = bytes_to_string($payment_data);
        $payment = array();
        if($payment_size>=44) {
            $receipt_data = array_slice($payment_data,0,12);
            $payment['receipt'] = bytes_to_string($receipt_data);
            $value_data = array_slice($payment_data,12,6);
            $payment['value'] = intval(bytes_to_string($value_data));
            $gst_data = array_slice($payment_data,18,4);
            $payment['gst'] = intval(bytes_to_string($gst_data));
            $pdate_data = array_slice($payment_data,22,12);
            $payment['pdate'] = bytes_to_string($pdate_data);
            $payloc_data = array_slice($payment_data,34,10);
            $payment['payloc'] = bytes_to_string($payloc_data);
            $rdata['payment'] = $payment;
        }
    }
    return result_success_data($rdata);
}

/**
 * pack_idle : pack 'idle check' packet
 *
 * @param $is_loopback : is loop back idle message or not
 *
 * @return $result :  the result array with success,error,message and data
 */
function pack_idle($is_loopback){
    $packet = array();
    $packet[] = STX;
    $packet[] = CMD_IDLE;
    $data_str = date("YmdHi",time());
    if ($is_loopback) {
        $data_str = '000000000000';
    }
    $data_bytes = string_to_bytes($data_str);
    $packet = array_merge($packet,$data_bytes);
    $checksum = calc_checksum(CMD_IDLE,$data_bytes);
    $packet[] = $checksum;
    $packet[] = ETX;
    $data = array(
        'packet' => $packet,
    );
    return result_success_data($data);
}

/**
 * parse_idle_response : parse the idle response from MaxPark
 *
 * @param $response : the response in bytes
 *
 * @return $result :  the result array with success,error,message and data
 */
function parse_idle_response($response) {
    // check total length
    if (count($response)<16){
        return result_response_parse_error('response length != 16');
    }
    // check STX
    if ($response[0]!=STX) {
        return result_response_parse_error('response STX error');
    }
    // check command
    if ($response[1]!=CMD_IDLE && $response[1]!=CMD_SYNCHRONIZE_TIME) {
        return result_response_parse_error('response CMD error');
    }
    // check checksum
    $data = array_slice($response,2,12);
    $checksum = calc_checksum($response[1],$data);
    if ($checksum!=$response[14]) {
        return result_response_parse_error('response CHECKSUM error');
    }
    // check ETX
    if ($response[15]!=ETX) {
        return result_response_parse_error('response ETX error');
    }
    $rdata = array(
        'cmd' => $response[1],
        'value' => bytes_to_string($data),
    );
    return result_success_data($rdata);
}

/**
 * Generate the image file name for ftp upload
 */
function get_ftp_upload_image_filename($camera_num,$plate_num) {
    /*
    The picture filename will have the format, yyyymmddccccxxxxxxxxxxxxxxxx, where
    yyyymmdd : date
    cccc : camera number
    xxxxxxxxxxxxxxxx : the license plate number
    */
    $retomte_file = date('Ymd',time()).$plate_num.'.jpg';
//    dd($retomte_file);
    return $retomte_file;
}
