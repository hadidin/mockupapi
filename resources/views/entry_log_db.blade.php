@extends('parts.template')

@section('title')
Home
@endsection

@section('content')
<style> 

</style>

<div class="page-loader">
    <div class="page-loader__spinner">
        <svg viewBox="25 25 50 50">
            <circle cx="50" cy="50" r="20" fill="none" stroke-width="2" stroke-miterlimit="10" />
        </svg>
    </div>
</div>

 
<font color="#b2b2b2" size="5"><b>ENTRY LOG</b></font><br>
<br> 
@php
$start_date=$_GET['start_date'];
$end_date=$_GET['end_date'];
@endphp  

@if(session()->has('message'))
    <div class="alert alert-success">
        {{ session()->get('message') }}
    </div>
@endif

<div class="modal hide fade" id="addImageDialog" tabindex="-1" role="dialog" aria-labelledby="roleModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <img id="imageView" src="" alt="" width="100%">
        </div>
    </div>
</div>
<div class="card">
    <div class="card-body"> 
    
    <form method="POST" name="exportExcelEntry" action="{{ route('entry_log_db') }}">  
        <div class="row">
            <div class="col-sm-6"> 
                <div class="input-group">
                <div class="input-group-prepend">
                <span class="input-group-text"><i class="zmdi zmdi-calendar"></i></span>
                </div>
                <input id="start_date" onchange="drawTable()" name="start_date" value={{$start_date}} type="text" class="form-control datetime-picker " placeholder="&nbsp;&nbsp;Start Date">
                </div>
            </div> 
            <div class="col-sm-6">
                <div class="input-group">
                <div class="input-group-prepend">
                <span class="input-group-text"><i class="zmdi zmdi-calendar"></i></span>
                </div>
                <input id="end_date" onchange="drawTable()" name="end_date" value={{$end_date}} type="text" class="form-control datetime-picker " placeholder="&nbsp;&nbsp;End Date">
                </div>
            </div> 
        </div>
        <br>
        <div class="row">
            <div class="col-sm-4"> 
                <select class="select2" class="form-control" id="season" name="season[]" data-placeholder="Season or Non Season Pass" multiple="multiple">
                    <option value="0">Non Season</option>
                    <option value="1">Season Pass</option> 
                </select>
            </div>
            <div class="col-sm-4"> 
                <select class="select2" id="lanes" name="lanes[]" multiple data-placeholder="Lane Entry or Exit">
                @foreach ($lane_list as $lane)
                    <option value="{{$lane->id}}">{{$lane->name}}</option>
                @endforeach 
                </select>
            </div>
            <div class="col-sm-4"> 
                <select class="select2" class="form-control" id="review_flag" name="review_flag[]" data-placeholder="Reviewed As" multiple="multiple">
                    <option value="0">Unreviewed</option>
                    <option value="1">Correct</option>
                    <option value="2">Wrong</option>
                    <option value="3">Undetected</option>
                    <option value="4">Trigger Timing Incorrect</option>
                    <option value="5">Invalid</option> 
                </select>
            </div>
        </div>
        <br> 
     </div>
</div>
<input id="export" name="export" type="hidden" value="export">
<button type="submit" class="btn btn-light"><i class="zmdi zmdi-download zmdi-hc-lg"></i> Download</button>
</form> 
<button class="btn btn-light" onclick="" data-toggle="modal" data-target="#modal-large"><i class="zmdi zmdi-file-text zmdi-hc-lg"></i> Reviewed Season & Summary</button>

<div class="modal fade" id="modal-large" aria-modal="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content"> 
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-6"> 
                        <h4>Reviewed Summary</h4>
                        <p id="txtSummary"></p> 
                     </div>
                    <div class="col-sm-6"> 
                        <h4>Reviewed Season</h4>
                        <p id="txtSeason"></p> 
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">  
    <div class="table-responsive"> 
      <table class="table table-bordered table-hover" id="posts">
          <thead>
              <tr>
              <th>Log id</th>
              <th>Lane name</th>
              <th>Entry/Exit</th>
              <th>Plate no</th>
              <th>Small img</th>
              <th>Big img</th>
              <th>Need adjustment</th>
              <th>Review</th>
              <th>Review Season Subscriber</th>
              <th>Season subscriber</th>
              <th>Date Time</th>
              <th>Success trx</th>
               </tr>
          </thead> 
      </table> 
    </div> 
    </div>
