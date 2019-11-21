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

	<header class="content__title">
        <h1>DASHBOARD</h1>
        <small id="small-sub-title">Show today's system summary report </small>

        <div class="actions">
        	<a href="javascript:refresh();" class="actions__item zmdi zmdi-refresh" data-title="refresh"></a>
        </div>
    </header>

	<div class="row">
		<div class="col-sm-6 col-md-3">
			<a href="report_daily_car_in_park">
				<div class="stats__item">
					<div class="stats__chart bg-lime">
						<div id="div-stats-car-cut" class="flot-chart flot-chart--xs"></div>
					</div>
					<div class="stats__info">
						<div>
							<h2 id="h2-car-in-park-cut-off">0</h2>
							<small>Opening Car in Parks</small>
						</div>
					</div>
				</div>		
			</a>
		</div>	
		<div class="col-sm-6 col-md-3">
			<div class="stats__item">
                <div class="stats__chart bg-teal">
                    <div id="div-stats-entry" class="flot-chart flot-chart--xs"></div>
                </div>
                <div class="stats__info">
        		    <div>
						<h2 id="h2-total-entry">0</h2>
						<small>Total Entry</small>
        			</div>
                </div>
            </div>		
		</div>

		<div class="col-sm-6 col-md-3">
			<div class="stats__item">
                <div class="stats__chart bg-blue">
                    <div id="div-stats-exit" class="flot-chart flot-chart--xs"></div>
                </div>
                <div class="stats__info">
        		    <div>
						<h2 id="h2-total-exit">0</h2>
						<small>Total Exit</small>
        			</div>
                </div>
            </div>		
		</div>

		<div class="col-sm-6 col-md-3">
			<a href="car_in_park">
				<div class="stats__item">
					<div class="stats__chart bg-lime">
						<div id="div-stats-car" class="flot-chart flot-chart--xs"></div>
					</div>
					<div class="stats__info">
						<div>
							<h2 id="h2-car-in-park">0</h2>
							<small>Car in Park</small>
						</div>
					</div>
				</div>
			</a>
		</div>
	</div>

	<div class="row">
		<div class="col-sm-6 col-md-3">
			<a href="report_revenue">
				<div class="card">
					<div class="card-body">
						<h4 class="card-title">Total Revenue</h4>
						<div id="div-flot-revenue" class="flot-chart" style="height: 150px;"></div>
						<div id="div-flot-revenue-legends" class="flot-chart-legends"></div>
					</div>
				</div>
			</a>
		</div> 
		<div class="col-sm-6 col-md-3">
			<div class="card">
				<div class="card-body">
					<h4 class="card-title">Parking Type</h4>
					<div id="div-flot-parking-type" class="flot-chart" style="height: 150px;"></div>
					<div id="div-flot-parking-type-legends" class="flot-chart-legends"></div>
				</div>
			</div>
		</div> 
		<div class="col-sm-6 col-md-6">
			<div class="card">
				<div class="card-body">
					<h4 class="card-title">Parking Efficiency (last 7 days)</h4>
					<div id="div-flot-efficiency" class="flot-chart" style="height: 180px;"></div>
				</div>
			</div>
		</div> 
	</div>

	<div class="row">
		<div class="col-sm-12">
			<div class="card">
				<div class="card-body">
					<h4 class="card-title">Traffic Statistic</h4>
					<div id="div-flot-traffic" class="flot-chart"></div>
					<div id="div-flot-traffic-legends" class="flot-chart-legends"></div>
				</div>
			</div>
		</div> 
	</div>

	<script>
	'use strict';
	function show_notify($message,$type) {
		$.notify({
                message: $message,
            }, {
                type: $type,
                element: 'body',
            });		
	}

	function format_money($number, $places=2, $symbol="RM", $thousand=",", $decimal=".") {
        $number = $number || 0;
        $places = !isNaN($places = Math.abs($places)) ? $places : 2;
        $symbol = $symbol !== undefined ? $symbol : "$";
        $thousand = $thousand || ",";
        $decimal = $decimal || ".";
        var $negative = $number < 0 ? "-" : "",
        $i = parseInt($number = Math.abs(+$number || 0).toFixed($places), 10) + "",
    	$j = ($j = $i.length) > 3 ? $j % 3 : 0;
        return $symbol + $negative + ($j ? $i.substr(0, $j) + $thousand : "") + $i.substr($j).replace(/(\d{3})(?=\d)/g, "$1" + $thousand) + ($places ? $decimal + Math.abs($number - $i).toFixed($places).slice(2) : "");
    }
	
	function setup_flot_traffic($entry_data,$exit_data,$tick_data) {
		var $chart_data = [
			{
				label: 'Entry',
				data: $entry_data,
				color: '#32c787',
				bars: {
                	order: 0
            	}
			},
			{
				label: 'Exit',
				data: $exit_data,
				color: '#03A9F4',
				bars: {
                	order: 1
            	}
			}   
		];
		var $bar_chart_options = {
			series: {
				bars: {
					show: true,
					barWidth: 0.3,
					lineWidth:1,			
					fill: 1,
				}
			},
			grid : {
				borderWidth: 1,
				borderColor: '#f8f8f8',
				show : true,
				hoverable : true
			},
			yaxis: {
				tickColor: '#f8f8f8',
				tickDecimals: 0,
				font :{
					lineHeight: 13,
					style: "normal",
					color: "#9f9f9f",
				},
				shadowSize: 0
			},
			xaxis: {
				tickColor: '#fff',
				tickDecimals: 0,
				font :{
					lineHeight: 13,
					style: "normal",
					color: "#9f9f9f"
				},
				ticks:$tick_data,
				shadowSize: 0,
			},
			legend:{
				container: '#div-flot-traffic-legends',
				backgroundOpacity: 0.5,
				noColumns: 0,
				backgroundColor: '#fff',
				lineWidth: 0,
				labelBoxBorderColor: '#fff'
			}
		};		

		var options = {
            series: {
                bars: {
                    show: true
                }
            },
            bars: {
                align: "center",
				barWidth: 0.2,
				fill:1,
            },
            xaxis: {
                axisLabelUseCanvas: true,
                axisLabelFontSizePixels: 12,
                axisLabelFontFamily: 'Verdana, Arial',
                axisLabelPadding: 10,
                ticks: $tick_data,
            },
            yaxis: {
                axisLabelUseCanvas: true,
                axisLabelFontSizePixels: 12,
                axisLabelFontFamily: 'Verdana, Arial',
                axisLabelPadding: 3,
                tickFormatter: function (v, axis) {
                    return v;
                }
            },
			legend:{
				container: '#div-flot-traffic-legends',
				backgroundOpacity: 0.5,
				noColumns: 0,
				backgroundColor: '#fff',
				lineWidth: 0,
				labelBoxBorderColor: '#fff'
			},
            grid: {
                hoverable: true,
				borderWidth: 1,
				borderColor: '#EDF5FF',
                backgroundColor: { colors: ["#ffffff", "#EDF5FF"] }
            }
        };
		$.plot($('#div-flot-traffic'), $chart_data, options);

	    $('#div-flot-traffic').bind('plothover', function (event, pos, item) {
            if (item) {
            	var x = item.datapoint[0],
					y = item.datapoint[1];
				$('.flot-tooltip').html(item.series.label + ' is ' + y).css({top: item.pageY+5, left: item.pageX+5}).show();
				console.log("show");
			}
            else {
                $('.flot-tooltip').hide();
            }
        });
	}

	function setup_flot_efficiency($efficiency_data,$total_data) {
		var $chart_data = [
			{
				label: 'Efficiency',
				data: $efficiency_data,
				color: '#C07512',
				yaxis: 1
			},
			{
				label: 'Total',
				data: $total_data,
				color: '#03A9F4',
				yaxis: 2
			}      
		];
		var $line_chart_options = {
			series: {
				lines: {
					show: true,
					barWidth: 0.05,
					fill: 0
				},
				points:{
					show:true,
				}			
			},
			shadowSize: 0.1,
            grid: {
                hoverable: true,
				borderWidth: 1,
				borderColor: '#EDF5FF',
                //backgroundColor: { colors: ["#ffffff", "#EDF5FF"] }
			},

			yaxes: [ 
				{ 
					tickColor: '#edf9fc',
					max: 100,
					font :{
						lineHeight: 13,
						style: 'normal',
						color: '#C07512',
					},					
				}, {
					font :{
						lineHeight: 13,
						style: 'normal',
						color: '#03A9F4',
					},					
					// align if we are to the right
					alignTicksWithAxis:1,
					position: 'right',
				} ],
			xaxis:{
				tickColor: '#fff',
				font :{
					lineHeight: 13,
					style: 'normal',
					color: '#9f9f9f'
				},
				shadowSize : 0,
				tickDecimals : 0,
				tickFormatter : function (val, axis) {
					return $efficiency_data[val][2];
        		},
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
				
		$.plot($('#div-flot-efficiency'), $chart_data, $line_chart_options);

        $('#div-flot-efficiency').bind('plothover', function (event, pos, item) {
            if (item) {
				if (item.seriesIndex==0) {
					var x = item.datapoint[0],
					y = item.datapoint[1].toFixed(2);
	                $('.flot-tooltip').html(item.series.label + ' ratio is ' + y + '%').css({top: item.pageY+5, left: item.pageX+5}).show();
				} else {
					var x = item.datapoint[0],
					y = item.datapoint[1];
	                $('.flot-tooltip').html(item.series.label + ' exit is ' + y).css({top: item.pageY+5, left: item.pageX+5}).show();
				}
            }
            else {
                $('.flot-tooltip').hide();
            }
        });
	}

	function setup_flot_line($place_holder,$data){
		var $stats_chart_data = [{
        	stack: true,
        	color: '#fff',
        	lines: {
            	show: true,
            	fill: 1,
            	fillColor: 'rgba(255,255,255,0.2)'
			},
			data:$data,
		}];
		var $stats_chart_options = {
			series: {
				shadowSize: 0,
				curvedLines: {
					apply: true,
					active: true,
					monotonicFit: true
				},
				lines: {
					show: false,
					lineWidth: 0
				}
			},
			grid: {
				borderWidth: 0,
				labelMargin:10,
				hoverable: false,
				clickable: false,
				mouseActiveRadius:6

			},
			xaxis: {
				tickDecimals: 0,
				ticks: false
			},

			yaxis: {
				tickDecimals: 0,
				ticks: false
			},

			legend: {
				show: false
			}
		};
		
		$.plot($place_holder,$stats_chart_data, $stats_chart_options);
	}

	function setup_flot_pie($place_holder,$legends_place_holder,$data) {
		var $pie_option = {
            series: {
                pie: {
                    innerRadius: 0.5,
                    show: true,
                    stroke: { 
                        width: 1
                    },
					label: {
                		show: true,
						formatter: function(label, slice) {
							var $i=0,$value = 0;
							for($i=0;$i<$data.length;$i++) {
								if ($data[$i].label==label) {
									$value = $data[$i].data;
								}
							}
							if ($legends_place_holder=='#div-flot-revenue-legends') {
								$value = 'RM'+$value;
							}
							return "<div style='font-size:x-small;text-align:center;padding:2px;color:" + slice.color + ";'>" + label + "<br/>" + $value+"("+ Math.round(slice.percent) + "%)</div>";
						},
            		},
                }
            },
			grid: {
        		hoverable: true
    		},
			legend:{
				container:$legends_place_holder,
				backgroundOpacity: 0.5,
				noColumns: 0,
				backgroundColor: '#fff',
				lineWidth: 0,
				labelBoxBorderColor: '#fff'
			}			
        };
		$.plot($place_holder,$data, $pie_option);
	}

	function setup_data($data) {
		// setup the title
		var $sub_title = "Show today's system summary report. Cut off time:"+$data.cut_off_time+",last refreshed at: ";
		$sub_title += "<span class=\"badge badge-pill badge-info\">";
		$sub_title += $data.datetime;
		$sub_title += "</span>";
		$('#small-sub-title').html($sub_title);
		// setup the total summary statisitc data
		$('#h2-car-in-park-cut-off').html($data.total_car_in_park_at_cut);
		$('#h2-total-entry').html($data.traffic_summary.total_entry);
		$('#h2-total-exit').html($data.traffic_summary.total_exit);
		$('#h2-car-in-park').html(format_money($data.total_car_in_park,0,""));
		$('#h2-revenue').html(format_money($data.payment_summary.total/100));
		// calc the traffic data
		var $i=0;
		var $entry_data=[],$exit_data=[],$tick_data=[];
		for ($i=0;$i<$data.traffic_records.length;$i++) {
			var $record = $data.traffic_records[$i];
			$entry_data.push([$i,$record.total_entry]);
			$exit_data.push([$i,$record.total_exit]);
			$tick_data.push([$i,$record.dt_str]);
		}
		setup_flot_line($('#div-stats-entry'),$entry_data);
		setup_flot_line($('#div-stats-exit'),$exit_data);
		setup_flot_traffic($entry_data,$exit_data,$tick_data);

		var $parking_type_data = [
			{data:$data.traffic_summary.season_entry , color: '#C07512', label: 'Season'},
			{data:$data.traffic_summary.visitor_entry , color: '#03A9F4', label: 'Visitor'},
		];
		setup_flot_pie($('#div-flot-parking-type'),'#div-flot-parking-type-legends',$parking_type_data);
		var $revenue_data = [
			{data:$data.payment_summary.kiple/100 , color: '#C07512', label: 'eWallet'},
			{data:($data.payment_summary.total-$data.payment_summary.kiple)/100, color: '#03A9F4', label: 'Cash'},
		];
		setup_flot_pie($('#div-flot-revenue'),'#div-flot-revenue-legends',$revenue_data);
		var $efficiency_data = [];
		var $total_data = [];
		for ($i=0;$i<$data.daily_efficiency.length;$i++) {
			var $record = $data.daily_efficiency[$i];
			var $total = $record.total_leave-$record.force_leave-$record.mark_leave;
			$total_data.push([$i,$total,$record.dt]);
			$efficiency_data.push([$i,($record.normal_leave/$total)*100,$record.dt]);
		}
		setup_flot_efficiency($efficiency_data,$total_data);
	}

	var $refres_first_time = true;
	var $ajax_refresh = null;
	function refresh() {
	    if ($ajax_refresh != null) {
    	    $ajax_refresh.abort();
        	$ajax_refresh = null;
    	}
    	$ajax_refresh = $.ajax({
            method: "GET",
            url: "home_dashboard/today_data"
        }).done(function($resp) {
            if ($resp.code==0) {
				setup_data($resp.data);
				if ($refres_first_time==false) {
					show_notify("refresh data succeeded.","success");				
				}
            } else {
				console.log($resp);
				if ($refres_first_time==false) {
					show_notify("refresh data failed.","danger");
				}
            }
        })
        .fail(function($resp) {
            console.log($resp);
			if ($refres_first_time==false) {
				show_notify("something get wrong, please try again.","danger");
			}
        })
        .always(function() {
			$ajax_refresh = null;
			$refres_first_time = false;
        });
	}

	$(document).ready(function(){
		var $car_data = [],$revenue_data = []; 
		// generate random data;
		var $i=0;
		for($i=0;$i<24;$i++) {
			$car_data.push([$i,Math.random()*100]);
			$revenue_data.push([$i,Math.random()*100]);
		}
		setup_flot_line($('#div-stats-car'),$car_data);
		setup_flot_line($('#div-stats-car-cut'),$revenue_data);
		$('<div class="flot-tooltip"></div>').appendTo('body');
		refresh();
	});


</script>
@endsection

