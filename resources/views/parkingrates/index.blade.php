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
 
<font color="#b2b2b2" size="5"><b>Parking Rates</b></font><br>
<br>

<div class="card" >
    <div class="card-body" style="width:auto">
 
 
        <div class="flash-message">
  @foreach (['danger', 'warning', 'success', 'info'] as $msg)
    @if(Session::has('alert-' . $msg))
    <p class="alert alert-{{ $msg }}">{{ Session::get('alert-' . $msg) }}</p>
    @endif
  @endforeach
</div>


    <div class="result-set">
    <table id="data-table" class="table table-bordered table-hover">
            <thead>
            <tr>
                <th>Type</th>
                <th>Subsequent Block</th>               
                <th>Entry Grace Period</th>
                <th>First Block</th>
                <th>Subsequent Rate</th>
                <th>Max Rate</th>
                <th>Overnight Rate</th>
                <th>Rate per Entry</th>
                <th>Day</th>
                <th>Service</th>
                @can('edit_parking_rates', 'delete_parking_rates')
                <th class="text-center">Actions</th>
                @endcan
            </tr>
            </thead>
            <tfoot> 
            <tbody>
            @foreach($result as $item)
                <tr>
                    <td>{{ ucwords($item->type) }}</td>
                    <td>{{ $item->subsequent_block }}</td>
                    <?php

                        $blockTime = 0;
                        $blockRate = 0;
                        $count = 0;
                        $td1 = '';
                        $td2 = '';

                        foreach( $item['rate_per_hour'] as $key=>$val) {
                            $blockTime += $key;
                            $blockRate += $val;
                            $count++;
                            
                            if($count==1) {
                               $td1 = $blockTime;
                            }
                            if($count==2) {
                                $td2 = $blockTime."Min : MYR".$blockRate;
                            }
                        }
                    
                    ?>
                    <td>{{ $td1 }}</td>
                    <td>{{ $td2 }}</td>
                    <td>{{ $item->subsequent_rate }}</td>
                    <td>{{ $item->max_rate }}</td>
                    <td>{{ $item->overnight_rates }}</td>
                    <td>{{ $item->rate_per_entry }}</td>
                    <td>{{ ($item->entry_period=='SD')? 'Same Day': 'Next Day' }}</td>
                    <td>{{ $item->service}}</td>

                    @can('edit_parking_rates', 'delete_parking_rates')
                    <td class="text-center">
                        @include('parkingrates._actions', [
                            'entity' => 'parking_rates',
                            'id' => $item->id
                        ])
                    </td>
                    @endcan
                </tr>
            @endforeach
            </tbody>
        </table>

        <div class="text-center">
            {{ $result->links() }}
        </div>
    

        <div class="row">
    @can('add_parking_rates')
        <a href="{{ route('rates.create') }}" class="btn btn-primary"> <i class="glyphicon glyphicon-plus-sign"></i> Add parking rates</a>
    @endcan
    </div>
 </div>
</div>
@endsection
