<?php
$ini_array = parse_ini_file("config.ini",true);

$SITE_ID=$ini_array['common']['SITE_ID'];
$DB_NAME=$ini_array['common']['DB_NAME'];
$DB_HOST=$ini_array['common']['DB_HOST'];
$DB_USER=$ini_array['common']['DB_USER'];
$DB_PSWD=$ini_array['common']['DB_PSWD'];

$conn=mysqli_connect($DB_HOST,$DB_USER,$DB_PSWD,$DB_NAME);

$q="SELECT id,small_picture,big_picture FROM psm_entry_log";
$r=mysqli_query($conn,$q);
echo "<pre>";
while($data=mysqli_fetch_assoc($r)){

    $has_image_text= substr($data['small_picture'], 0, 7);

    if($has_image_text != '/result'){
        $small_image='/result'.$data['small_picture'];
        $big_image='/result'.$data['big_picture'];
        $update_query="update psm_entry_log set small_picture='$small_image',big_picture='$big_image' where id='$data[id]'";
        mysqli_query($conn,$update_query);

    }
    echo $has_image_text;
    print_r($data);
    echo "<br>";
    echo "<br>";

}


?>