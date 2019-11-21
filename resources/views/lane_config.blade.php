@extends('parts.template')

@section('title')
Home
@endsection


@section('content')

<div class="page-loader">
    <div class="page-loader__spinner">
        <svg viewBox="25 25 50 50">
            <circle cx="50" cy="50" r="20" fill="none" stroke-width="2" stroke-miterlimit="10" />
        </svg>
    </div>
</div>
 
<br>
<!--
<ol class="breadcrumb">
	<li class="breadcrumb-item active">Index</li>
</ol> 
-->
<font color="#b2b2b2" size="5"><b>LANE/CAMERA INFO</b></font><br>

<div class="card">
    <div class="card-body">
 
                    <div class="table-responsive">
 
                        <table class="table table-striped">
                            <thead>
                             <tr>
                                <th>Bil</th>
                                <th>Name</th>
                                <th>Camera_sn</th>
                                <th>Ip_address</th>
                                <th>Vendor lane id</th>
                                <th>Camera state</th>
                                <th>Last log id </th>
                                <th>Entry/exit</th>
                                <th>Last_update</th>
                            </tr>
                            </thead>
                            @foreach ($lane_list as $index => $lane)
                                <tr></tr>
                                <td>{{$index + 1}}</td>
                                <td>{{ $lane->lane_name }}</td>
                                <td>{{ $lane->camera_sn }}</td>
                                <td>{{ $lane->ip_address }}</td>
                                <td>{{ $lane->vendor_lane_id }}</td>
                                <td>
                                    @if($lane->camera_state=='online')
                                        <font color="green"> {{ $lane->camera_state }}</font>
                                    @elseif($lane->camera_state=='offline')
                                        <font color="red"> {{ $lane->camera_state }}</font>
                                    @endif
                                </td>
                                <td>{{ $lane->camera_state_log }}</td>
                                <td>
                                    @if($lane->in_out_flag==0)
                                        {{'Entry'}}
                                    @elseif($lane->in_out_flag==1)
                                        {{'Exit'}}
                                    @endif
                                </td>

                                <td>{{ $lane->updated_at }}</td>
                            @endforeach
                        </table>
                    </div> 
        </div>
    </div>

    <font color="#b2b2b2" size="5"><b>CAMERA STATE LOGS</b></font><br>

<div class="card">
    <div class="card-body"> 
        <div class="table-responsive"> 
            <table class="table table-striped">
                <thead>
                <tr>
                    <th>Bil</th>
                    <th>Log id</th>
                    <th>camera_sn</th>
                    <th>state</th>
                    <th>raw</th>
                    <th>create time</th>

                </tr>
                </thead>
                @php
                    if(isset($_GET['page'])){
                        $page=$_GET['page'];
                    }
                    else{
                        $page=1;
                    }
                    $page=$page-1;
                @endphp

                @foreach ($cam_state_list as $index => $cam_state)
                    @php
                        $a=$index+1;
                        $bil=$a+$page*$offset;
                    @endphp

                    <tr></tr>
                    <td>{{ $bil }}</td>
                    <td>{{ $cam_state->id }}</td>
                    <td>{{ $cam_state->camera_sn }}</td>
                    <td>{{ $cam_state->state }}</td>
                    <td>{{ $cam_state->raw }}</td>
                    <td>{{ $cam_state->create_time }}</td>

                @endforeach
            </table>
            {{ $cam_state_list->links() }}
        </div>
    </div>
</div> 
@endsection
