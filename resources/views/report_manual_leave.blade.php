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
        <h1>Manual Leave Report</h1>
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
                <div class="col-sm-6">
                    <div id="div-flot-legends" class="flot-chart-legends"></div>
                    <div id="div-flot" class="flot-chart" style="height: 300px;"></div>
                </div> 
                <div class="col-sm-6">
                    <table class="table table-bordered table-striped" id="table-report">
                        <thead>
                            <tr>
                                <th>Reason</th>
                                <th>Total Number</th>
                                <th>Percent(%)</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
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
        var $i = 0, $total = 0, $pie_datas = [];        
        for($i=0;$i<$records.length;$i++) {
            var $record = $records[$i];
            // fix the emptry remark issue
            if ($record.manual_leave_remark=='') {
                $record.manual_leave_remark = 'N/A';
            }
            $total += $record.number;
            $pie_datas.push({data:$record.number,label:$record.manual_leave_remark});
        }        
        for($i=0;$i<$records.length;$i++) {
            var $percent = $records[$i].number/$total*100;
            $records[$i].percent = $percent.toFixed(2);
        }
		setup_flot_pie($('#div-flot'),'#div-flot-legends',$pie_datas);
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
            url: "report_manual_leave_data",
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
                {"data": "manual_leave_remark"}, 
                {"data": "number"},
                {"data": "percent"},
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


