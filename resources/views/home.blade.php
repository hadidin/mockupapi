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

<font color="#b2b2b2" size="5"><b>DASHBOARD</b></font><br> 
<br>
<!--
	<ol class="breadcrumb">
		<li class="breadcrumb-item active">Index</li>
	</ol> 
-->

	<div class="row quick-stats">
		<div class="col-sm-6 col-md-3">
			<div class="quick-stats__item bg-blue">
				<div class="quick-stats__info">
					<h2>RM 1,200</h2>
					<small>Total Transaction</small>
				</div>
				<div class="quick-stats__chart sparkline-bar-stats">6,4,8,6,5,6,7,8,3,5,9,5</div>

			</div>
		</div>

		<div class="col-sm-6 col-md-3">
			<div class="quick-stats__item bg-amber">
				<div class="quick-stats__info">
					<h2>{{$total_car_season_park}}</h2>
					<small>Season Parker</small>
				</div>
				<div class="quick-stats__chart sparkline-bar-stats">4,7,6,2,5,3,8,6,6,4,8,6</div>

			</div>
		</div>

		<div class="col-sm-6 col-md-3">
			<div class="quick-stats__item bg-purple">
				<div class="quick-stats__info">
					<h2>{{$total_parking_non_season}}</h2>
					<small>Visitor</small>
				</div>
				<div class="quick-stats__chart sparkline-bar-stats">9,4,6,5,6,4,5,7,9,3,6,5</div>

			</div>
		</div>

		<div class="col-sm-6 col-md-3">
			<div class="quick-stats__item bg-red">
				<div class="quick-stats__info">
					<h2>800</h2>
					<small>Parking (yet to paid)</small>
				</div>
				<div class="quick-stats__chart sparkline-bar-stats">5,6,3,9,7,5,4,6,5,6,4,9</div>

			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-sm-4">
			<div class="card">
				<div class="card-body">
					<h4 class="card-title">Payment Channel</h4>
					<div class="flot-chart" id="chart1"></div>
					<div class="flot-chart-legends"  id="legend1"></div>
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="card">
			<div class="card-body">
					<h4 class="card-title">Parking Type</h4>
					<div class="flot-chart flot-donut1"></div>
					<div class="flot-chart-legends flot-chart-legend--donut1"></div>
				</div> 
			</div>
		</div>
		<div class="col-sm-4">
			<div class="card">
				<div class="card-body">
				<h4 class="card-title">Device Monitoring</h4>
				<table class="table">
                            <thead> 
                            </thead>
                            @foreach ($lane_list as $lane)
                                <tr></tr>
								<td><font size="4" color="grey"><i class="zmdi zmdi-camera"></i></font></td>
                                <td>{{ $lane->name }}</td>
                                <td>
                                    @if($lane->camera_state=='online')
                                        <font size="3" color="limegreen"><i class="zmdi zmdi-circle"></i></font>
                                    @elseif($lane->camera_state=='offline')
                                        <font size="3" color="red"><i class="zmdi zmdi-circle"></i></font>
                                    @endif
                                </td> 

                             @endforeach
                        </table>
				</div>
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-sm-12">
			<div class="card">
				<div class="card-body">
				<h4 class="card-title">Traffic Statistic</h4>
				<div class="flot-chart flot-line"></div>
				<div class="flot-chart-legends flot-chart-legends--line"></div>
				</div>
			</div>
		</div> 
	</div>

	<div class="row">
		<div class="col-sm-6">
 			<div class="card">
				<div class="card-body">
				<h4 class="card-title">Car Plate (Ingress)</h4>
					<table class="table">
					<thead>
					<tr>
					<th>Lane</th>
					<th>Time</th>
					<th>Car Plate</th>
					</tr>
					</thead>
					<tbody>
					@foreach ($entry_log_in as $entry)
						<tr>
						<td>{{ $entry->lane_name }}</td>
						<td>{{ $entry->create_time }}</td>
						<td>{{ $entry->plate_no }}</td>
						</tr>
					@endforeach 
					</tbody>
					</table>
				</div>
 			</div> 
		</div>
		<div class="col-sm-6">
		<div class="card">
				<div class="card-body">
				<h4 class="card-title">Car Plate (Egress)</h4>
				<table class="table">
					<thead>
					<tr>
					<th>Lane</th>
					<th>Time</th>
					<th>Car Plate</th>
					</tr>
					</thead>
					<tbody>
					@foreach ($entry_log_out as $entry)
						<tr>
						<td>{{ $entry->lane_name }}</td>
						<td>{{ $entry->create_time }}</td>
						<td>{{ $entry->plate_no }}</td>
						</tr>
					@endforeach 
					</tbody>
					</table>
				</div>
 			</div> 
		</div>
	</div> 
	{{--<div data-columns>--}}
  	  {{--<div class="card">--}}
            {{--<img id="entry-image-id" class="card-img-top" src="img/plate/entry.jpg" alt="">--}}
            {{--<div class="card-body">--}}
	      {{--<h6 id="entry-title-id" class="card-title">Current Entry Record</h6>--}}
	      {{--<h6>--}}
                 {{--<span id="entry-name-id" class="badge badge-light">Entry 1</span> --}}
		 {{--<span id="entry-time-id" class="badge badge-light">2019-12-12 08:12:12</span> --}}
		 {{--<span id="entry-plate-id" class="badge badge-dark">WGS4838</span>--}}
	       {{--</h6>--}}
	       {{--<h6>--}}
                 {{--<span class="badge badge-light">Season</span>--}}
                 {{--<span id="entry-season-no-id" class="badge badge-info">00345 </span> --}}
		 {{--<span id="entry-season-result-id" class="badge badge-danger">Expired </span>--}}
               {{--</h6>--}}
	       {{--<h6>--}}
                 {{--<span class="badge badge-light">Normal</span> --}}
                 {{--<span id="normal-ticket-id" class="badge badge-info">TK2342342 </span> --}}
                 {{--<span id="normal-result-id" class="badge badge-success">Entry Success </span>--}}
               {{--</h6>--}}
	       {{--<a href="cards.html#" class="card-link">Manually Enter</a>                        --}}
              {{--</div>--}}
    	  {{--</div>--}}
	{{--</div>--}}

	<script>
