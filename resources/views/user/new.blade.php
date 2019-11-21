
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
 
<font color="#b2b2b2" size="5"><b>CREATE NEW USER</b></font><br>
<br>

<div class="card">
    <div class="card-body">
 
 
            <form name="longsubmit" class="" action="{{route('users.store')}}" method="post">
                {!! csrf_field() !!}

            <!-- Name Form Input -->
            <div class="form-group @if ($errors->has('name')) has-error @endif">
                <label for="name">Name:</label>
                <input type="text" name="name" placeholder="name" value="{{ old('name') }}" class="form-control" id="Name"> 
                <i class="form-group__bar"></i>
                @if ($errors->has('name')) <p class="help-block">{{ $errors->first('name') }}</p> @endif
            </div>

            <!-- email Form Input -->
            <div class="form-group @if ($errors->has('email')) has-error @endif">
            <label for="email">Email:</label>
                <input type="email" name="email" placeholder="Email" value="{{ old('email') }}" class="form-control" id="Email"> 
                <i class="form-group__bar"></i>
                @if ($errors->has('email')) <p class="help-block">{{ $errors->first('email') }}</p> @endif
            </div>

            <!-- password Form Input -->
            <div class="form-group @if ($errors->has('password')) has-error @endif">
                <label for="password">Password:</label>
                <input type="password" name="password" placeholder="Password" value="{{ old('password') }}" class="form-control" id="Password">  
                <i class="form-group__bar"></i>
                @if ($errors->has('password')) <p class="help-block">{{ $errors->first('password') }}</p> @endif
            </div>
 
            <!-- Roles Form Input -->
            <div class="form-group @if ($errors->has('roles')) has-error @endif">
                <label for="roles[]">Roles:</label>

                <select class="form-control" id="roles" name="roles[]" multiple>
                    @foreach($roles as $role) 
                        <option value="{{$role->id}}" >{{$role->name}}</option>
                    @endforeach 
                </select>

                @if ($errors->has('roles')) <p class="help-block">{{ $errors->first('roles') }}</p> @endif
            </div>

            <!-- Permissions -->
            @if(isset($user))
                @include('shared._permissions', ['closed' => 'true', 'model' => $user ])
            @endif
                <!-- Submit Form Button -->
				<button type="submit" class="btn btn-primary" onClick="return submitForm(document.longsubmit, this)">Submit</button>
         

 </div>

 </div>
@endsection
