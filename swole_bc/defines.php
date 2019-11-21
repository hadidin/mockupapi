<?php

define('KIPLE_CARD_ISSUER','XX');
define('KIPLE_CARD_TYPE','CLESS');
define('KIPLE_CARD_EXPIRED_DATE','');

define('KIPLE_ACCOUNT_DEMO','***********1111');
define('KIPLE_TICKET_DEMO','TICKET20181220111111');


define ('ERR_UNKNOWN_RECEIVED_DATA','999');

define('SB_DEVICE_TYPE_ENTRY','1');
define('SB_DEVICE_TYPE_EXIT','2');

define('SB_STATUS_APPROVAL','000');
define('SB_STATUS_IDLE','100');
define('SB_STATUS_INVALID_ACCOUNT','014');
define('SB_STATUS_CARD_INSERT','101');
define('SB_STATUS_CARD_REMOVED','102');
define('SB_STATUS_CARD_IDENTIFICATION','106');
define('SB_STATUS_AUTHORIZATION_APPROVED','112');
define('SB_STATUS_CANCELED_BY_MERCHANT','200');
define('SB_STATUS_CANCELED','203');
define('SB_STATUS_CANCELED_ACCEPTED','300');
define('SB_STATUS_SERVER_ONLINE','500');
define('SB_STATUS_INITIALIZED','600');
define('SB_STATUS_COMMAND_SUCCESSFUL','900');

define('SB_STATUS_DECLINED_PAYMENT','033');

define('SB_STATUS_APPROVAL_REJECT_TRX','201'); #for reject double entry

define('SB_IDENTIFICATION_TIMER','50');  #after receive TrxEMV and wait  max for this timer to get data from localpsm
define('SB_CHECK_RESULT_TO_REPLY','3,4,5,7,25,26');  #after receive TrxEMV and wait  max for this timer to get data from localpsm
/*
 * 3 => card expired
 * 4 => repeated entry
 * 5 => season in active
 * 7 => repeated exit
 * 8 => car locked
 * 10 => entry normal parking
 * 11 => exit normal parking
 * 12 => _none_ entry
 * 13 => _none_ exit
 */

$ini_array = parse_ini_file("../cron/config.ini",true);
define('DB_HOST',$ini_array['common']['DB_HOST']);
define('DB_USER',$ini_array['common']['DB_USER']);
define('DB_PSWD',$ini_array['common']['DB_PSWD']);
define('DB_NAME',$ini_array['common']['DB_NAME']);

define('STX', 0x02);
define('ETX', 0x03);