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
        <h1>API LIST</h1>
        <small id="small-sub-title"></small>
        <div class="actions">
            <!--
                <a href="javascript:create();" class="actions__item zmdi zmdi-file-plus" data-title="create"></a>
            -->
        </div>
    </header>
    
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-sm-2">
                    <button id="button-create" onclick="create()" class="btn btn-link btn-sm btn--icon-text"><i class="zmdi zmdi-file-plus"></i> Create</button>
                </div>
            </div>
            <hr>      
            <table class="table table-bordered table-striped" id="table-records">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Email</th>
                        <th>Enable Flag</th>
                        <th>Create At</th>
                        <th>Update At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
  
    <div class="modal fade" id="modal-form">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-body">
                    <h5 class="modal-title">API LIST</h5>
                </div>
                <div class="modal-body">
                    <input id="input-id" type="hidden">
                    <div class="form-group">
                        <label>Email address</label>
                        <input id="input-email" type="text" class="form-control">
                        <i class="form-group__bar"></i>
                    </div>                    
                    {{--<div class="form-group">--}}
                        {{--<label>Desired Plate No</label>--}}
                        {{--<input id="input-desire-plate-no" type="text" class="form-control">--}}
                        {{--<i class="form-group__bar"></i>--}}
                    {{--</div>                    --}}
                    <div class="form-group">
                        <div class="checkbox checkbox--inline">
                            <input id="input-enable_flag"  type="checkbox">
                            <label class="checkbox__label" for="enable_flag">Enabled</label>
                        </div>                        
                    </div>                
                </div>
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
            autoWidth: false,
            bFilter:true,
            processing: true,
            paging : true,
            sorting: [[ 0, "desc" ]],
            deferRender: true,
            serverSide: false,
            responsive: false,
            scrollX:false,
            language: {searchPlaceholder: "Search in records..."},            
            sDom: '<"dataTables__top"lfB>rt<"dataTables__bottom"ip><"clear">',
            buttons: [
                {extend: "csvHtml5",title: function() {return 'PlateNoMap';}}, 
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
                {"data": "email"},
                {"data": "enable_flag","orderable":false,"render": function ( data, type, row, meta ) 
                    {
                        if (data==1) {
                            return '<span class="badge badge-pill badge-success">enabled</span>';
                        }
                        return '<span class="badge badge-pill badge-danger">disabled</span>';
                    }
                },
                {"data": "created_at"},
                {"data": "updated_at"},
                {"data": "action","orderable":false,"render": function ( data, type, row, meta ) 
                    {
                       var $val = '<button onclick="edit('+row.id+',\''+row.email+'\','+row.enable_flag+')" type="button" class="btn btn-link btn-sm btn--icon-text"><i class="zmdi zmdi-edit"></i>Edit</button>';
                       $val += '<button onclick="del('+row.id+')" type="button" class="btn btn-link btn-sm btn--icon-text"><i class="zmdi zmdi-delete "></i>Delete</button>';
                       return $val;
                    }
                },                     
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
        var $url = 'api_list/all';
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
            'email' : $('#input-email').val(),
            'enable_flag' : $('#input-enable_flag').is(":checked")?1:0,
        };
        var $url = 'api_list/create';
        if ($id>0) {
            $url = 'api_list/update';
            $data['id'] = $id;
        }
        post_request($url,$data);
    }

    /**
     * create record
     */
    function create() {
        $('#input-id').val(-1);        
        $('#input-email').val('');
        $('#input-enable_flag').prop( "checked",true );
        $('#modal-form').modal('show');
    }
    /**
     * edit record
     */
    function edit($id,$email,$enable_flag) {
        $('#input-id').val($id);        
        $('#input-email').val($email);
        $('#input-enable_flag').prop( "checked", $enable_flag==1?true:false );
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
                var $url = 'api_list/delete';
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
    });        
</script>
@endsection