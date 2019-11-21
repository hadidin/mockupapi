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
 
<font color="#b2b2b2" size="5"><b>ENTRY LOG REVIEWED</b></font><br>
<br>  
@php
$start_date=$_GET['start_date'];
$end_date=$_GET['end_date'];
@endphp 

<div class="modal hide fade" id="addImageDialog" tabindex="-1" role="dialog" aria-labelledby="roleModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <img id="imageView" src="" alt="" width="100%">
        </div>
    </div>
</div>
 

<div class="card">
    <div class="card-body">  
        <div class="row">
            <div class="col-sm-4"> 
                <div class="input-group">
                <div class="input-group-prepend">
                <span class="input-group-text"><i class="zmdi zmdi-calendar"></i></span>
                </div>
                <input id="start_date" onblur="drawTable()" name="start_date" value={{$start_date}} type="text" class="form-control datetime-picker " placeholder="&nbsp;&nbsp;Start Date">
                </div>
            </div> 
            <div class="col-sm-4">
                <div class="input-group">
                <div class="input-group-prepend">
                <span class="input-group-text"><i class="zmdi zmdi-calendar"></i></span>
                </div>
                <input id="end_date" onblur="drawTable()" name="end_date" value={{$end_date}} type="text" class="form-control datetime-picker " placeholder="&nbsp;&nbsp;End Date">
                </div>
            </div>
            <div class="col-sm-4">
            </div>
        </div>
        <br>
        <div class="row">
            <div class="col-sm-4"> 
                <select class="select2" class="form-control" id="season" data-placeholder="Season or Non Season Pass" multiple="multiple">
                    <option value="0">Non Season</option>
                    <option value="1">Season Pass</option> 
                </select>
            </div>
            <div class="col-sm-4"> 
                <select class="select2" id="lanes" multiple data-placeholder="Lane Entry or Exit">
                @foreach ($lane_list as $lane)
                    <option value="{{$lane->id}}">{{$lane->name}}</option>
                @endforeach 
                </select>
            </div>

            <div class="col-sm-4"> 
                <select class="select2" class="form-control" id="review_flag" data-placeholder="Reviewed As" multiple="multiple">
                    <option value="1">Correct</option>
                    <option value="2">Wrong</option>
                    <option value="3">Undetected</option>
                    <option value="4">Trigger Timing Incorrect</option>
                    <option value="5">Invalid</option> 
                </select>
            </div>
        </div>
    
    
        <div class="table-responsive"> 
            <table class="table table-bordered table-hover" id="posts">
                <thead>
                <tr>
                <th>Log id</th>
                <th>Lane name</th>
                <th>Camera SN</th>
                <th>Entry/Exit</th>
                <th>Plate no</th>
                <th>Small img</th>
                <th>Big img</th>
                <th>Reviewed result</th>
                <th>Season subscriber</th>
                <th>Date Time</th>
                <th>Success trx</th>
                <th>Remark</th>
                </tr>
                </thead>
                <tfoot>
                <tr>
                <th>Log id</th>
                <th>Lane name</th>
                <th>Camera SN</th>
                <th>Entry/Exit</th>
                <th>Plate no</th>
                <th>Small img</th>
                <th>Big img</th>
                <th>Reviewed result</th>
                <th>Season subscriber</th>
                <th>Date Time</th>
                <th>Success trx</th>
                <th>Remark</th>
                </tr>
                </tfoot> 
            </table> 
        </div> 
    </div>
</div>