'use strict';

function entryTimerFun() {
  
}

$(document).ready(function(){
    // Make some sample data
    var pieData = [
        {data: 1, color: '#ff6b68', label: 'E-Wallet'},
        {data: 2, color: '#03A9F4', label: 'Cash'},
        {data: 3, color: '#32c787', label: 'AutoPay'}
    ];
     
    // Donut Chart
    if($('#chart1')[0]){
        $.plot('#chart1', pieData, {
            series: {
                pie: {
                    innerRadius: 0.5,
                    show: true,
                    stroke: { 
                        width: 2
                    }
                }
            },
            legend: {
                container: '#legend1',
                backgroundOpacity: 0.5,
                noColumns: 0,
                backgroundColor: "white",
                lineWidth: 0,
                labelBoxBorderColor: '#fff'
            }
        });
	}
	
	var pieData2 = @php echo json_encode($total_parking_by_type) @endphp
     
    // Donut Chart
    if($('.flot-donut1')[0]){
        $.plot('.flot-donut1', pieData2, {
            series: {
                pie: {
                    innerRadius: 0.5,
                    show: true,
                    stroke: { 
                        width: 2
                    }
                }
            },
            legend: {
                container: '.flot-chart-legend--donut1',
                backgroundOpacity: 0.5,
                noColumns: 0,
                backgroundColor: "white",
                lineWidth: 0,
                labelBoxBorderColor: '#fff'
            }
        });
	}
	
	  // Chart Data
	  var lineChartData = @php echo json_encode($totalListIn) @endphp

    // Chart Options
    var lineChartOptions = {
        series: {
            lines: {
                show: true,
                barWidth: 0.05,
                fill: 0
            }
        },
        shadowSize: 0.1,
        grid : {
            borderWidth: 1,
            borderColor: '#edf9fc',
            show : true,
            hoverable : true,
            clickable : true
        },

        yaxis: {
            tickColor: '#edf9fc',
            tickDecimals: 0,
            font :{
                lineHeight: 13,
                style: 'normal',
                color: '#9f9f9f',
            },
            shadowSize: 0
        },

        xaxis: {
            tickColor: '#fff',
            tickDecimals: 0,
            font :{
                lineHeight: 13,
                style: 'normal',
                color: '#9f9f9f'
            },
            shadowSize: 0,
        },
        legend:{
            container: '.flot-chart-legends--line',
            backgroundOpacity: 0.5,
            noColumns: 0,
            backgroundColor: '#fff',
            lineWidth: 0,
            labelBoxBorderColor: '#fff'
        }
    };

    // Create chart
    if ($('.flot-line')[0]) {
        $.plot($('.flot-line'), lineChartData, lineChartOptions);
    }
});


	</script>
	@endsection
