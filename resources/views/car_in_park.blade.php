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
        <h1>CAR IN PARK</h1>

        <div class="actions">
        	<a href="javascript:refresh();" class="actions__item zmdi zmdi-refresh" data-title="refresh"></a>
        </div>
    </header>
    
    <div class="card">
        <div class="card-body">
            <table class="table table-bordered" id="table-report">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Plate No</th>
                        <th>Plate No2</th>
                        <th>Entry Time</th>
                        <th>Lane</th>
                        <th>Parking Type</th>
                        <th>eTicket/Season ID</th>
			            <th>Image</th>
                        <th>Locked</th>
			        <th>Action</th>
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

    <script>
 
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
        
    function refresh(){
        var $table_report = $('#table-report').DataTable();
        var $url = 'car_in_park_data';
        $table_report.ajax.url($url).load();
    }

    function show_notify($message,$type) {
        $.notify(
            {
                message: $message,
            },{
                type:$type,
                element: 'body',
            }
        );
    }

    var $ajax_mark_as_leave = null;

    function send_mark_request($id,$plate_no){
        if ($ajax_mark_as_leave != null) {
            $ajax_mark_as_leave.abort();
            $ajax_mark_as_leave = null;
        }
        $ajax_mark_as_leave = $.ajax({
                method: "POST",
                url: "car_in_park_mark_as_leave",
                data: { id: $id,
                    plate_no:$plate_no }
            }).done(function($data) {
                console.info($data);
                var $resp = $data;
                if ($resp.code==0) {
                refresh();
                show_notify("clear record succeeded","success");
            } else {
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
                $ajax_mark_as_leave = null;
            });
    }

    function on_button_mark_clicked($id,$plate_no,$entry_time,$parking_type) {
     	swal({
            title: 'Confirm to mark as leave?',
            text: 'This action may cause the selected car unable to leave the carpark. Please make sure the car is no longer in the carpark.',
            type: 'warning',
            showCancelButton: true,
            buttonsStyling: false,
            confirmButtonClass: 'btn btn-primary',
            confirmButtonText: 'Confirm',
            cancelButtonClass: 'btn btn-secondary'
        }).then(result => {
	        if(result.value){
		        send_mark_request($id,$plate_no);
            }
        });
    }

     /**
      * send payment done to backend,
      * @param $id the ticket id
      * @parma $amount the payment amount
      */
     $ajax_payment = null;
     function send_payment($id,$amount,$auth_code){
         if ($ajax_payment != null) {
             $ajax_payment.abort();
             $ajax_payment = null;
         }
         $ajax_payment = $.ajax({
             method: "POST",
             url: "api/localagent/v1/external_auth_payment",
             data: { ticket_id: $id,payment_amount: $amount,payment_method: 'cash',payment_authorization_code: $auth_code }
         }).done(function($data) {
             var $resp = $data;
             if ($resp.success==1) {
                 show_notify("payment succeeded","success");
             } else {
                 show_notify($resp.message,"danger");
             }
         }).fail(function($data) {
             if ($data.status==401) {
                 show_notify("Unauthenticated. Please re-login.","danger");
                 location.reload();
             } else {
                 show_notify("Something wrong. Please try again.","danger");
             }
         }).always(function() {
             $ajax_payment = null;
         });
     }
     /**
      * get the ticket payment info from backend
      */
     function get_payment($id,$plate_no){
         if ($ajax_payment != null) {
             $ajax_payment.abort();
             $ajax_payment = null;
         }
         $ajax_payment = $.ajax({
             method: "POST",
             url: "api/localagent/v1/get_ticket_info",
             data: { ticket_id: $id }
         }).done(function($data) {
             var $resp = $data;
             if ($resp.success==1) {
                 console.log(JSON.stringify($resp.data));
                 // return $resp.data
                 show_payment_info($id,$plate_no,$resp.data)
             } else {
                 show_notify($resp.message,"danger");
             }
         }).fail(function($data) {
             if ($data.status==401) {
                 show_notify("Unauthenticated. Please re-login.","danger");
                 location.reload();
             } else {
                 show_notify("Something wrong. Please try again.","danger");
             }
         }).always(function() {
             $ajax_payment = null;
         });
     }

     function show_payment_info($id,$plate_no,$data) {
         var $payment_amount = $data.value;
         var $latest_subticket_number = $data.ticket;
         var $payments = $data.subticket_list;
         var $i,$html_text;
         $html_text = 'Plate No <font color="blue">' + $plate_no + '</font>, Entry at ' + $data.entry +'<br>';
         for ($i = 0; $i < $payments.length; $i++) {
             if($payments[$i].status == 0){
                 $amount = 0;
             }
             else{
                 $amount = $payments[$i].amount;
                 $amount = $amount/100;
                 $amount = $amount.toFixed(2);

             }
             $html_text += '['+$payments[$i].subticket+'] '+'Pay <font color="red">RM' + $amount +'</font> at '+ $payments[$i].trx_date + ' by '+ $payments[$i].method + '<br>';
         }
         console.log($html_text);
         if ($payment_amount>0) {
             $payment_amount_format = $payment_amount/100;
             $payment_amount_format = $payment_amount_format.toFixed(2);
             var $date = new Date();
             var $timestamp = $date.getTime()
             $auth_code = $timestamp + $latest_subticket_number;
             // console.log($auth_code);
             swal({
                 title: 'Need to pay :RM'+ $payment_amount_format,
                 html: $html_text,
                 type: 'warning',
                 showCancelButton: true,
                 buttonsStyling: false,
                 confirmButtonClass: 'btn btn-primary',
                 confirmButtonText: 'Payment Done',
                 cancelButtonClass: 'btn btn-secondary'
             }).then(result => {
                 if(result.value){
                     $payment_amount = $payment_amount / 100;
                     send_payment($id,$payment_amount,$auth_code);
                 }
             });
         } else {
             swal({
                 title: 'No payment need to be done',
                 html: $html_text,
                 type: 'success',
                 showCancelButton: false,
                 buttonsStyling: false,
                 confirmButtonClass: 'btn btn-primary',
                 confirmButtonText: 'Ok',
                 cancelButtonClass: 'btn btn-secondary'
             }).then(result => {
             });
         }

     }
    $(document).ready(function() {
        $(document).on("click",".open-AddImageDialog", function() {
		var myImageId = $(this).data('id');
		$(".modal-content #imageId").val(myImageId);
		document.getElementById("imageView").src = myImageId;
	});

    $("#table-report").DataTable({
        autoWidth: true,
        scrollX:true,
        bFilter:true,
        processing: true,
        deferRender: true,
        paging: true,
        serverSide: false,
        ordering: true,
        responsive: true,
        sorting: [[ 0, "desc" ]],
        lengthMenu: [
            [10, 20, 40, -1],
            ["10 Rows", "20 Rows", "40 Rows", "Everything"]
        ],
        language: {searchPlaceholder: "Filter in records..."},
        sDom: '<"dataTables__top"lfB>rt<"dataTables__bottom"ip><"clear">',
        buttons: [{
            extend: "csvHtml5",
            title: function() {
                return 'CarInPark';
            }
        }, {
            extend: "print",
            title: function() {
                return 'CarInPark';
            }
        }],
        initComplete: function(a, b) {
            var $buttonHtml = '<div class="dataTables_buttons hidden-sm-down actions">';
            $buttonHtml += '<span class="actions__item zmdi zmdi-print" data-table-action="print" />';
            $buttonHtml += '<span class="actions__item zmdi zmdi-fullscreen" data-table-action="fullscreen"/>';
            $buttonHtml += '<span class="actions__item zmdi zmdi-download" data-table-action="csv"/>';
            $buttonHtml += '<span class="actions__item zmdi zmdi-refresh" data-table-action="refresh"/>';
            $buttonHtml += '</div>';
            $(this).closest(".dataTables_wrapper").find(".dataTables__top").prepend($buttonHtml)
        },
        "columns": [{
                "data": "id"
            }, {
                "data": "plate_no","orderable":false,
                "render": function ( data, type, row, meta ) {
		    	    return data;
		        }
            }, {
                "data": "plate_no2","orderable":false,
                "render": function ( data, type, row, meta ) {
		    	    return data;
		        }
            }, {
                    "data": "entry_time"
            }, {
                    "data": "lane_id","orderable":false,
                    "render": function ( data, type, row, meta ) {
			            return get_lane_string(data);
                    }
            }, {
                "data": "parking_type","orderable":false,
                "render": function ( data, type, row, meta ) {
			        return data;
                }
            }, {
                "data": "season_card","orderable":false,
                "render": function ( data, type, row, meta ) {
                    if (row.parking_type=='season') {
                        return row.season_card + '<br>'+row.user_name;
                    }
                    return row.visitor_ticket;
                }
            },{
                "data": "big_picture","orderable":false,"searchable":false,
                "render": function ( data, type, row, meta ) {
                    return '<a data-toggle="modal" data-id="'+data+'" title="click to view" class="open-AddImageDialog btn-link btn-primary" href="#showImageDialog"><img src="'+data+'" width="100" height="50"/></a>';
                }
            },{
                "data": "locked_flag","orderable":false,
                "render": function ( data, type, row, meta ) {
                    if (data==1) {
                        return '<span class="badge badge-pill badge-danger">locked</span>';
                    }
                    return '<span class="badge badge-pill badge-success">unlocked</span>';
                }
            },{
                "data": "action","orderable":false,"searchable":false,
                "render": function ( data, type, row, meta ) {
                    var $val ='';
                    $val += '<button onclick="on_button_mark_clicked('+row.id+',\''+row.plate_no+'\',\''+row.entry_time+'\',\''+row.parking_type+'\')" type="button" class="btn btn-link btn-sm btn--icon-text"><i class="zmdi zmdi-edit"></i>Mark as Leave</button>';
                    @if ($can_pay)
                        $val += '<br><button onclick="get_payment('+row.id+',\''+row.plate_no+'\')" type="button" class="btn btn-link btn-sm btn--icon-text"><i class="zmdi zmdi-card "></i>Pay</button>';
                    @endif
                    return $val;
                }
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
                if (exportFormat === 'refresh') {
                    refresh();
                }
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

        refresh();
    });
    </script>

@endsection


