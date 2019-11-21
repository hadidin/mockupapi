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
        <h1>Revenue Report</h1>
        <small id="small-sub-title"></small>
        <div class="actions">
        </div>
    </header>
    
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-sm-5">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="zmdi zmdi-calendar"></i>&nbsp;&nbsp;start date:</span>
                        </div>
                        <input type="datetime-local" class="form-control hidden-md-up" placeholder="select a date">
                        <input id="input-start-date" type="text" class="form-control date-picker hidden-sm-down flatpickr-input active" placeholder="select a date" readonly="readonly">
                    </div>
                </div>
                <div class="col-sm-5">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="zmdi zmdi-calendar"></i>&nbsp;&nbsp;end date:</span>
                        </div>
                        <input type="datetime-local" class="form-control hidden-md-up" placeholder="select a date">
                        <input id="input-end-date" type="text" class="form-control date-picker hidden-sm-down flatpickr-input active" placeholder="select a date" readonly="readonly">
                    </div>
                </div>
                <div class="col-sm-2">
                    <button id="button-search" onclick="search()" class="btn btn-light btn--icon-text"><i class="zmdi zmdi-search"></i> Search</button>
                </div>
            </div>
            <hr>
            
            <div class="row">               
                <div class="col-sm-12">
                    <div id="div-flot-revenue-legends" class="flot-chart-legends"></div>
                    <div id="div-flot-revenue" class="flot-chart" style="height: 180px;"></div>
                </div> 
            </div>            
            <table class="table table-bordered table-striped" id="table-report">
                <thead>
                    <tr>
                        <th>Datetime</th>
                        <th>Cash(RM)</th>
                        <th>eWallet(RM)</th>
                        <th>Total(RM)</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
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
    
    function date_format(dt,fmt) {
        var o = {   
            "M+" : dt.getMonth()+1,
            "d+" : dt.getDate(),
            "h+" : dt.getHours(),
            "m+" : dt.getMinutes(),
            "s+" : dt.getSeconds(),
            "q+" : Math.floor((dt.getMonth()+3)/3),
            "S"  : dt.getMilliseconds()
        };   
        if(/(y+)/.test(fmt))   
            fmt=fmt.replace(RegExp.$1, (dt.getFullYear()+"").substr(4 - RegExp.$1.length));   
        for(var k in o)   
            if(new RegExp("("+ k +")").test(fmt))   
        fmt = fmt.replace(RegExp.$1, (RegExp.$1.length==1) ? (o[k]) : (("00"+ o[k]).substr((""+ o[k]).length)));   
        return fmt;   
    }

    function setup_data($records) {
        var $i=0,$ewallet_data=[],$cash_data=[],$tick_data=[];
        var $ewallet_total = 0,$cash_total=0;
		for($i=0;$i<$records.length;$i++) {
            var $record = $records[$i];
            $cash_data.push([$i, $record.cash]);
            $cash_total += $record.cash;
			$ewallet_data.push([$i, $record.ewallet]);
            $ewallet_total += $record.ewallet;
			$tick_data.push([$i,$record.dt]);
        }
        setup_flot_revenue($cash_data,$ewallet_data,$tick_data,$cash_total,$ewallet_total);
        var $table_report = $('#table-report').DataTable();
        $table_report.clear();
        $table_report.rows.add($records);
        $table_report.draw();
    }

	var $refresh_first_time = true;
	var $ajax_search = null;
	function search() {
	    if ($ajax_search != null) {
    	    $ajax_search.abort();
        	$ajax_search = null;
    	}
    	$ajax_search = $.ajax({
            method: "GET",
            url: "report_revenue_data",
            data: { start_date:$('#input-start-date').val(),end_date:$('#input-end-date').val()}
        }).done(function($resp) {
            if ($resp.code==0) {
				setup_data($resp.data);
				if ($refresh_first_time==false) {
					show_notify("search succeeded.","success");				
				}
            } else {
				console.log($resp);
				if ($refresh_first_time==false) {
					show_notify("search failed.","danger");
				}
            }
        })
        .fail(function($resp) {
            console.log($resp);
			if ($refresh_first_time==false) {
				show_notify("something get wrong, please try again.","danger");
			}
        })
        .always(function() {
			$ajax_search = null;
			$refresh_first_time = false;
        });
    }

    function setup_table_revenue() {
        $("#table-report").DataTable({
            autoWidth: true,
            bFilter:false,
            processing: true,
            paging : false,
            sorting : false,
            deferRender: true,
            serverSide: false,
            responsive: true,
            autoWidth:true,
            language: {
                searchPlaceholder: "Filter in records..."
            },
            sDom: '<"dataTables__top"lfB>rt<"dataTables__bottom"ip><"clear">',
            buttons: [
                {extend: "csvHtml5",title: function() {return 'RevenueReport_' + $('#input-stat-date').val();}}, 
                {extend: "print",title: function() {return 'RevenueReport_' + $('#input-start-date').val();}
            }],
            initComplete: function(a, b) {
                var $buttonHtml = '<div class="dataTables_buttons hidden-sm-down actions">';
                $buttonHtml += '<span class="actions__item zmdi zmdi-print" data-table-action="print" />';
                $buttonHtml += '<span class="actions__item zmdi zmdi-fullscreen" data-table-action="fullscreen"/>';
                $buttonHtml += '<span class="actions__item zmdi zmdi-download" data-table-action="csv"/>';
                //$buttonHtml += '<span class="actions__item zmdi zmdi-refresh" data-table-action="refresh"/>';
                $buttonHtml += '</div>';
                    $(this).closest(".dataTables_wrapper").find(".dataTables__top").prepend($buttonHtml)
                },
            "columns": [
                {"data": "dt"}, 
                {"data": "cash"}, 
                {"data": "ewallet"}, 
                {"data": "total"}
            ],
        });
        
        $(".dataTables_filter input[type=search]").focus(function() {
            $(this).closest(".dataTables_filter").addClass("dataTables_filter--toggled")
        });
                
        $(".dataTables_filter input[type=search]").blur(function() {
            $(this).closest(".dataTables_filter").removeClass("dataTables_filter--toggled")
        });
                // Data table buttons
        $('body').on('click', '[data-table-action]', function(e) {
            e.preventDefault();
            var exportFormat = $(this).data('table-action');

            if (exportFormat === 'csv') {
                $(this).closest('.dataTables_wrapper').find('.buttons-csv').trigger('click');
            }
            if (exportFormat === 'print') {
                $(this).closest('.dataTables_wrapper').find('.buttons-print').trigger('click');
            }
            if (exportFormat === 'refresh') {
                alert('refresh');
            }
            if (exportFormat === 'fullscreen') {
                var parentCard = $(this).closest('.card');
                if (parentCard.hasClass('card--fullscreen')) {
                    parentCard.removeClass('card--fullscreen');
                    $('body').removeClass('data-table-toggled');
                } else {
                    parentCard.addClass('card--fullscreen')
                    $('body').addClass('data-table-toggled');
                }
            }
        });
    }
    
    function setup_flot_revenue($cash_data,$ewallet_data,$tick_data,$cash_total,$ewallet_total){
        var $chart_data = [
			{
				label: 'Cash(RM'+$cash_total+')',
				data: $cash_data,
				color: '#32c787',
				bars: {
                	order: 0
            	}
			},
			{
				label: 'eWallet(RM'+$ewallet_total+')',
				data: $ewallet_data,
				color: '#03A9F4',
				bars: {
                	order: 1
            	}
			}   
        ];
        
        var $chart_options = {
			series: {
				lines: {
					show: true
				},
				points: {
					show: true
				}
			},
            grid: {
				borderWidth: 1,
				borderColor: '#c0c0c0',
				show : true,
				hoverable : true
			},
			yaxis: {
				min: 0
			},
			xaxis: {
				ticks: $tick_data
			},
			legend:{
				container: '#div-flot-revenue-legends',
				backgroundOpacity: 0.5,
				noColumns: 0,
				backgroundColor: '#fff',
				lineWidth: 0,
				labelBoxBorderColor: '#fff'
			},
		};

        var plot = $.plot("#div-flot-revenue", $chart_data, $chart_options);  
        $('#div-flot-revenue').bind('plothover', function (event, pos, item) {
            if (item) {
            	var x = item.datapoint[0],
                    y = item.datapoint[1];
                var label = item.series.label;
                label = label.substring(0,label.indexOf('('));
				$('.flot-tooltip').html(label + ' is RM' + y).css({top: item.pageY+5, left: item.pageX+5}).show();
				console.log("show");
			}
            else {
                $('.flot-tooltip').hide();
            }
        });
    }
    $(document).ready(function() {
		$('<div class="flot-tooltip"></div>').appendTo('body');
        var $now = date_format(new Date(),"yyyy-MM-dd");
        $('#input-start-date').val($now);
        $('#input-end-date').val($now);
        setup_table_revenue();
        search();
    });
    </script>
@endsection


