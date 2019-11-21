<?php
$purchase_date='190506';
$year=substr($purchase_date,0,2);
$month=substr($purchase_date,2,2);
$day=substr($purchase_date,4,2);

$purchase_time='233127';
$hour=substr($purchase_time,0,2);
$minute=substr($purchase_time,2,2);
$second=substr($purchase_time,4,2);

$datefull="$year-$month-$day $hour:$minute:$second";



echo $datefull;
echo "<<>>>";
echo $newDate = date("Y-m-d H:i:s", strtotime($datefull));



$date="Fri May 28 16:18:17 SGT 2027";
$new_expired_datex=strtotime($date);
$new_expired_datey=date("Y-m-d H:i:s",$new_expired_datex);
echo "<br>";
echo $new_expired_datey;
echo "<<>>>";
echo $new_expired_datey;
echo "<br>";