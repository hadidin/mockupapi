<?php

/**
 * Helper functions
 */

/**
 * log function
 */
function _log($str) {
    $log_file_name = dirname(__FILE__) . DIRECTORY_SEPARATOR."local_agent_maxpark.log";
	$info = date("Y-m-d H:i:s") . "|" . $str . "\n";
	#print($info);
	file_put_contents($log_file_name, $info, FILE_APPEND);
}

function string_to_bytes($string) {
    $bytes = array();
    for($i=0;$i<strlen($string);$i++) {
        $bytes[] = ord($string[$i]);
    }
    return $bytes;
}

function bytes_to_string($bytes) {
    $string = '';
    foreach($bytes as $byte) {
        $string .= chr($byte);
    }
    return $string;
}

function string_padding_space($string,$padding_len) {
    $str_len = strlen($string);
    if ($str_len>=$padding_len) {
        return $string;
    }
    $new_string = '';
    for ($i=0;$i<$padding_len-$str_len;$i++) {
        $new_string .= ' ';
    }
    $new_string .= $string;
    return $new_string;
}
