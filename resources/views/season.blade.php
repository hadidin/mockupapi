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
        <h1>Season Account</h1>
        <small id="small-sub-title"></small>
        <div class="actions">
            <!--
                <a href="javascript:create();" class="actions__item zmdi zmdi-file-plus" data-title="create"></a>
            -->
        </div>
    </header>

    <div class="card">
        <div class="card-body">
            @if ($can_create)
            <div class="row">
                <div class="col-sm-2">
                    <button id="button-create" onclick="create()" class="btn btn-link btn-sm btn--icon-text"><i class="zmdi zmdi-file-plus"></i> Create</button>
                </div>
            </div>
            <hr>
            @endif
            <table class="table table-bordered table-striped" id="table-records">
                <thead>
                    <tr>
                        <th width="100px">ID</th>
                        <th>Card Number</th>
                        <th>Username</th>
                        <th>Username 2</th>
                        <th>Company Name / Unit No</th>
                        <th>Contact No</th>
                        <th>Plate No1</th>
                        <th>Plate No2</th>
                        <th>Plate No3</th>
                        <th>Plate No4</th>
                        <th>Plate No5</th>
                        <th>Parking Slot</th>
                        <th>Bay No</th>
                        <th>Remarks</th>
                        <th>Valid From</th>
                        <th>Valid Until</th>
                        <th>Enable Flag</th>
                        <th>Updated At</th>
                        @if ($can_edit || $can_delete)
                        <th>Action</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modal-form">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Season Account</h5>
                </div>
                <div class="modal-body">
                    <input id="input-id" type="hidden">
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table">
                                <tbody>
                                    <tr>
                                        <th width="80px">Card Number</th>
                                        <th >
                                            <input id="input-card-id" type="text" class="form-control">
                                        </th>
                                    </tr>
                                    <tr>
                                        <th width="80px">Username</th>
                                        <th >
                                            <input id="input-username" type="text" class="form-control">
                                        </th>
                                    </tr>
                                    <tr>
                                        <th width="80px">Username2</th>
                                        <th >
                                            <input id="input-username2" type="text" class="form-control">
                                        </th>
                                    </tr>
                                    <tr>
                                        <th width="80px">Company Name</th>
                                        <th >
                                            <input id="input-company-name" type="text" class="form-control">
                                        </th>
                                    </tr>
                                    <tr>
                                        <th width="80px">Contact Number</th>
                                        <th >
                                            <input id="input-contact-no" type="text" class="form-control">
                                        </th>
                                    </tr>
                                    <tr>
                                        <th width="80px">Plate No1</th>
                                        <th>
                                            <input id="input-plate-no1" type="text" class="form-control">
                                        </th>
                                    </tr>
                                    <tr>
                                        <th width="80px">Plate No2</th>
                                        <th>
                                            <input id="input-plate-no2" type="text" class="form-control">
                                        </th>
                                    </tr>
                                    <tr>
                                        <th width="80px">Plate No3</th>
                                        <th>
                                            <input id="input-plate-no3" type="text" class="form-control">
                                        </th>
                                    </tr>
                                    <tr>
                                        <th width="80px">Plate No4</th>
                                        <th>
                                            <input id="input-plate-no4" type="text" class="form-control">
                                        </th>
                                    </tr>
                                    <tr>
                                        <th width="80px">Plate No5</th>
                                        <th>
                                            <input id="input-plate-no5" type="text" class="form-control">
                                        </th>
                                    </tr>
                                    <tr>
                                        <th width="80px">Parking Slot</th>
                                        <th>
                                            <input id="input-parking-slot" type="text" class="form-control">
                                        </th>
                                    </tr>
                                    <tr>
                                        <th width="80px">Bay No</th>
                                        <th>
                                            <input id="input-bay-no" type="text" class="form-control">
                                        </th>
                                    </tr>
                                    <tr>
                                        <th width="80px">Remarks</th>
                                        <th>
                                            <input id="input-remarks" type="text" class="form-control">
                                        </th>
                                    </tr>
                                    <tr>
                                        <th width="80px">Valid From</th>
                                        <th>
                                            <input id="input-valid-from" type="text" class="form-control" placeholder="select a date" readonly="readonly">
                                        </th>
                                    </tr>
                                    <tr>
                                        <th width="80px">Valid Until</th>
                                        <th>
                                            <input id="input-valid-until" type="text" class="form-control" placeholder="select a date" readonly="readonly">
                                        </th>
                                    </tr>
                                    <tr>
                                        <th width="80px">Enable Flag</th>
                                        <th>
                                            <input type="checkbox" id="input-enable-flag" >
                                        </th>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-link" data-dismiss="modal" onclick="submit()">Submit</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
    'use strict';
    var $obj_input_valid_from,$obj_input_valid_until;
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
     * setup the data table
     */
    function setup_table() {
        $("#table-records").DataTable({
            autoWidth: true,
            bFilter:true,
            processing: true,
            paging : true,
            sorting: [[ 0, "desc" ]],
            deferRender: true,
            serverSide: false,
            responsive: false,
            scrollX:true,
            language: {searchPlaceholder: "Search in records..."},
            sDom: '<"dataTables__top"lfB>rt<"dataTables__bottom"ip><"clear">',
            buttons: [
                {extend: "csvHtml5",title: function() {return 'SeasonAccount';}},
            ],
            initComplete: function(a, b) {
                var $buttonHtml = '<div class="dataTables_buttons hidden-sm-down actions">';
                $buttonHtml += '<span class="actions__item zmdi zmdi-fullscreen" data-table-action="fullscreen"/>';
                $buttonHtml += '<span class="actions__item zmdi zmdi-download" data-table-action="csv"/>';
                $buttonHtml += '</div>';
                    $(this).closest(".dataTables_wrapper").find(".dataTables__top").prepend($buttonHtml)
            },
            "columns": [
                {"data": "id"},
                {"data": "card_id"},
                {"data": "user_name"},
                {"data": "user_name2"},
                {"data": "company_name"},
                {"data": "contact_no"},
                {"data": "plate_no1"},
                {"data": "plate_no2"},
                {"data": "plate_no3"},
                {"data": "plate_no4"},
                {"data": "plate_no5"},
                {"data": "parking_slot"},
                {"data": "bay_no"},
                {"data": "remarks"},
                {"data": "valid_from"},
                {"data": "valid_until"},
                {"data": "active_flag","orderable":false,"render": function ( data, type, row, meta )
                    {
                        if (data==1) {
                            return '<span class="badge badge-pill badge-success">enabled</span>';
                        }
                        return '<span class="badge badge-pill badge-danger">disabled</span>';
                    }
                },
                {"data": "updated_at"},
                @if ($can_edit || $can_delete)
                {"data": "action","orderable":false,"render": function ( data, type, row, meta )
                    {
                        var $obj_str = Base64.encode(JSON.stringify(row));
                        var $val='';
                        @if ($can_edit)
                        $val += '<button onclick="edit('+row.id+',\''+$obj_str+'\')" type="button" class="btn btn-link btn-sm btn--icon-text"><i class="zmdi zmdi-edit"></i>Edit</button>';
                        @endif
                        @if ($can_delete)
                        $val += '<button onclick="del('+row.id+')" type="button" class="btn btn-link btn-sm btn--icon-text"><i class="zmdi zmdi-delete "></i>Delete</button>';
                        @endif
                        return $val;
                    }
                },
                @endif
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
            var action = $(this).data('table-action');
            if (action === 'csv') {
                $(this).closest('.dataTables_wrapper').find('.buttons-csv').trigger('click');
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
    }
    /**
     * refresh data
     */
    function refresh(){
        var $table = $('#table-records').DataTable();
        var $url = 'season/all';
        $table.ajax.url($url).load();
    }

    var $ajax_request = null;
    function post_request($url,$post_data){
        if ($ajax_request != null) {
            $ajax_request.abort();
            $ajax_request = null;
        }
        $ajax_request = $.ajax({
            method: "POST",
            url: $url,
            data:$post_data
        }).done(function($data) {
            var $resp = $data;
            if ($resp.code==0) {
                refresh();
                show_notify("Operation done","success");
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
            $ajax_request = null;
        });
    }
    /**
     * submit request
     */
    function submit() {
        var $id = $('#input-id').val();
        var $data = {
            'card_id' : $('#input-card-id').val(),
            'username' : $('#input-username').val(),
            'username2' : $('#input-username2').val(),
            'company_name' : $('#input-company-name').val(),
            'contact_no' : $('#input-contact-no').val(),
            'plate_no1' : $('#input-plate-no1').val(),
            'plate_no2' : $('#input-plate-no2').val(),
            'plate_no3' : $('#input-plate-no3').val(),
            'plate_no4' : $('#input-plate-no4').val(),
            'plate_no5' : $('#input-plate-no5').val(),
            'parking_slot' : $('#input-parking-slot').val(),
            'bay_no' : $('#input-bay-no').val(),
            'remarks' : $('#input-remarks').val(),
            'valid_from' : $('#input-valid-from').val(),
            'valid_until' : $('#input-valid-until').val(),
            'enable_flag' : $('#input-enable-flag').is(":checked")?1:0,
        };
        var $url = 'season/create';
        if ($id>0) {
            $url = 'season/update';
            $data['id'] = $id;
        }
        post_request($url,$data);
    }

    /**
     * create record
     */
    function create() {
        $('#input-id').val(-1);
        $('#input-card-id').val('');
        $('#input-username').val('');
        $('#input-username2').val('');
        $('#input-company-name').val('');
        $('#input-contact-no').val('');
        $('#input-plate-no1').val('');
        $('#input-plate-no2').val('');
        $('#input-plate-no3').val('');
        $('#input-plate-no4').val('');
        $('#input-plate-no5').val('');
        $('#input-parking-slot').val('');
        $('#input-bay-no').val('');
        $('#input-remarks').val('');
        $obj_input_valid_from.setDate('',true,"Y-m-d");
        $obj_input_valid_until.setDate('',true,"Y-m-d");
        $('#input-enable-flag').prop( "checked",true );
        $('#modal-form').modal('show');
    }
    /**
     * edit record
     */
    function edit($id,$obj_str) {
        var $obj = JSON.parse(Base64.decode($obj_str));
        $('#input-id').val($id);
        $('#input-card-id').val($obj.card_id);
        $('#input-username').val($obj.user_name);
        $('#input-username2').val($obj.user_name2);
        $('#input-company-name').val($obj.company_name);
        $('#input-contact-no').val($obj.contact_no);
        $('#input-plate-no1').val($obj.plate_no1);
        $('#input-plate-no2').val($obj.plate_no2);
        $('#input-plate-no3').val($obj.plate_no3);
        $('#input-plate-no4').val($obj.plate_no4);
        $('#input-plate-no5').val($obj.plate_no5);
        $('#input-parking-slot').val($obj.parking_slot);
        $('#input-bay-no').val($obj.bay_no);
        $('#input-remarks').val($obj.remarks);
        if ($obj.valid_from === null)
        {
            $obj.valid_from = '';
        }
        $obj_input_valid_from.setDate($obj.valid_from,true,"Y-m-d H:i:S");
        if ($obj.valid_until===null) {
            $obj.valid_until = '';
        }
        $obj_input_valid_until.setDate($obj.valid_until,true,"Y-m-d H:i:S");
        $('#input-enable-flag').prop( "checked", $obj.active_flag==1?true:false );
        $('#modal-form').modal('show');
    }
    /**
     * delete record
     */
    function del($id) {
     	swal({
            title: 'Confirm to delete?',
            type: 'warning',
            showCancelButton: true,
            buttonsStyling: false,
            confirmButtonClass: 'btn btn-primary',
            confirmButtonText: 'Confirm',
            cancelButtonClass: 'btn btn-secondary'
        }).then(result => {
	        if(result.value){
                var $data = {'id' : $id};
                var $url = 'season/delete';
                post_request($url,$data);
            }
        });
    }
    /**
     * document ready function
     */
    $(document).ready(function() {
        setup_table();
        refresh();

        $obj_input_valid_from = flatpickr("#input-valid-from", {dateFormat: "Y-m-d H:i:S",
            enableTime: true, enableSeconds:true,disableMobile:true});
        $obj_input_valid_until = flatpickr("#input-valid-until", {dateFormat: "Y-m-d H:i:S",
            enableTime: true, enableSeconds:true,disableMobile:true});

        $('#input-acc-flag').change(function(){
          if($(this).is(':checked')){
                $(".time-picker").prop('disabled', false);
            } else {
                $(".time-picker").prop('disabled', true);
            }
        });

        var $i = 0;
        for ($i=1;$i<=7;$i++) {
            var $from = '#input-w'+$i+'-from';
            $($from).val('00:00');
            var $to = 'input-w'+$i+'-to';
            $($to).val('23:59');
        }


    });
</script>
@endsection
