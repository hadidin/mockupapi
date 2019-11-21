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
        <h1>Daily Car In Park Report</h1>
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
                        <input id="input-datetime" type="text" class="form-control datetime-picker hidden-sm-down flatpickr-input active" placeholder="Pick a date" readonly="readonly">
                    </div>
                </div>
                <div class="col-sm-2">
                    <button id="button-search" onclick="on_button_search_clicked()" class="btn btn-light btn--icon-text"><i class="zmdi zmdi-search"></i> Search</button>
                </div>
            </div>
            <hr>
            <table class="table table-bordered table-striped display nowrap" width="100%"  id="table-report">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Entry Time</th>
                        <th>Plate No</th>
                        <th>Parking Type</th>
                        <th>Season Card</th>
                        <th>e-ticket ID</th>
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
    var $url = 'report_daily_car_in_park_data?datetime='+$('#input-datetime').val();
    console.log($url);
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
    var now = date_format(new Date(),"yyyy-MM-dd")+'{{ $cut_off_time }}';
    $('#input-datetime').val(now);
            $("#table-report").DataTable({
                autoWidth: true,
                bFilter:true,
                processing: true,
                deferRender: true,
                paging: true,
                serverSide: false,
                ordering: true,
                responsive: true,
                lengthMenu: [
                    [15, 30, 45, -1],
                    ["15 Rows", "30 Rows", "45 Rows", "Everything"]
                ],
                language: {
                    searchPlaceholder: "Filter in records..."
                },
                sDom: '<"dataTables__top"lfB>rt<"dataTables__bottom"ip><"clear">',
                buttons: [{
                    extend: "csvHtml5",
                    title: function() {
                        return 'DialyCarInSiteReport_' + $('#input-datetime').val();
                    }
                }, {
                    extend: "print",
                    title: function() {
                        return 'DialyCarInSiteReport_' + $('#input-datetime').val();
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
                    "data": "id"
                }, {
                    "data": "entry_time"
                }, {
                    "data": "plate_no"
                }, {
                    "data": "parking_type"
                }, {
                    "data": "season_card"
                }, {
                    "data": "visitor_ticket"
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

                if (exportFormat === 'excel') {
                    $(this).closest('.dataTables_wrapper').find('.buttons-excel').trigger('click');
                }
                if (exportFormat === 'csv') {
                    $(this).closest('.dataTables_wrapper').find('.buttons-csv').trigger('click');
                }
                if (exportFormat === 'print') {
                    $(this).closest('.dataTables_wrapper').find('.buttons-print').trigger('click');
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


