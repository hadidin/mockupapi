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
 
<font color="#b2b2b2" size="5"><b>LIST OF ROLES</b></font><br>
<br>


<div class="card">
    <div class="card-body">



 
    <!-- Modal -->
    <div class="modal fade" id="roleModal" tabindex="-1" role="dialog" aria-labelledby="roleModalLabel">
        <div class="modal-dialog" role="document">
            <form name="longsubmit" class="" action="{{url('roles')}}" method="post">
                {!! csrf_field() !!}

            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="roleModalLabel">Role</h4> 
                </div>
                <div class="modal-body">
                    <!-- name Form Input -->
                    <div class="form-group @if ($errors->has('name')) has-error @endif">
                    <label for="name">Name:</label>
                <input type="text" name="name" placeholder="name" value="{{ old('name') }}" class="form-control" id="Name"> 
                <i class="form-group__bar"></i>
                @if ($errors->has('name')) <p class="help-block">{{ $errors->first('name') }}</p> @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">CLOSE</button>

                    <!-- Submit Form Button -->
                    <button type="submit" class="btn btn-link" onClick="return submitForm(document.longsubmit, this)">Submit</button>
                </div>
                </form>
            </div>
        </div>
    </div>

    <div class="flash-message">
  @foreach (['danger', 'warning', 'success', 'info'] as $msg)
    @if(Session::has('alert-' . $msg))
    <p class="alert alert-{{ $msg }}">{{ Session::get('alert-' . $msg) }}</p>
    @endif
  @endforeach
</div>

    <div class="row">
        <div class="col-md-5">
         </div>
        <div class="col-md-7 page-action text-right">
            @can('add_roles')
                <a href="#" class="btn btn-success pull-right" data-toggle="modal" data-target="#roleModal"> <i class="glyphicon glyphicon-plus"></i> Add New Role</a>
            @endcan
        </div>
    </div>


    @forelse ($roles as $role)
 
        <form class="m-b" action="{{url('roles/'.$role->id)}}" method="post">
                        {!! csrf_field() !!}
				<input type="hidden" name="_method"  value="put">

        @if($role->name === 'Admin')
            @include('shared._permissions', [
                          'title' => $role->name .' Permissions',
                          'options' => ['disabled'] ])
        @else
            @include('shared._permissions', [
                          'title' => $role->name .' Permissions',
                          'model' => $role ])
            @can('edit_roles')
            <button type="submit" class="btn btn-primary" onClick="return submitForm(document.longsubmit, this)">Submit</button>
            @endcan
        @endif
        </form>

    @empty
        <p>No Roles defined, please run <code>php artisan db:seed</code> to seed some dummy data.</p>
    @endforelse



 
</div>
@endsection
