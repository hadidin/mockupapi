<?php
//dd($url_path);
$home_active="";$car_in_site_active="";$entry_history_active="";$entry_log_db="";$entry_log_reviewed="";
$lane_config_active="";$led_config_active="";
if($url_path=='card_list'){$home_active="class='active'";}
if($url_path=='car_in_site'){$car_in_site_active="class='active'";}
if($url_path=='entry_history'){$entry_history_active="class='active'";}
if($url_path=='entry_log_db'){$entry_log_db="class='active'";}
if($url_path=='entry_log_reviewed'){$entry_log_reviewed="class='active'";}
if($url_path=='lane_config'){$lane_config_active="class='active'";}
if($url_path=='led_display_config'){$led_config_active="class='active'";}
//dd($entry_history_active);
?>
<div class="col-sm-3 col-md-2 sidebar">
    <ul class="nav nav-sidebar">
        <?php
        $a_date=date('Y-m-d');
        $lastday_month=date("Y-m-t", strtotime($a_date));
        $firstday_month=date("Y-m-01");
        ?>
        <li <?php echo $home_active; ?>><a href="{{ url('/card_list') }}">Season card list</a></li>
        <li <?php echo $car_in_site_active; ?>><a href="{{ url('/car_in_site') }}">Car in-site</a></li>
        <li <?php echo $entry_log_db; ?>><a href="{{ url('/entry_log_db') }}?start_date={{$firstday_month}}&end_date={{$lastday_month}}">Entry log</a></li>
        <li <?php echo $entry_log_reviewed; ?>><a href="{{ url('/entry_log_reviewed') }}?start_date={{$firstday_month}}&end_date={{$lastday_month}}">Entry log Reviewed</a></li>

        <li <?php echo $entry_history_active; ?>><a href="{{ url('/entry_history') }}?start_date={{$firstday_month}}&end_date={{$lastday_month}}">Season Parking</a></li>
    </ul>
    <ul class="nav nav-sidebar">
        <li <?php echo $lane_config_active; ?>><a href="{{ url('/lane_config') }}">Lane/Camera Info</a></li>

        <li><a href="{{ route('Rap2hpoutre') }}">Details Logs</a></li>
        <li>
            <a href="{{ route('logout') }}"
               onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                Logout
            </a>

            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                {{ csrf_field() }}
            </form>
        </li>

    </ul>


</div>