<div>
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
        // filter out the query string since we add in the post data, otherwise, backend will merge the post data and query string
        var queryStart = url.indexOf("?");
	if (queryStart>0) {
		url = url.substr(0, queryStart);
	}
        $('#posts').DataTable({ 
            "processing": true,
            "serverSide": true,
            "searching":true,
            "stateSave": true,
            "ajax":{
            "url": url,
            "dataType": "json",
            "type": "POST", 
	    "data": function ( d ) {
                        return $.extend( {}, d, {
                        _token: "{{csrf_token()}}",
                        "review_flag": $('#review_flag').val(),
                        "season": $('#season').val(),
                        "lanes": $('#lanes').val(),
                        "start_date": $('#start_date').val(),
                        "end_date": $('#end_date').val()
                        } );
                    },
            "dataFilter": function(data){
                var json = jQuery.parseJSON( data ); 
                var summary = json.summary;
                var summaryDisplay = "<hr>";
                var totSummary = 0;

                for (i = 0; i < summary.length; i++) { 
                    if(i>0){
                        var value = parseInt(summary[i].total); 
                        totSummary = totSummary+value;
                    }
                }

                for (j = 0; j < 6; j++) { 
                    if(summary.length>0){
                        if(j>0){ 
                            var resultArray = jQuery.grep(summary, function (n, i) {
                                return (n.flag==j);
                            },false);

                            if(resultArray.length>0){
                                var value = resultArray[0].total;

                                var percentage = (value/totSummary)*100;
                                summaryDisplay +=  reviewedStatus(resultArray[0].flag) + " : "+ value + " (" +percentage.toFixed(2) +"%)<br>"; 

                            }
                            else{
                                summaryDisplay +=  reviewedStatus(j) + " : 0 (0%)<br>"; 
                            }
                            
                            if(j==5){
                                summaryDisplay += "<hr><b>Total Reviewed : "+ totSummary +"</b><span><br><hr><b>Total Unreviewed : "+ summary[0].total +"</b><hr><b>Total All : "+ (totSummary+summary[0].total);
                            }
                        } 
                    }
                }
 
                $('#txtSummary').html(summaryDisplay);

                var season = json.season;
                var seasonDisplay = "<hr>";
                var totSeason = 0;

                for (i = 0; i < season.length; i++) { 
                    var value = parseInt(season[i].total); 
                    totSeason = totSeason+value;
                 }

                for (j = 0; j < 6; j++) { 
                    if(season.length>0){
                        if(j>0){ 
                            var resultArray = jQuery.grep(season, function (n, i) {
                                return (n.flag==j);
                            },false);

                            if(resultArray.length>0){
                                var value = resultArray[0].total;

                                var percentage = (value/totSeason)*100;
                                seasonDisplay +=  reviewedStatus(resultArray[0].flag) + " : "+ value + " (" +percentage.toFixed(2) +"%)<br>"; 

                            }
                            else{
                                seasonDisplay +=  reviewedStatus(j) + " : 0 (0%)<br>"; 
                            }
                            
                            if(j==5){
                                seasonDisplay += "<hr><b>Total Reviewed : "+ totSeason +"</b><span><hr>";
                            }
                        } 
                    }
                }

                $('#txtSeason').html(seasonDisplay);

                return JSON.stringify( json ); 
            }
            },
            "columns": [
                { "data": "id" },
                { "data": "lane_name" },
                { "data": "in_out_flag" },
                { "data": "plate_no" },
                { "data": "img" },
                { "data": "img_cover" },
                { "data": "review_flag" },
                { "data": "review" },
                { "data": "review_season_subscriber" },
                { "data": "is_season_subscriber" },
                { "data": "create_time" },
                { "data": "is_success" }
            ],
            "columnDefs": [ 
                { "targets": [2,5,6,7,10], "orderable": false}

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
				searchPlaceholder: "Search log id , plate no or plate no review ..."
			},
 
        });


        // $(".dataTables__top").hide();

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

        $('#posts').on( 'draw.dt', function () {
 
         });

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

    // $("#exportExcelEntry").click(function(){
    //      var url = window.location.href;;

    //     $.ajax({
    //     type: "POST",
    //     url: url,
    //     dataType: "json",
    //     data: {
    //             "_token": "{{csrf_token()}}",
    //             "review_flag": $('#review_flag').val(),
    //             "season": $('#season').val(),
    //             "lanes": $('#lanes').val(),
    //             "start_date": $('#start_date').val(),
    //             "end_date": $('#end_date').val(),
    //             "export": true
    //             },
    //     success: function (response, textStatus, request) {
    //         var a = document.createElement("a");
    //         a.href = response.file; 
    //         a.download = response.name;
    //         document.body.appendChild(a);
    //         a.click();
    //         a.remove();
    //     }
    //     });

    // });

    $('#posts').on('change', 'td select', function (){
        var result = $(this).val();

        var wrongFlag = result.split('|');
        
        // if(wrongFlag[1]=="2"){
        //     $( '<input type="text" onblur="updatePlateNo('+wrongFlag[0]+',this.value)">').insertAfter(this);
        // }

        if(result!==0){

            updatePlateNo(wrongFlag[0],wrongFlag[1],0);
        
        }
       
    });

    function updatePlateNo(log_id,result,type){

        $.ajax({
            type: "POST",
            url: "{{ route('update_correct_plate') }}",
            data: {log_id: log_id,value:result,type:type},
                success: function(msg){
                    var obj = JSON.parse(msg);
                    var log_id = obj.logs_id;
                    var value = obj.value;

                    $("#"+log_id).text(value);

                 }
            });
    }


    function reviewedStatus(value) {

        var lblFlag = "";

        switch(value) {
            case 0:
                lblFlag = "Unreviewed";
            break;
            case 1:
                lblFlag = "Correct";
            break;
            case 2:
                lblFlag = "Wrong";
            break;
            case 3:
                lblFlag = "Undetected";
            break;
            case 4:
                lblFlag = "Trigger Timing Incorrect";
            break;
            case 5:
                lblFlag = "Invalid";
            break; 
        }

        return lblFlag;
    }
</script>
@endsection
