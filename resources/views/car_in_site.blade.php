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
 
<font color="#b2b2b2" size="5"><b>CAR IN PARK</b></font><br>
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
        <div class="table-responsive"> 
            <table id="data-table" class="table table-bordered table-hover">
                <thead>
                <tr>
                    <th>Bil</th>
                    <th>Card ID</th>
                    <th>Type</th>
                    <th>Ext Ref Id</th>
                    <th>Plate No</th>
                    <th>Lane</th>
                    <th>Entry Time</th>
                    <th>Locked Status</th>
                    <th>User Name</th>
                    <th>Full Img</th>
                    <th>Action</th>
                </tr>
                </thead> 
                <tbody>
                @foreach ($car_insite_list as $index => $car)
                    <tr>
                    <td>{{$index + 1}}</td>
                    <td>{{ $car->season_holder_id }}</td>
                    <td>
                        @if( $car->parking_type==0)
                            Normal Parking
                        @elseif( $car->parking_type==1)
                            Season parking
                        @endif
                    </td>
                    <td>{{ $car->vendor_ticket_id }}</td>
                    <td>{{ $car->plate_no }}</td>
                    <td>{{ $car->lane_name }}</td>
                    <td>{{ $car->created_at }}</td>
                    <td>
                        @if( $car->locked_flag==1)
                            <font color="green">true</font>
                        @else
                            <font color="red">false</font>
                        @endif
                    </td>
                    <td>{{ $car->user_name }}</td>
                    <td>

                    <a data-toggle="modal" data-id="http://{{$lpr_backend_server_base_url}}{{$car->big_picture}}" title="Add this item" class="open-AddImageDialog btn-link btn-primary" href="#addImageDialog"><img src="http://{{$lpr_backend_server_base_url}}{{$car->big_picture}}" width="100" height="50"/></a>

                    </td>

                    <?php
                    $car_plate=$car->plate_no;
                    $car_color=$car->car_color;
                    $card_id=$car->season_holder_id;
                    $param="plate_no=$car_plate&car_color=$car_color&card_id=$card_id";
                    ?>
                    <td>
                        
                        @php
                            if($car -> parking_type == 1){
                                $car_plate=$car->plate_no;
                                $car_color=$car->car_color;
                                $card_id=$car->season_holder_id;
                                $ticket_id = $car -> id_tbl_car_in_site;
                                $param="plate_no=$car_plate&car_color=$car_color&card_id=$card_id&ticket_id=$ticket_id";
                                echo "<a class='btn btn-outline-primary' onclick='return confirm_delete()' href='".url('/manual_exit')."?$param'>
                                Manual Exit season</a>";
                            }

                            if($car -> parking_type == 0){
                                $ticket_id = $car -> id_tbl_car_in_site;
                                $plate_no = $car -> plate_no;
                                $param = "ticket_id=$ticket_id&plate_no=$plate_no";
                                echo "<a class='btn btn-outline-primary' onclick='return confirm_delete()' href='".url('/manual_exit_normal')."?$param'>
                                Manual Exit normal</a>";
                            }
                        @endphp

                    </td>
                    </tr>
                @endforeach
            </tbody>
            </table> 
        </div>
    </div>
</div> 
		    <script>
		    $(document).on("click",".open-AddImageDialog", function() {
			    var myImageId = $(this).data('id');
			    $(".modal-content #imageId").val(myImageId);
			    document.getElementById("imageView").src = myImageId;
		    });

		   </script>
 
@endsection
