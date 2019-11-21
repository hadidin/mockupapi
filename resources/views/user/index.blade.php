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
 
<font color="#b2b2b2" size="5"><b>LIST OF USERS</b></font><br>
<br>

<div class="card">
    <div class="card-body">
 
 
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
                <th>Id</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Created At</th>
                @can('edit_users', 'delete_users')
                <th class="text-center">Actions</th>
                @endcan
            </tr>
            </thead>
            <tfoot> 
            <tbody>
            @foreach($result as $item)
                <tr>
                    <td>{{ $item->id }}</td>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->email }}</td>
                    <td>{{ $item->roles->implode('name', ', ') }}</td>
                    <td>{{ $item->created_at->toFormattedDateString() }}</td>

                    @can('edit_users')
                    <td class="text-center">
                        @include('shared._actions', [
                            'entity' => 'users',
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
     @can('add_users')
                <a href="{{ route('users.create') }}" class="btn btn-primary"> <i class="glyphicon glyphicon-plus-sign"></i> Create New User</a>
            @endcan
    </div>
 </div>
</div>
@endsection
