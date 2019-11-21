@extends('parts.template')

@section('title')
Home
@endsection

@section('style')
<style>
	ul { 
        list-style-type: none;
	}
	.active{
		background-color:#e8e9ea;
	}
	th,td {
        white-space: nowrap;
	}
</style>
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
        <h1>Parking History</h1>
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
                            <span class="input-group-text">Start Datetime</span>
                        </div>
                        <input type="datetime-local" class="form-control hidden-md-up" placeholder="select a date">
                        <input id="input-start-date" type="text" class="form-control datetime-picker hidden-sm-down flatpickr-input active" placeholder="select a date" readonly="readonly">
                    </div>
                </div>
                <div class="col-sm-5">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">End Datetime</span>
                        </div>
                        <input type="datetime-local" class="form-control hidden-md-up" placeholder="select a date">
                        <input id="input-end-date" type="text" class="form-control datetime-picker hidden-sm-down flatpickr-input active" placeholder="select a date" readonly="readonly">
                    </div>
                </div>
            </div>
            <div class="row" style="margin-top:12px">
                <div class="col-sm-5">
                    <select class="select2" id="entry_lanes" name="entry_lanes[]" multiple data-placeholder="Entry Lanes">
                        @foreach ($lane_list as $lane)
                        @if ($lane->in_out_flag==0 || $lane->in_out_flag==2)
                        <option value="{{$lane->id}}">{{$lane->name}}</option>
                        @endif 
                        @endforeach   
                    </select>
                </div>
                <div class="col-sm-5">
                    <select class="select2" id="exit_lanes" name="exit_lanes[]" multiple data-placeholder="Exit Lanes">
                        @foreach ($lane_list as $lane)
                        @if ($lane->in_out_flag==1 || $lane->in_out_flag==3)
                        <option value="{{$lane->id}}">{{$lane->name}}</option>
                        @endif 
                        @endforeach   
                    </select>
                </div>
            </div>
            <div class="row" style="margin-top:12px">
                <div class="col-sm-4">
                    <select class="select2" class="form-control" id="parking_types" name="parking_types[]" data-placeholder="Parking Type" multiple="multiple">
                        <option value="0">Visitor</option>
                        <option value="1">Season</option>
                        <option value="2">White List</option>
                    </select>                   
                </div>
                <div class="col-sm-3">
                    <select class="select2" class="form-control" id="leave_types" name="leave_types[]" data-placeholder="Leave Type" multiple="multiple">
                        <option value="0">Auto</option>
                        <option value="1">Manual</option>
                        <option value="2">Forced</option>
                        <option value="3">Marked</option>
                    </select>                   
                </div>
                <div class="col-sm-3">
                    <div class="input-group">
                        <input id="search_content" type="text" class="form-control" placeholder="Plate No">
                        <i class="form-group__bar"></i>
                    </div>                
                </div>
                <div class="col-sm-2">
                    <button id="button-search" onclick="search()" class="btn btn-light btn--icon-text"><i class="zmdi zmdi-search"></i> Search</button>
                </div>
            </div>
            <!--
            <div class="row" style="margin-top:12px">
                <div class="col-sm-4">
                    <select class="select2" class="form-control" id="payment_method" name="payment_method[]" data-placeholder="Payment Method" multiple="multiple">
                        <option value="kiple">ewallet</option>
                        <option value="maxpark">cash</option>
                    </select>                   
                </div>
                <div class="col-sm-6">
                    <select class="select2" class="form-control" id="payment_status" name="payment_status[]" data-placeholder="Payment Status" multiple="multiple">
                        <option value="0">None</option>
                        <option value="1">Sending</option>
                        <option value="2">Success</option>
                        <option value="3">Failed</option>
                        <option value="4">Refunding</option>
                        <option value="5">Refund Success</option>
                        <option value="6">Refund Failed</option>
                    </select>                   
                </div>
            </div>
            -->
            <hr>      
            <table class="table table-bordered table-striped" id="table-history">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Plate No</th>
                        <th>Plate No2</th>
                        <th>Parking Type</th>
                        <th>Entry Time</th>
                        <th>Entry Lane</th>
                        <th>Exit Time</th>
                        <th>Exit Lane</th>
                        <th>Leave Type</th>
                        <th>Payment Method</th>
                        <th>Payment Amount</th>
                        <th>Payment Status</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
@endsection



