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

    <br>
    <!--
    <ol class="breadcrumb">
        <li class="breadcrumb-item active">Index</li>
    </ol>
    -->

    <font color="#b2b2b2" size="5"><b>PAYMENT LOGS</b></font><br>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>Bil</th>
                        <th>Log Id</th>
                        <th>Ticket ID</th>
                        <th>Status</th>
                        <th>Ref Id</th>
                        <th>Amount</th>

                        <th>User</th>
                        <th>Plate Number</th>
                        <th>Ext Ref Id</th>
                        <th>Datetime</th>

                    </tr>
                    </thead>
                    @php
                        if(isset($_GET['page'])){
                            $page=$_GET['page'];
                        }
                        else{
                            $page=1;
                        }
                        $page=$page-1;
                    @endphp

                    @foreach ($trx_list as $index => $trx)
                        @php
                            $a=$index+1;
                            $bil=$a+$page*$offset;
                            $amount = $trx->amount/100;
                            $amount = number_format((float)$amount, 2, '.', '');
                        @endphp

                        <tr></tr>
                        <td>{{ $bil }}</td>
                        <td>{{ $trx->id }}</td>
                        <td>{{ $trx->ticket_id }}</td>
                        <td>
                            @if($trx->status_id == 0)
                                Payment process start
                            @elseif($trx->status_id == 1)
                                Sending to kiple payment
                            @elseif($trx->status_id == 2)
                                <font color="green"> Payment succeed with kiplepay<font>
                            @elseif($trx->status_id == 3)
                                <font color="orange"> Payment failed with kiplepay</font>
                            @elseif($trx->status_id == 4)
                                Request autovoid
                            @elseif($trx->status_id == 5)
                                <font color="green">Autovoid success</font>
                            @elseif($trx->status_id == 6)
                                <font color="red">Autovoid failed</font>
                            @else
                                Unknown
                            @endif
                        </td>
                        <td>{{ $trx->external_ref_id }}</td>
                        <td>MYR {{ $amount }}</td>
                        <td>{{ $trx->user_id }}</td>
                        <td>{{ $trx->plate_no }}</td>
                        <td>{{ $trx->vendor_ticket_id }}</td>

                        <td>{{ $trx->trx_date }}</td>

                    @endforeach
                </table>
                {{ $trx_list->links() }}
            </div>
        </div>
    </div>
@endsection
