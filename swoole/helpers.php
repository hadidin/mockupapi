<?php

/**
 * log function 
 */
function _log($str) {
    $log_file_name = dirname(__FILE__) . DIRECTORY_SEPARATOR."kiplepark_terminal.log";
	$info = date("Y-m-d H:i:s") . "|" . $str . "\n";
	print($info);
	file_put_contents($log_file_name, $info, FILE_APPEND);
}
/**
 * get code text message
 */
function _get_code_message($code) {
    $code_msgs = array(
        '000' => 'APPROVAL',
        '040' => 'REQUESTED FUNCTION NOT SUPPORTED',

        '014' => 'INVALID ACCOUNT',
        '033' => 'CARD EXPIRED',
        // for RESPONSE STATUS
        '100' => 'Idle',
        '101' => 'Card inserted',
        '102' => 'Card removed',
        '103' => 'Chip card accepted',
        '104' => 'Swiped card accepted',
        '105' => 'Contactless card accepted',
        '106' => 'Card identification',
        '107' => 'Card not accepted',
        '108' => 'Enter PIN',
        '109' => 'PIN accepted',
        '110' => 'Wrong PIN',
        '111' => 'Authorization processing',
        '112' => 'Authorization approved',
        '113' => 'Authorization declined',
        '114' => 'Insert Card',
        '115' => 'Void processing',
        '116' => 'Initialization processing',
        '117' => 'Shift Close processing',
        '118' => 'Activation processing',
        '119' => 'Deactivation processing',
        '120' => 'Download processing',
        '121' => 'Top-Up processing',
        '122' => 'Refund processing',
        // for ERROR STATUS
        '198' => 'Terminal not configured',
        '199' => 'Terminal unavailable',

        '203' => 'Canceled after remove card',
        '500' => 'Server online',
        '600' => 'terminal successful initialized',
        '900' => 'command successful',
        '999' => 'Unknown received data',
    );
    if (isset($code_msgs[$code])) {
        return $code_msgs[$code];
    }
    return "Unknown message";
}

/**
 * get S&B device type according to the code
 */
function _get_sb_degice_type($code) {
    $code_types = array (
        '1' => 'Entry',        
        '2' => 'Exit',
        '4' => 'PXU',
        '6' => 'Pay station',
        '7' => 'Manual cashier station',
        '8' => 'exit cashier',
    );
    return $code_types[$code];
}

/**
 * parse xml string (sent by S&B) to array, here is a sample:
 * <?xml version="1.0" ?> 
 * <AdditionalTransactionDataEMV>
 *  <MerchantTransactionID>12345</MerchantTransactionID> 
 *  <ZRNumber>2010</ZRNumber> 
 *  <DeviceNumber>201</DeviceNumber> 
 *  <DeviceType>2</DeviceType> 
 *  <TerminalID>Term01</TerminalID>
 *  <AuthorizationType>Sale</AuthorizationType> 
 *  <TransactionAmount>4.50</TransactionAmount> 
 *  <SurchargeAmount>0.50</SurchargeAmount> 
 *  <Currency>CAD</Currency > 
 *  <TimeoutResponse>30</TimeoutResponse>
 * </AdditionalTransactionDataEMV>* 
 * @param $name : output parameter, the root node name, here is "AdditionalTransactionDataEMV"
 * @param $datas : output paramter, the subnodes name and value
 */
function _parse_sb_xml_to_array($xml,&$name,&$datas) {
    $dom = DOMDocument::loadXML($xml); 
    if ($dom==false) {
        _log("_parse_sb_xml: parse failed.");
        return false;
    }
    if (count($dom->childNodes)!=1) {
        _log("_parse_sb_xml: dom child nodes is not 1.");
        return false;
    }
    $root_node = $dom->childNodes[0];
    $datas = array();
    foreach ($root_node->childNodes as $node) { 
        if ($node->nodeType==XML_ELEMENT_NODE) {
            $datas[$node->localName] = $node->nodeValue; 
        }
    }
    $name = $root_node->localName;
    return true;
}
/**
 * format to xml string according to the root node name and the response data
 */
function _format_as_xml($name,$response) {
    $xml_string = "<?xml version=\"1.0\"?>";
    $xml_string .= "<{$name}>";
    foreach ($response as $n => $v) {
        $xml_string .= "<{$n}>$v</{$n}>";
    }
    $xml_string .= "</{$name}>";
    return $xml_string;
}

/**
 * create a response from the request
 */
function _create_response($request) {
    $response = array();
    $fields = array('MerchantTransactionID',
        'ZRNumber','DeviceNumber','DeviceType','TerminalID',
        'TransactionAmount','SurchargeAmount','Currency');
    foreach ($fields as $field) {
        if (isset($request[$field])) {
            $response[$field] = $request[$field];
        }
    }
    return $response;
}

/**
 * fill response with status according to S&B doc
 */
function _fill_response_with_status(&$response,$status,$code) {
    $response['ResponseStatus'] = $status;
    $response['ResponseCode'] = $code;
    $response['ResponseTextMessage'] = _get_code_message($code);
}

/**
 * fill response with card info according to S&B doc
 */
function _fill_response_with_card_info(&$response,$account_number,$hashed_epan) {
    if ($account_number) {
        $response['AccountNumber'] = $account_number;
    }
    $response['HashedEpan'] = $hashed_epan;    
    $response['ExpirationDate'] = date('ym',strtotime('+3 month'));
    $response['CardIssuer'] = KIPLE_CARD_ISSUER;
    $response['CardType'] = KIPLE_CARD_TYPE;
}
