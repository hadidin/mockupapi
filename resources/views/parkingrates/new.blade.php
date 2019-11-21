
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
 
<font color="#b2b2b2" size="5"><b>ADD NEW PARKING RATE</b></font><br>
<br>

@can('edit_parking_rates', 'delete_parking_rates')

<?php

    if(isset($data['rate_per_hour'])) {

        $count = 0;
        $entry_grace = [];

        foreach($data['rate_per_hour'] as $key=>$val) {
            $entry_grace[$count] = $key;
            $entry_grace_amount[$count] = $val;
            $count++;
        }
    }
?>

<div class="card">
    <div class="card-body">
 
 
            <form name="longsubmit" class="" action="{{route('rates.store')}}" method="post">
                {!! csrf_field() !!}

                <div class="row">
                    <div class="col">
                        <div class="form-group  @if ($errors->has('type')) has-error @endif">
                        <label for="type">Type:</label>                  
                            <select name="type" class="form-control" id="Type">
                                @foreach($types as $type)
                                    <option value="{{$type}}" @if (isset($data['type']) && ($data['type'] ==$type)) selected='selected' @endif >{{$type}}</option>
                                @endforeach
                            </select>
                            <i class="form-group__bar"></i>
                            @if ($errors->has('type')) <p class="help-block">{{ $errors->first('type') }}</p> @endif
                        </div>

                        <div class="form-group @if ($errors->has('entry_grace')) has-error @endif">
                            <label for="entry_grace">Entry Grace Period:</label>
                            <input type="text" @if(isset($entry_grace[0])) value="{{$entry_grace[0]}}"  @else value="0" @endif name="entry_grace" id="EntryGrace" class="form-control" >  
                            <i class="form-group__bar"></i>
                            @if ($errors->has('entry_grace')) <p class="help-block">{{ $errors->first('entry_grace') }}</p> @endif
                        </div>

                        <div class="form-group @if ($errors->has('first_block')) has-error @endif">
                            <label for="first_block">First Block:</label>
                            <input type="text" name="first_block" id="FirstBlock" @if(isset($entry_grace[1])) value="{{$entry_grace[1]}}"  @else value="0" @endif class="form-control" >  
                            <i class="form-group__bar"></i>
                            @if ($errors->has('first_block')) <p class="help-block">{{ $errors->first('first_block') }}</p> @endif
                        </div>
                       
                        <div class="form-group @if ($errors->has('time_block')) has-error @endif">
                        <label for="type">Subsequent Block:</label>                           
                            <input type="text" name="time_block"  id="TimeBlock" @if (isset($data['subsequent_block'])) value="{{$data['subsequent_block']}}" @else value="60" @endif class="form-control"> 
                            <i class="form-group__bar"></i>
                            @if ($errors->has('time_block')) <p class="help-block">{{ $errors->first('time_block') }}</p> @endif
                        </div>

                        <div class="form-group @if ($errors->has('exit_grace')) has-error @endif">
                            <label for="exit_grace">Exit Grace Period:</label>
                            <input type="text" name="exit_grace" id="ExitGrace" @if(isset($data['exit_grace'])) value="{{$data['exit_grace']}}"  @else value="0" @endif class="form-control" >  
                            <i class="form-group__bar"></i>
                            @if ($errors->has('exit_grace')) <p class="help-block">{{ $errors->first('exit_grace') }}</p> @endif
                        </div>

                        <div class="form-group @if ($errors->has('overnight_rate')) has-error @endif">
                            <label for="overnight_rate">Overnight Rate:</label>
                            <input type="text" name="overnight_rate" id="OvernightRate" @if(isset($data['overnight_rates'])) value="{{$data['overnight_rates']}}"  @else value="0" @endif class="form-control" >  
                            <i class="form-group__bar"></i>
                            @if ($errors->has('overnight_rate')) <p class="help-block">{{ $errors->first('overnight_rate') }}</p> @endif
                        </div>

                        <div class="form-group @if ($errors->has('rate_per_entry_start')) has-error @endif">
                            <label for="rate_per_entry_start">Rate Per Entry Start Time:</label>
                            <input type="text" name="rate_per_entry_start" id="RatePerEntryStart" @if(isset($data['start_rate_per_entry'])) value="{{$data['start_rate_per_entry']}}"  @else value="00:00:00" @endif class="form-control" >  
                            <i class="form-group__bar"></i>
                            @if ($errors->has('rate_per_entry_start')) <p class="help-block">{{ $errors->first('rate_per_entry_start') }}</p> @endif
                        </div>

                        <div class="form-group @if ($errors->has('entry_period')) has-error @endif">
                            <label for="entry_period">Entry Period:</label>
                            <select name="entry_period" class="form-control" id="Entry Period">                   
                                <option value="SD" @if(isset($data['entry_period']) && $data['entry_period']=="SD" ) selected="selected" @endif>Same Day</option>
                                <option value="ND" @if(isset($data['entry_period']) && $data['entry_period']=="ND" ) selected="selected" @endif>Next Day</option>                  
                            </select>               
                            <i class="form-group__bar"></i>
                            @if ($errors->has('entry_period')) <p class="help-block">{{ $errors->first('entry_period') }}</p> @endif
                        </div>

                    </div>
