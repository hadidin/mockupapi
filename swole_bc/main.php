<?php

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__) . DS);

require_once(ROOT.'defines.php');
require_once(ROOT.'helpers.php');
require_once(ROOT.'process.php');
require_once(ROOT.'server.php');
require_once(ROOT.'entry_log.php');

function main() {
    date_default_timezone_set("Asia/Kuala_Lumpur");
    error_reporting(1); 
    _start_server("0.0.0.0",2605);
}

main();
