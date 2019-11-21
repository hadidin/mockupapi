<div class="card my-3">
    <div class="card-header" role="tab" id="{{ isset($title) ? str_slug($title) :  'permissionHeading' }}">
        <h4 class="mb-0">
            <a role="button" data-toggle="collapse" href="#dd-{{ isset($title) ? str_slug($title) :  'permissionHeading' }}" aria-expanded="{{ isset($closed) ? 'true' : 'false' }}" aria-controls="dd-{{ isset($title) ? str_slug($title) :  'permissionHeading' }}">
                {{ $title ?? 'Override Permissions' }} {!! isset($user) ? '<span class="text-danger">(' . $user->getDirectPermissions()->count() . ')</span>' : '' !!}
            </a>
        </h4>
    </div>
    <div>
        <div class="card-body">
            <div class="row">
                @foreach($permissions as $perm)
                    <?php
                        $per_found = null;

                        if( isset($role) ) {
                            $per_found = $role->hasPermissionTo($perm->name);
                        }

                        if( isset($user)) {
                            $per_found = $user->hasDirectPermission($perm->name);
                        }
                    ?>

                    <div class="col-md-4">
                        <div class="checkbox">
                            <label class="{{ str_contains($perm->name, 'delete') ? 'text-danger' : '' }}">
                                @if($per_found)
                                <input type="checkbox" name="permissions[]" value="{{$perm->name}}" checked>&nbsp;
                                @else
                                <input type="checkbox" name="permissions[]" value="{{$perm->name}}">&nbsp;
                                @endif
                                <button class="btn btn-link" data-toggle="popover" data-trigger="hover" title="" data-content="{{$perm->descriptions}}" data-original-title="{{$perm->name}}">{{$perm->name}}</button>
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