`
                    <div class="col">

                        <div class="form-group @if ($errors->has('service')) has-error @endif">
                            <label for="service">Service:</label>

                            <select name="service" class="form-control" id="Service">
                                @foreach($services as $service)
                                    <option value="{{$service}}" @if (isset($data['service']) && ($data['service'] ==$service)) selected='selected' @endif >{{$service}}</option>
                                @endforeach
                            </select>
                        
                            <i class="form-group__bar"></i>
                            @if ($errors->has('service')) <p class="help-block">{{ $errors->first('service') }}</p> @endif
                        </div>
                                                
                        <div class="form-group @if ($errors->has('entry_grace_amount')) has-error @endif">
                            <label for="entry_grace_amount">Entry Grace Amount:</label>
                            <input type="text" name="entry_grace_amount" id="EntryGraceAmount" @if(isset($entry_grace_amount[0])) value="{{$entry_grace_amount[0]}}"  @else value="0" @endif class="form-control" >  
                            <i class="form-group__bar"></i>
                            @if ($errors->has('entry_grace_amount')) <p class="help-block">{{ $errors->first('entry_grace_amount') }}</p> @endif
                        </div>

                        <div class="form-group @if ($errors->has('first_block_amount')) has-error @endif">
                            <label for="first_block_amount">First Block Amount:</label>
                            <input type="text" name="first_block_amount" id="FirstBlockAmount" @if(isset($entry_grace_amount[1])) value="{{$entry_grace_amount[1]}}"  @else value="0" @endif class="form-control" >  
                            <i class="form-group__bar"></i>
                            @if ($errors->has('first_block_amount')) <p class="help-block">{{ $errors->first('first_block_amount') }}</p> @endif
                        </div>                        

                        <div class="form-group @if ($errors->has('subsequent_rate')) has-error @endif">
                            <label for="subsequent_rate">Subsequent Block Amount:</label>
                            <input type="text" name="subsequent_rate" id="SubsequentRate" @if(isset($data['subsequent_rate'])) value="{{$data['subsequent_rate']}}"  @else value="0" @endif class="form-control" >  
                            <i class="form-group__bar"></i>
                            @if ($errors->has('subsequent_rate')) <p class="help-block">{{ $errors->first('subsequent_rate') }}</p> @endif
                        </div>

                        <div class="form-group @if ($errors->has('max_rate')) has-error @endif">
                            <label for="max_rate">Max Rate:</label>
                            <input type="text" name="max_rate" id="MaxRate" @if (isset($data['max_rate'])) value="{{$data['max_rate']}}" @else value="0" @endif class="form-control" >  
                            <i class="form-group__bar"></i>
                            @if ($errors->has('max_rate')) <p class="help-block">{{ $errors->first('max_rate') }}</p> @endif
                        </div>

                        <div class="form-group @if ($errors->has('rate_per_entry')) has-error @endif">
                            <label for="rate_per_entry">Rate Per Entry:</label>
                            <input type="text" name="rate_per_entry" id="RatePerEntry" @if (isset($data['rate_per_entry'])) value="{{$data['rate_per_entry']}}" @else value="0" @endif class="form-control" >  
                            <i class="form-group__bar"></i>
                            @if ($errors->has('rate_per_entry')) <p class="help-block">{{ $errors->first('rate_per_entry') }}</p> @endif
                        </div>

                        <div class="form-group @if ($errors->has('rate_per_entry_end')) has-error @endif">
                            <label for="rate_per_entry_end">Rate Per Entry End Time:</label>
                            <input type="text" name="rate_per_entry_end" id="RatePerEntryEnd" @if (isset($data['end_rate_per_entry'])) value="{{$data['end_rate_per_entry']}}" @else value="00:00:00" @endif class="form-control" >  
                            <i class="form-group__bar"></i>
                            @if ($errors->has('rate_per_entry_end')) <p class="help-block">{{ $errors->first('rate_per_entry_end') }}</p> @endif
                        </div>

                        

                    </div>
                </div>
            
            @if(isset($data['id']))
                <input type="hidden" name="id" value="{{$data['id']}}"  >
            @endif
            <!-- Submit Form Button -->
            <button type="submit" class="btn btn-primary" onClick="return submitForm(document.longsubmit, this)">Submit</button>
            <button type="button" class="btn btn-danger" onClick="window.history.back()">Cancel</button>
         

 </div>

 </div>

@endcan

@endsection

<script>
    function getFirstBlock() {
        var TimeBlock= $('#TimeBlock').val();
        var FirstBlock = parseInt(TimeBlock)  - parseInt($('#EntryGrace').val());
        $('#FirstBlock').val(FirstBlock);
    }

</script>