<script>
    $(document).ready(function () {
        	// Add custom buttons
		var dataTableButtons =  '<div class="dataTables_buttons hidden-sm-down actions">' +
			'<span class="actions__item zmdi zmdi-print" data-table-action="print" />' +
			'<span class="actions__item zmdi zmdi-fullscreen" data-table-action="fullscreen" />' +
			'<div class="dropdown actions__item">' +
			'<i data-toggle="dropdown" class="zmdi zmdi-download" />' +
			'<ul class="dropdown-menu dropdown-menu-right">' +
			'<a href="" class="dropdown-item" data-table-action="excel">Excel (.xlsx)</a>' +
			'<a href="" class="dropdown-item" data-table-action="csv">CSV (.csv)</a>' +
			'</ul>' +
			'</div>' +
            '</div>';
            
        var url = window.location.href;

        $('#posts').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax":{
                     "url": url,
                     "dataType": "json",
                     "type": "POST", 
                     "data": 
                        function ( d ) {
                            return $.extend( {}, d, {
                            _token: "{{csrf_token()}}",
                            "review_flag": $('#review_flag').val(),
                            "season": $('#season').val(),
                            "lanes": $('#lanes').val(),
                            "start_date": $('#start_date').val(),
                            "end_date": $('#end_date').val()
                            } );
                    }
            },
            "columns": [
                { "data": "id" },
                { "data": "lane_name" },
                { "data": "camera_sn" },
                { "data": "in_out_flag" },
                { "data": "plate_no" },
                { "data": "img" },
                { "data": "img_cover" },
                { "data": "review_flag" },
                { "data": "is_season_subscriber" },
                { "data": "create_time" },
                { "data": "is_success" },
                { "data": "review" }

            ],
            "lengthMenu":[[10,50,5000],["10 Rows","50 Rows","5000 Rows"]],
            "sDom": '<"dataTables__top"lfB>rt<"dataTables__bottom"ip><"clear">',
			buttons: [
				{
					extend: 'excelHtml5',
					title: 'Export Data' 
				},
				{
					extend: 'csvHtml5',
					title: 'Export Data'
				},
				{
					extend: 'print',
					title: 'KiplePark Local-PSM'
				}
			],
			"initComplete": function(settings, json) {
				$(this).closest('.dataTables_wrapper').find('.dataTables__top').prepend(dataTableButtons);
			},
			language: {
				searchPlaceholder: "Plate Number"
			},
 
        });

        
		// Add blue line when search is active
		$('.dataTables_filter input[type=search]').focus(function () {
			$(this).closest('.dataTables_filter').addClass('dataTables_filter--toggled');
		});

		$('.dataTables_filter input[type=search]').blur(function () {
			$(this).closest('.dataTables_filter').removeClass('dataTables_filter--toggled');
		});


		// Data table buttons
		$('body').on('click', '[data-table-action]', function (e) {
			e.preventDefault();

			var exportFormat = $(this).data('table-action');

			if(exportFormat === 'excel') {
				$(this).closest('.dataTables_wrapper').find('.buttons-excel').trigger('click');
			}
			if(exportFormat === 'csv') {
				$(this).closest('.dataTables_wrapper').find('.buttons-csv').trigger('click');
			}
			if(exportFormat === 'print') {
				$(this).closest('.dataTables_wrapper').find('.buttons-print').trigger('click');
			}
			if(exportFormat === 'fullscreen') {
				var parentCard = $(this).closest('.card');

				if(parentCard.hasClass('card--fullscreen')) {
					parentCard.removeClass('card--fullscreen');
					$('body').removeClass('data-table-toggled');
				}
				else {
					parentCard.addClass('card--fullscreen')
					$('body').addClass('data-table-toggled');
				}
			}
		});


        var table = $('#posts').DataTable();

        $('#season').on('select2:close', function (e) {
            table.draw();
        });

        $('#lanes').on('select2:close', function (e) {
            table.draw();
        });
       
        $('#review_flag').on('select2:close', function (e) {
            table.draw();
        });
  
    });

    function drawTable() {
        var table = $('#posts').DataTable();
        table.draw();
    }

    $(document).on("click", ".open-AddImageDialog", function () {
     var myImageId = $(this).data('id');
     $(".modal-content #imageId").val( myImageId );
     // As pointed out in comments, 
     // it is superfluous to have to manually call the modal.
        // $('#addImageDialog').modal('show');
    document.getElementById("imageView").src = myImageId;

    });
</script>
@endsection