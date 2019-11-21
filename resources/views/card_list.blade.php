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
    <h1>Season Account</h1>
    <small id="small-sub-title"></small>
    <div class="actions">
    </div>
</header>

<div class="card">
    <div class="card-body">
			<select class="select2" class="form-control" id="status" name="status[]" data-placeholder="Choose Status" multiple="multiple">
				<option value="1">Active</option>
				<option value="0">Inactive</option> 
			</select>
            <div class="table-responsive"> 
                <table id="posts" class="table table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Card ID</th>
                        <th>Name</th>
                        <th>Plate No 1</th>
                        <th>Plate No 2</th>
                        <th>Plate No 3</th>
                        <th>VIP</th>
                        <th>Valid Until</th>
                        <th>Active Flag</th>
                        <th>Updated At</th>
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
            "ajax":{
                     "url": url,
                     "dataType": "json",
                     "type": "POST",
					 "data": function ( d ) {
                        return $.extend( {}, d, {
                        _token: "{{csrf_token()}}",
                        "status": $('#status').val()
                        } );
                    }
                },
            "columns": [
                { "data": "id" }, 
                { "data": "user_name" }, 
                { "data": "plate_no1" }, 
                { "data": "plate_no2" }, 
                { "data": "plate_no3" }, 
                { "data": "vip" }, 
                { "data": "valid_until" }, 
                { "data": "active_flag" },
                { "data": "updated_at" } 
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

		var table = $('#posts').DataTable();
		
		$('#status').on('select2:close', function (e) {
			table.draw();
		});
 
    });
</script>
@endsection