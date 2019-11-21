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
        <h1>White List</h1>
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
                        <th>Plate No</th>
                        <th>Username</th>
                        <th>Description</th>
                        <th>Valid From</th>
                        <th>Valid Until</th>
                        <th>Enable Flag</th>
                        <th>Updated At</th>
                        @if ($can_edit||$can_delete) 
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
                    <h5 class="modal-title">White List</h5>                    
                </div>
                <div class="modal-body">
                    <p>White list allow user can enter/leave parking site in the valid period.</p>                    
                    <input id="input-id" type="hidden">
                    <table class="table">
                    <tbody>
                        <tr>
                            <th width="80px">Plate No</th>
                            <th >
                                <input id="input-plate-no" type="text" class="form-control">
                            </th>
                        </tr>
                        <tr>
                            <th width="80px">Username</th>
                            <th >
                                <input id="input-username" type="text" class="form-control">
                            </th>
                        </tr>
                        <tr>
                            <th width="80px">Description</th>
                            <th>
                                <input id="input-description" type="text" class="form-control">
                            </th>
                        </tr>
                        <tr>
                            <th width="80px">Enable Flag</th>
                            <th>
                                <input type="checkbox" id="input-enable-flag" >
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
                    </tbody>
                </table>                    
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
                {extend: "csvHtml5",title: function() {return 'WhiteList';}}, 
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
                {"data": "plate_no"},
                {"data": "username"},
                {"data": "description"},
                {"data": "valid_from"},
                {"data": "valid_until"},
                {"data": "enable_flag","orderable":false,"render": function ( data, type, row, meta ) 
                    {
                        if (data==1) {
                            return '<span class="badge badge-pill badge-success">enabled</span>';
                        }
                        return '<span class="badge badge-pill badge-danger">disabled</span>';
                    }
                },                     
                {"data": "updated_at"},
                @if ($can_edit||$can_delete) 
                {"data": "action","orderable":false,"render": function ( data, type, row, meta ) 
                    {
                        var $obj_str = Base64.encode(JSON.stringify(row));
                        var $val ='';
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
        var $url = 'white_list/all';
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
            'plate_no' : $('#input-plate-no').val(),
            'username' : $('#input-username').val(),
            'description' : $('#input-description').val(),
            'valid_from' : $('#input-valid-from').val(),
            'valid_until' : $('#input-valid-until').val(),
            'enable_flag' : $('#input-enable-flag').is(":checked")?1:0,
        };
        var $url = 'white_list/create';
        if ($id>0) {
            $url = 'white_list/update';
            $data['id'] = $id;
        }
        post_request($url,$data);
    }

    /**
     * create record
     */
    function create() {
        $('#input-id').val(-1);        
        $('#input-plate-no').val('');
        $('#input-username').val('');
        $('#input-description').val('');
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
        $('#input-plate-no').val($obj.plate_no);
        $('#input-username').val($obj.username);
        $('#input-description').val($obj.description);
        if ($obj.valid_from === null) 
        {
            $obj.valid_from = '';
        }
        $obj_input_valid_from.setDate($obj.valid_from,true,"Y-m-d");
        if ($obj.valid_until===null) {
            $obj.valid_until = '';
        }
        $obj_input_valid_until.setDate($obj.valid_until,true,"Y-m-d");
        $('#input-enable-flag').prop( "checked", $obj.enable_flag==1?true:false );
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
                var $url = 'white_list/delete';
                post_request($url,$data);
            }
        });
    }
    /**
     * document ready function
     */

    $(document).ready(function() {
        $obj_input_valid_from = flatpickr("#input-valid-from", {dateFormat: "Y-m-d",
            enableTime: false,disableMobile:true});   
        $obj_input_valid_until = flatpickr("#input-valid-until", {dateFormat: "Y-m-d",
            enableTime: false,disableMobile:true});   
        setup_table();
        refresh();
    });        
</script>
@endsection