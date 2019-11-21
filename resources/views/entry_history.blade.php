@extends('parts.template')

@section('title')
Home
@endsection

@section('content')

@php
    $start_date=$_GET['start_date'];
    $end_date=$_GET['end_date'];
@endphp

<div class="page-loader">
    <div class="page-loader__spinner">
        <svg viewBox="25 25 50 50">
            <circle cx="50" cy="50" r="20" fill="none" stroke-width="2" stroke-miterlimit="10" />
        </svg>
    </div>
</div>

<font color="#b2b2b2" size="5"><b>HISTORY</b></font><br>
<br>

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
            <div class="col-sm-6">
                <div class="input-group">
                <div class="input-group-prepend">
                <span class="input-group-text"><i class="zmdi zmdi-calendar"></i></span>
                </div>
                <input id="start_date" onblur="drawTable()" name="start_date" value={{$start_date}} type="text" class="form-control datetime-picker " placeholder="&nbsp;&nbsp;Start Date">
                </div>
            </div>
            <div class="col-sm-6">
                <div class="input-group">
                <div class="input-group-prepend">
                <span class="input-group-text"><i class="zmdi zmdi-calendar"></i></span>
                </div>
                <input id="end_date" onblur="drawTable()" name="end_date" value={{$end_date}} type="text" class="form-control datetime-picker " placeholder="&nbsp;&nbsp;End Date">
                </div>
            </div>
        </div>
        <br>
            <div class="table-responsive">
                <table class="table table-bordered" id="posts">
                    <thead>
                        <tr>
                        <th>ID</th>
                        <th>Parking Type</th>
                        <th>Plate No</th>
                        <th>Plate No2</th>
                        <th>Entry Time</th>
                        <th>Exit Time</th>
                        <th>Leave Type</th>
                        <th>Payment Type</th>
                        <th>Amount</th>
                        <th>Payment Status</th>
                        <th>Payment Time</th>
                        <th>Ref ID</th>
                        </tr>
                    </thead>
                </table>
                </div>
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

        var url = window.location.href;;

        $('#posts').DataTable({
            "processing": true,
            "serverSide": true,
            "order": [[ 0, "desc" ]],
            "ajax":{
            "url": url,
            "dataType": "json",
            "type": "POST",
            "data": function ( d ) {
                        return $.extend( {}, d, {
                        _token: "{{csrf_token()}}",
                        "start_date": $('#start_date').val(),
                        "end_date": $('#end_date').val()
                        } );
                    }
            },
            "columns": [
                { "data": "id_tbl_car_in_site" },
                { "data": "parking_type" },
                { "data": "plate_no" },
                { "data": "plate_no2" },
                { "data": "entry_logs_time" },
                { "data": "exit_time" },
                { "data": "leave_type" },
                { "data": "method" },
                { "data": "amount" },
                { "data": "payment_status" },
                { "data": "payment_time" },
                { "data": "vendor_ticket_id" },
            ],
            "lengthMenu":[[10,50,100],["10 Rows","50 Rows","100 Rows"]],
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
				searchPlaceholder: "Search for records..."
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
