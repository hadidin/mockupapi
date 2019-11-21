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
        <h1>Transaction Report</h1>
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
                        <input type="datetime-local" class="form-control hidden-md-up" placeholder="select a date &amp; time">
                        <input id="input-start-datetime" type="text" class="form-control datetime-picker hidden-sm-down flatpickr-input active" placeholder="Pick a date" readonly="readonly">
                    </div>
                </div>
                <div class="col-sm-5">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="zmdi zmdi-calendar"></i>&nbsp;&nbsp;end date:</span>
                        </div>
                        <input type="datetime-local" class="form-control hidden-md-up" placeholder="select a date &amp; time">
                        <input id="input-end-datetime" type="text" class="form-control datetime-picker hidden-sm-down flatpickr-input active" placeholder="Pick a date" readonly="readonly">
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
                        <th>Ticket Id</th>
                        <th>Sub Ticket</th>
                        <th>Entry Time</th>
                        <th>Status</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Date</th>
                        <th>Reference Id</th>
                        <th>Is Used</th>
                        <th>Exit Time</th>
                        <th>Grace</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot class="report_foot">
              <tr>
                <th></th>
                <th></th>
                <th></th>
                <th id="total"></th>
                <th id="totalAmount"></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
              </tr>
            </tfoot>
            </table>
        </div>
        <input type="hidden" class="totalAmount" value="" />
    </div>
    <script>

function on_button_search_clicked() {
    var $table_report = $('#table-report').DataTable();
    var $url = 'report_payment_transaction_data?startdate='+$('#input-start-datetime').val()+'&enddate='+$('#input-end-datetime').val();
    console.log($url);
    $table_report.ajax.url($url).load();
    showTotalAmount();
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

    var date = new Date();
    date.setDate(date.getDate() - 1);
    var startDate = date_format(date,"yyyy-MM-dd")+'{{ $cut_off_time }}'; 

    $('#input-start-datetime').val(startDate);
    $('#input-end-datetime').val(now);

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
                        return 'TransactionReport_' + $('#input-start-datetime').val() + '- ' + $('#input-end-datetime').val();
                    }
                }, {
                    extend: "print",
                    title: function() {
                        return 'TransactionReport_' + $('#input-start-datetime').val() + '- ' + $('#input-end-datetime').val();
                    }
                }],
                initComplete: function(a, b) {
                    var $buttonHtml = '<div class="dataTables_buttons hidden-sm-down actions">';
                    $buttonHtml += '<span class="actions__item zmdi zmdi-print" data-table-action="print" />';
                    $buttonHtml += '<span class="actions__item zmdi zmdi-fullscreen" data-table-action="fullscreen"/>';
                    $buttonHtml += '<span class="actions__item zmdi zmdi-download" data-table-action="csv"/>';
                    //$buttonHtml += '<span class="actions__item zmdi zmdi-refresh" data-table-action="refresh"/>';
                    $buttonHtml += '</div>';
                    $(this).closest(".dataTables_wrapper").find(".dataTables__top").prepend($buttonHtml);
                    showTotalAmount();
                },
                "columns": [{
                    "data": "ticket_id"
                }, {
                    "data": "subticket"
                }, {
                    "data": "start_time"
                }, {
                    "data": "status"
                }, {
                    "data": "amount"
                },
                {
                    "data": "method"
                }, {
                    "data": "trx_date"
                }, {
                    "data": "external_ref_id"
                }, {
                    "data": "is_used"
                }, {
                    "data": "used_time"
                }, {
                    "data": "grace_period"
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

        function showTotalAmount() {

            $url = 'report_payment_transaction_data?action=total&startdate='+$('#input-start-datetime').val()+'&enddate='+$('#input-end-datetime').val();

            $.ajax({
                "url": $url,
                "dataType": "json",
                'headers': {
                'Accept':'application/json; charset=utf-8',
                },
                "type": "GET",
                success: function(result){             

                    $('#total').html('Total');
                    $('#totalAmount').html(result.totalamount[0].total);
                    $('.totalAmount').val(result.totalamount[0].total);
                }
            });
        }

    </script>

@endsection


