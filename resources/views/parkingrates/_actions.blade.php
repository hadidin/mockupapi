
    <a href="{{ route($entity.'.edit', [str_singular($entity) => $id]) }}" class="btn btn-md btn-light">
    <i class="zmdi zmdi-edit"></i></a>

    <form method="get" style="display: inline" action="{{ route($entity.'.destroy') }}">
        <input type="hidden" name="id" value="{{$id}}" />
        <input type="hidden" name="_method" value="delete" />
        {!! csrf_field() !!}
        <button onclick="return confirm('Confrm Delete?')"  class="btn-delete btn btn-md btn-light"> <i class="zmdi zmdi-delete"></i></button>
    </form>