@section('script')
<script>
    'use strict';    
    /**
     * date time format function
     */
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

    function get_leave_type_string($leave_type) {
        switch($leave_type) {
            case 0: return 'Auto';
            case 1: return 'Manual';
            case 2: return 'Forced';
            case 3: return 'Marked';
        }
        return '';
    }

    function get_payment_status_string($status) {
        switch($status) {
            case 0: return 'None';
            case 1: return 'Sending';
            case 2: return 'Success';
            case 3: return 'Failed';
            case 4: return 'Refunding';
            case 5: return 'Refund Success';
            case 6: return 'Refund Failed';
        }
        return '';
    }
   
    /**
     * get lane name string according to lane id
     */
    function get_lane_string($lane_id) {
        var $i,$lane_names = [
            @foreach ($lane_list as $lane)
            [{{ $lane->id }},'{{ $lane->name }}'],
            @endforeach            
        ];
        for($i=0;$i<$lane_names.length;$i++) {
            var $record = $lane_names[$i];
            if($lane_id==$record[0]) {
                return $record[1];
            }
        }        
    }    

    /**
     * setup the data table
     */
    function setup_table() {
        $("#table-history").DataTable({
            autoWidth: true,
            bFilter:false,
            processing: true,
            paging : true,
            sorting: [[ 0, "desc" ]],
            deferRender: true,
            serverSide: true,
            responsive: true,
            autoWidth:true,
            scrollX:true,
            sDom: '<"dataTables__top"lfB>rt<"dataTables__bottom"ip><"clear">',
            buttons: [
                {extend: "csvHtml5",title: function() {return 'EntryLog_' + $('#input-stat-date').val();}}, 
            ],
            initComplete: function(a, b) {
                var $buttonHtml = '<div class="dataTables_buttons hidden-sm-down actions">';
                $buttonHtml += '<span class="actions__item zmdi zmdi-fullscreen" data-table-action="fullscreen"/>';
                $buttonHtml += '<span class="actions__item zmdi zmdi-download" data-table-action="csv"/>';
                $buttonHtml += '<span class="actions__item zmdi zmdi-eye" data-table-action="reviewresult"/>';
                $buttonHtml += '</div>';
                    $(this).closest(".dataTables_wrapper").find(".dataTables__top").prepend($buttonHtml)
            },
            "columns": [
                {"data": "id"}, 
                {"data": "plate_no"},
                {"data": "plate_no2"},
                {"data": "parking_type","orderable":true,"render": function ( data, type, row, meta ) 
                    {
                        if (row.plate_no=='_NONE_') {
                            return '';
                        }
                        if (data==1) {return 'season'} 
                        if (data==2) {return 'white_list'} 
                        return 'visitor';
                    }
                },                
                {"data": "entry_time"},
                {"data": "entry_lane","orderable":false,"render": function ( data, type, row, meta ) 
                    {
                        return get_lane_string(data);                   
                    }
                },                      
                {"data": "exit_time"},
                {"data": "exit_lane","orderable":false,"render": function ( data, type, row, meta ) 
                    {
                        return get_lane_string(data);                   
                    }
                },                      
                {"data": "leave_type","orderable":false,"render": function ( data, type, row, meta ) 
                    {
                        return get_leave_type_string(data);                   
                    }
                },                
                {"data": "payment_method"},
                {"data": "payment_amount","orderable":false,"render": function ( data, type, row, meta ) 
                    {
                        if (data==null || data=='') {
                            return data;
                        }
                        return (data/100).toFixed(2);                   
                    }
                },                      
                {"data": "payment_status","orderable":false,"render": function ( data, type, row, meta ) 
                    {
                        return get_payment_status_string(data);                   
                    }
                },                
            ],
            "ajax":{
                "url": "parking_history/search",
                "dataType": "json",
                'headers': {
                    'Accept':'application/json; charset=utf-8',
                },
                "type": "POST", 
                "data": function ( d ) {
                    return $.extend( {}, d, {
                        _token: "{{csrf_token()}}",
                        "start_date": $('#input-start-date').val(),
                        "end_date": $('#input-end-date').val(),
                        "entry_lanes": $('#entry_lanes').val(),
                        "exit_lanes": $('#exit_lanes').val(),
                        "parking_types": $('#parking_types').val(),
                        "leave_types": $('#leave_types').val(),
                        "search_content": $('#search_content').val(),
                    } );
                }
            },
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
            var action = $(this).data('table-action');

            if (action === 'csv') {
                download();
            }
            if (action === 'reviewresult') {
                req_result();
            }
            if (action === 'fullscreen') {
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
        // select change event
        $('#table-history').on('change', 'td select', function (){
            var $value = $(this).val();
            var $values = $value.split('|');
            update_review_result($values[0],'review_flag',$values[1]);
        });
    }    
    /**
     * search records according to the inputs
     */
    function search() {
        $("#table-history").DataTable().ajax.reload();
    }
      
    /**
     * download the records
     */
    function download() {
        var $params = '?start_date='+$('#input-start-date').val();
        $params += '&end_date='+$('#input-end-date').val();
        $params += '&entry_lanes='+$('#entry_lanes').val();
        $params += '&exit_lanes='+$('#exit_lanes').val();
        $params += '&parking_types='+$('#parking_types').val();
        $params += '&leave_types='+$('#leave_types').val();
        $params += '&search_content='+$('#search_content').val();
        window.open('parking_history/download'+$params, '_blank');        
    }    
  
    $(document).ready(function() {
        $('.datetime-picker').flatpickr({
            dateFormat: 'Y-m-d H:i:S',
            enableTime: true,
            enableSeconds:true
        });
        var $now = date_format(new Date(),"yyyy-MM-dd");
        // date is today by default
        $('#input-start-date').val($now + " 00:00:00");
        $('#input-end-date').val($now + " 23:59:59");
        setup_table();
    });        
</script>
@endsection
