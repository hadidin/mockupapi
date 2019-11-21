<?php


/**
 * tcp server deamon function
 */
function _start_server($host,$port) {
    _log("server listen on {$host}:{$port}.");
    
    $serv = new swoole_server($host,$port,SWOOLE_PROCESS,SWOOLE_SOCK_TCP /*| SWOOLE_SSL*/); 

    $serv->on('connect', function ($serv, $fd) {  
        _log("client {$fd} connected.");
    });
    
    $serv->on('receive', function ($serv, $fd, $from_id, $data) {
        $xml_content = substr($data,1,-1);
        $name = "";
        $request = array();
        $result = _parse_sb_xml_to_array($xml_content,$name,$request);
        if ($result==false) {
            _log("parse xml failed");
            return;
        }
        _log("client {$fd} send request: name={$name},data:{$xml_content}.");
        _process_sb_request($serv,$fd,$name,$request);
    });
    
    $serv->on('close', function ($serv, $fd) {
        _log("client {$fd} disconnected.");
    });
    
    $serv->start(); 
}
