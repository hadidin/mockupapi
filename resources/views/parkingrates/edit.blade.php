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
 
<font color="#b2b2b2" size="5"><b>EDIT USER</b></font><br>
<br>


<div class="card">
    <div class="card-body">
 

    <div class="wrapper wrapper-content animated fadeInRight">
        <div class="row">
            <div class="col-lg-12">
                <div class="ibox float-e-margins">
                    <div class="ibox-content">

 
                    <form class="" action="{{url('users/'.$user->id)}}" method="post">
                        {!! csrf_field() !!}
				<input type="hidden" name="_method"  value="put">

 
                        <!-- Name Form Input -->
            <div class="form-group @if ($errors->has('name')) has-error @endif">
                <label for="name">Name:</label>
                <input type="text" name="name" placeholder="name" value="{{ $user->name }}" class="form-control" id="Name"> 
                <i class="form-group__bar"></i>
                @if ($errors->has('name')) <p class="help-block">{{ $errors->first('name') }}</p> @endif
            </div>

            <!-- email Form Input -->
            <div class="form-group @if ($errors->has('email')) has-error @endif">
            <label for="email">Email:</label>
                <input type="email" name="email" placeholder="Email" value="{{ $user->email }}" class="form-control" id="Email"> 
                <i class="form-group__bar"></i>
                 @if ($errors->has('email')) <p class="help-block">{{ $errors->first('email') }}</p> @endif
            </div>

            <div class="form-group @if ($errors->has('phone_no')) has-error @endif">
                <label for="phone_no">Phone Number:</label>
                <input type="text" name="phone_no" placeholder="Phone Number" value="{{ $user->phone_no }}" class="form-control" id="phone_no"> 
                <i class="form-group__bar"></i>
                @if ($errors->has('phone_no')) <p class="help-block">{{ $errors->first('phone_no') }}</p> @endif
            </div>

            <!-- password Form Input -->
            <div class="form-group @if ($errors->has('password')) has-error @endif">
                <label for="password">Password:</label>
                <input type="password" name="password" placeholder="Password" class="form-control" id="Password">  
                <i class="form-group__bar"></i>
                 @if ($errors->has('password')) <p class="help-block">{{ $errors->first('password') }}</p> @endif
            </div>
 
            

            <!-- Roles Form Input -->
            <div class="form-group @if ($errors->has('roles')) has-error @endif">
                <label for="roles[]">Roles:</label>
 
                    <select class="form-control" id="roles" name="roles[]" multiple>
                    @foreach($roles2 as $role2) 
                        @if(sizeof($user->roles->pluck('id'))>0){
                            @if($role2->id==$user->roles->pluck('id')[0])
                            <option value="{{$role2->id}}" selected>{{$role2->name}}</option>
                            @else
                            <option value="{{$role2->id}}">{{$role2->name}}</option>
                            @endif
                        @else
                        <option value="{{$role2->id}}">{{$role2->name}}</option>
                        @endif    
                    @endforeach
                    </select>

 
                @if ($errors->has('roles')) <p class="help-block">{{ $errors->first('roles') }}</p> @endif
            </div>

            <label>Status :</label>

            <div class="custom-control custom-radio">
                <input type="radio" value="1" id="status1" name="status" class="custom-control-input" 
                    @if($user->status==1)
                    checked="checked" 
                    @endif> 
                <label class="custom-control-label" for="status1">Active</label>
            </div>

            <div class="clearfix mb-2"></div>

            <div class="custom-control custom-radio">
                <input type="radio" value="0" id="status2" name="status" class="custom-control-input" 
                    @if($user->status==0)
                    checked="checked"
                    @endif> 
                <label class="custom-control-label" for="status2">Not Active</label>
            </div>
            <hr>
                        
                        <!-- Permissions -->
                        @if(isset($user))
                            @include('shared._permissions', ['closed' => 'true', 'model' => $user ])
                        @endif
                            
                        <!-- Submit Form Button -->
                        <button type="submit" class="btn btn-primary" onClick="return submitForm(document.longsubmit, this)">Submit</button>
                     </div>
                </div>
            </div>
        </div>
    </div>





</div>
@endsection
