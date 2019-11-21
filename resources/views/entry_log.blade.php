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
        <h1>Entry Log</h1>
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
                    <select class="select2" id="lanes" name="lanes[]" multiple data-placeholder="Lanes">
                        @foreach ($lane_list as $lane)
                        <option value="{{$lane->id}}">{{$lane->name}}</option>
                        @endforeach   
                    </select>
                </div>
                <div class="col-sm-5">
                    <select class="select2" class="form-control" id="review_flags" name="review_flags[]" data-placeholder="Reviewed As" multiple="multiple">
                        <option value="0">Unreviewed</option>
                        <option value="1">Correct</option>
                        <option value="2">Wrong</option>
                        <option value="3">Undetected</option>
                        <option value="4">Trigger Timing Incorrect</option>
                        <option value="5">Invalid</option> 
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
            <hr>      
            <table class="table table-bordered table-striped" id="table-entry-log">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Datetime</th>
                        <th>Plate No</th>
                        <th>Plate No2</th>
                        <th>Small Picture</th>
                        <th>Big Picture</th>
                        <th>Reviewed</th>
                        <th width="140px">Adjustment</th>
                        <th>Lane</th>
                        <th>Parking Type</th>
                        <th>Leave Type</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="modal hide fade" id="showImageDialog" tabindex="-1" role="dialog" aria-labelledby="roleModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <img id="imageView" src="" alt="" width="100%">
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-result" tabindex="-1">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="tab-container">
                        <ul class="nav nav-tabs pull-right" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="tab" href="#tab-review-result" role="tab">Review Result</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#tab-efficiency-result" role="tab">Efficiency Result</a>
                            </li>
                        </ul>
                         <div class="tab-content">
                            <div class="tab-pane active fade show" id="tab-review-result" role="tabpanel">
                                <table class="table table-striped" id="table-review-result">
                                </table>                            
                            </div>
                            <div class="tab-pane fade" id="tab-efficiency-result" role="tabpanel">
                                <table class="table table-striped" id="table-efficiency-result">
                                </table>                            
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection



