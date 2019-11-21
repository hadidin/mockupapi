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
        <h1>Hourly Traffic Report</h1>
        <small id="small-sub-title"></small>
        <div class="actions">
        </div>
    </header>
    
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-sm-10">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="zmdi zmdi-calendar"></i>&nbsp;&nbsp;select cut off time:</span>
                        </div>
                        <input type="datetime-local" class="form-control hidden-md-up" placeholder="select a date &amp; time">
                        <input id="input-datetime" type="text" class="form-control datetime-picker hidden-sm-down flatpickr-input active" placeholder="select a date" readonly="readonly">
                    </div>
                </div>
                <div class="col-sm-2">
                    <button id="button-search" onclick="on_button_search_clicked()" class="btn btn-light btn--icon-text"><i class="zmdi zmdi-search"></i> Search</button>
                </div>
            </div>
            <hr>
            <table class="table table-bordered table-striped" id="table-report">
                <thead>
                    <tr>
                        <th>Hour</th>
                        <th>Season Entry</th>
                        <th>Visitor Entry</th>
                        <th>Total Entry</th>
                        <th>Season Exit</th>
                        <th>Visitor Exit</th>
                        <th>Total Exit</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
    <script>

function on_button_search_clicked() {
    var $table_report = $('#table-report').DataTable();
    var $url = 'report_hourly_traffic_data?datetime='+$('#input-datetime').val();
    $table_report.ajax.url($url).load();
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


$(document).ready(function() {
    // 05:00 is the cut off time
    var now = date_format(new Date(),"yyyy-MM-dd")+'{{ $cut_off_time }}';
    $('#input-datetime').val(now);
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
                buttons: [{
                    extend: "csvHtml5",
                    title: function() {
                        return 'HourlyTrafficReport_' + $('#input-datetime').val();
                    }
                }, {
                    extend: "print",
                    title: function() {
                        return 'HourlyTrafficReport_' + $('#input-datetime').val();
                    }
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
                "columns": [{
                    "data": "dt_str",
                    "render": function ( data, type, row, meta ) {
                        return '<span>'+data+'</span>';
                    }
                }, {
                    "data": "season_entry"
                }, {
                    "data": "visitor_entry"
                }, {
                    "data": "total_entry"
                }, {
                    "data": "season_exit"
                }, {
                    "data": "visitor_exit"
                }, {
                    "data": "total_exit"
                }],
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
            on_button_search_clicked();
        });
    </script>

@endsection


