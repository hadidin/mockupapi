@can('edit_'.$entity)
    <a href="{{ route($entity.'.edit', [str_singular($entity) => $id])  }}" class="btn btn-md btn-light">
    <i class="zmdi zmdi-edit"></i></a>
@endcan

@can('delete_'.$entity)

<form method="post" style="display: inline" action="{{ route($entity.'.destroy', ['user' => $id]) }}">
                            <input type="hidden" name="_method" value="delete" />
                            {!! csrf_field() !!}
                            <button onclick="return confirm('Confrm Delete?')"  class="btn-delete btn btn-md btn-light"> <i class="zmdi zmdi-delete"></i></button>
                            </form>

 
@endcan
 