@section('script')
<script>
    'use strict';    
    /**
     * show notify 
     *
     * @param string $message, the notify message content
     * @param string $type, the notify type, can be 'success','warning','danger'
     */
	function show_notify($message,$type) {
        $.notify({message: $message,}, 
            {type: $type,element: 'body',}
            );		
    }
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
            case 3: return 'Marked'
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
     * get result string according to season & visitor check result
     */
    function get_result_string($white_list_check_result,$season_check_result,$visitor_check_result) {
        var $i,$white_list_result_strings = [
            [-1,'default',0],
            [0,'none',0],
            [40,'white list entry not',0],
            [41,'white list entry disabled',0],
            [42,'white list entry expired',0],
            [43,'white list entry success',1],
            [45,'white list exit not',0],
            [46,'white list exit disabled',0],
            [47,'white list exit expired',0],
            [48,'white list exit success',1],
        ];
        for($i=0;$i<$white_list_result_strings.length;$i++) {
            var $record = $white_list_result_strings[$i];
            if($white_list_check_result==$record[0] && $record[2]==1) {
                return $record[1];
            }
        }
        var $i,$season_result_strings = [
            [-1,'default',0],
            [0,'none',0],
            [1,'season entry success',1],
            [2,'season entry expired',1],
            [3,'season entry secondary',1],
            [4,'season entry duplicate number',1],
            [5,'season entry deactive',1],
            [6,'season entry access denied',1],
            [10,'none',1],
            [11,'season no entry',1],
            [12,'season car locked',1],
            [13,'season exit success',1],
        ];
        for($i=0;$i<$season_result_strings.length;$i++) {
            var $record = $season_result_strings[$i];
            if($season_check_result==$record[0] && $record[2]==1) {
                return $record[1];
            }
        }
        var $visitor_result_strings = [
            [-1,'default'],
            [0,'none'],
            [1,'visitor entry success'],
            [2,'visitor entry  duplicate number'],
            [3,'visitor entry sys error'],
            [10,'none'],
            [11,'visitor exit unpaid'],
            [12,'visitor exit ticket used'],
            [13,'visitor exit exceed grace period'],
            [14,'visitor exit success'],
            [15,'visitor sys error'],
            [16,'visitor exit command rejected'],
            [17,'visitor exit ticket not found'],
            [18,'visitor exit comm error'],
            [20,'visitor car locked'],
        ];
        for($i=0;$i<$visitor_result_strings.length;$i++) {
            var $record = $visitor_result_strings[$i];
            if($visitor_check_result==$record[0]) {
                return $record[1];
            }
        }
        return 'N/A['+$season_check_result+','+$visitor_check_result+']';
    }

    /**
     * get review flag html string for the table column
     */
    function get_review_flag_html_string($entry_id,$index) {
        var $str = '<select class="inner form-control">';
        var $i,$values = ['Unreviewed','Correct','Wrong','Undetected','Trigger Incorrect','Invalid'];
        for ($i=0;$i<$values.length;$i++) {
            if ($i==$index) {
                $str += '<option value="'+$entry_id+'|'+$i+'" selected>'+$values[$i]+'</option>';
            } else {
                $str += '<option value="'+$entry_id+'|'+$i+'">'+$values[$i]+'</option>';
            }
        }
        $str += '</select>';
        return $str;
    }

    var $ajax_update = null;
    /**
     * udpate review result 
     * 
     * @param string $entry_id the entry log record id
     * @param string $field the update field, can be 'reviewed_plate_no' or 'review_flag'
     * @param string $value the udpate value
     */
    function update_review_result($entry_id,$field,$value){
        if ($field=='plate_no_reviewed' && $value=='') {
            return ;
        }
        if ($ajax_update != null) {
            $ajax_update.abort();
            $ajax_update = null;
        }
        $ajax_update = $.ajax({
            method: "POST",
            dataType : 'json',
            headers: {
                'Accept':'application/json; charset=utf-8',
            },
            url: "entry_log/update",
            data: { id: $entry_id,field:$field,value:$value },                  
        })
        .beforeSend(function($xhr) {
            console.log('beforesend');
        })
        .done(function($data) {
            var $resp = $data;
            if ($resp.code!=0) {
                show_notify($resp.message,"danger");
            }
        })
        .fail(function($data) {
            if ($data.status==401) {
                show_notify("Unauthenticated. Please re-login.","danger");
                location.reload();
            } else {
                show_notify("Something wrong. Please try again.","danger");
            }
        })
        .always(function() {
            $ajax_update = null;
        });
    }
       
    /**
     * setup the data table
     */
    function setup_table() {
        $("#table-entry-log").DataTable({
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
                {"data": "date_time","orderable":true,"render": function ( data, type, row, meta ) 
                    {
                        return data.replace(' ','<br>');
                    }
                },
                {"data": "plate_no"},
                {"data": "plate_no2"},
                {"data": "small_picture","orderable":false,"searchable":false,"render": function ( data, type, row, meta ) 
                    {
                        return '<a data-toggle="modal" data-id="'+data+'" title="click to view" class="open-AddImageDialog btn-link btn-primary" href="#showImageDialog"><img src="'+data+'" width="100" height="50"/></a>';
		            }
		        },
                {"data": "big_picture","orderable":false,"searchable":false,"render": function ( data, type, row, meta ) 
                    {
                        return '<a data-toggle="modal" data-id="'+data+'" title="click to view" class="open-AddImageDialog btn-link btn-primary" href="#showImageDialog"><img src="'+data+'" width="100" height="50"/></a>';
		            }
		        },
                {"data": "plate_no_reviewed","orderable":true,"render": function ( data, type, row, meta ) 
                    {
                        return '<input style="width:130px" id="input-' + row.id + '" onblur="update_review_result('+row.id+',\'plate_no_reviewed\',this.value)" type="text" value="'+data+'"></input>';
                    }
                },
                {"data": "review_flag","orderable":true,"render": function ( data, type, row, meta ) 
                    {
                        return get_review_flag_html_string(row.id,data);
                    }
                },                     
                {"data": "lane_id","orderable":true,"render": function ( data, type, row, meta ) 
                    {
                        return get_lane_string(data);
                    }
                },
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
                {"data": "leave_type","orderable":true,"render": function ( data, type, row, meta ) 
                    {
                        return get_leave_type_string(data);                   
                    }
                },                
                {"data": "check_result","orderable":false,"render": function ( data, type, row, meta ) 
                    {
                        if (row.plate_no=='_NONE_') {
                            return '';
                        }
                        return get_result_string(row.white_list_check_result,row.season_check_result,row.visitor_check_result);
		            }
                },
                
            ],
            "ajax":{
                "url": "entry_log/search",
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
                        "lanes": $('#lanes').val(),
                        "review_flags": $('#review_flags').val(),
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
        $('#table-entry-log').on('change', 'td select', function (){
            var $value = $(this).val();
            var $values = $value.split('|');
            update_review_result($values[0],'review_flag',$values[1]);
        });
    }    
    /**
     * search records according to the inputs
     */
    function search() {
        $("#table-entry-log").DataTable().ajax.reload();
    }
      
    /**
     * download the records
     */
    function download() {
        var $params = '?start_date='+$('#input-start-date').val();
        $params += '&end_date='+$('#input-end-date').val();
        $params += '&lanes='+$('#lanes').val();
        $params += '&review_flags='+$('#review_flags').val();
        $params += '&parking_types='+$('#parking_types').val();
        $params += '&leave_types='+$('#leave_types').val();
        $params += '&search_content='+$('#search_content').val();
        console.log($params);     
        window.open('entry_log/download'+$params, '_blank');        
    }    
    var $ajax_result = null;
    /**
     * send request to get the review/efficiency result
     */
    function req_result(){
        $('#table-review-result').html('');
        $('#table-efficiency-result').html('');        
        $('#modal-result').modal('show');        

        if ($ajax_result != null) {
            $ajax_result.abort();
            $ajax_result = null;
        }
        $ajax_result = $.ajax({
            method: "POST",
            headers: {
                'Accept':'application/json; charset=utf-8',
            },
            url: "entry_log/result",
            data: {  
                "start_date": $('#input-start-date').val(),
                "end_date": $('#input-end-date').val(),
                "lanes": $('#lanes').val(),
                "review_flags": $('#review_flags').val(),
                "parking_types": $('#parking_types').val(),
                "leave_types": $('#leave_types').val(),
                "search_content": $('#search_content').val(),                
            }
        }).done(function($data) {
            var $resp = $data;
            if ($resp.code!=0) {
                show_notify($resp.message,"danger");
                return;
            }
            show_result_data($resp.data);

        })
        .fail(function($data) {
            if ($data.status==401) {
                show_notify("Unauthenticated. Please re-login.");
                location.reload();
            } else {
                show_notify("Something wrong. Please try again.","danger");
            }
        })
        .always(function() {
            $ajax_result = null;
        });
    }        
    /**
     * show the review result and efficiency result in dialog
     */
    function show_result_data($data) {
        var $html = '<tbody>';
        var $review_records = $data.review;
        var $total = $review_records.total;
        for (var $name in $review_records) {
            if ($review_records.hasOwnProperty($name)) {
                if ($total>0) {
                    $html += '<tr><td>' +$name + '</td><td>' + $review_records[$name] + '</td><td>' + ($review_records[$name]/$total*100).toFixed(2)+'%' + '</td></tr>' 
                } else {
                    $html += '<tr><td>' +$name + '</td><td>' + $review_records[$name] + '</td><td>0.00%' +'</td></tr>' 
                } 
            }
        }
        $html += '</tbody>';
        $('#table-review-result').html($html);
        $html = '<tbody>';
        var $efficiency_records = $data.efficiency;
        $total = $efficiency_records.total;
        for (var $name in $efficiency_records) {
            if ($efficiency_records.hasOwnProperty($name)) {
                if ($total>0) {
                    $html += '<tr><td>' +$name + '</td><td>' + $efficiency_records[$name] + '</td><td>' + ($efficiency_records[$name]/$total*100).toFixed(2)+'%' +'</td></tr>' 
                } else {
                    $html += '<tr><td>' +$name + '</td><td>' + $efficiency_records[$name] + '</td><td>0.00%' +'</td></tr>' 
                } 
            }
        }
        $html += '</tbody>';
        $('#table-efficiency-result').html($html);        
    }

    /**
     * document ready function
     */
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
        // show image dialog when click
        $(document).on("click", ".open-AddImageDialog", function () {
            var myImageId = $(this).data('id');
            $(".modal-content #imageId").val( myImageId );
            document.getElementById("imageView").src = myImageId;
        });        
        setup_table();
    });        
</script>
@endsection
