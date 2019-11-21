<?php

/**
 * Config settings 
 */

 $cfg_maxpark_tcp_servers = array (
     'barcode' => array (
        'ip' => '127.0.0.1',
        'port' => 4566,
        'recv_timeout' => 10,
        'send_timeout' =>10,
     ),
     'lpr' => array (
        'ip' => '127.0.0.1',
        'port' => 4710,
        'recv_timeout' => 10,
        'send_timeout' =>10,
     ),
 );

 $cfg_maxpark_ftp_servers =array (
    'lpr' => array(
        'host' => '127.0.0.1',
        'port' => 21,
        'timout' => 10,
        'username' => 'hadi',
        'password' => 'hadi123',
        'remote_path' => 'vehicle_plate',
    )  
 );


