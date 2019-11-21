<table class="table table-striped">
    <thead>
    <tr>
        <th>Bil</th>
        <th>lane_id</th>
        <th>camera_sn</th>
        <th>Entry/Exit</th>
        <th>plate_no</th>
        <th>small_picture</th>
        <th>big_picture</th>
        <th>Reviewed plate</th>
        <th>is_season</th>
        <th>create_time</th>
        <th>is_success</th>
        <th>Remark</th>
        {{--<th>Action</th>--}}
    </tr>
    </thead>

    @foreach ($entry_log_db_list as $index => $car)
        @php
            $a=$index+1;
            #echo "<pre>";
            #print_r($car);die;
        @endphp

        <tr></tr>
        <td>{{$a}}</td>
        <td>{{ $car['lane_id'] }}</td>
        <td>{{ $car['camera_sn'] }}</td>
        <td>
            @if($car['in_out_flag']==0)
                {{'Entry'}}
            @elseif($car['in_out_flag']==1)
                {{'Exit'}}
            @endif
        </td>
        <td>{{ $car['plate_no'] }}</td>
        <td>
            {{--{{url('img/071524519058_clip.jpg')}}--}}
            <img src="{{url('img/071524519058_clip.jpg')}}" width="100" height="50"/>
        </td>
        <td>
            <img src="http://{{$lpr_backend_server_base_url}}{{$car['big_picture']}}" width="100" height="50"/>
        </td>
        <td>{{ $car['plate_no_reviewed'] }}</td>
        <td>
            @if($car['is_season_subscriber']==0)
                {{'No'}}
            @elseif($car['is_season_subscriber']==1)
                {{'Yes'}}
            @endif
        </td>
        <td>{{ $car['create_time'] }}</td>
        <td>{{ $car['is_success'] }}</td>
        <td>{{ $car['review'] }}</td>

    @endforeach
</table>
