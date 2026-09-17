<form id="role-form" method="POST" action="{{ route('roles') }}">
    @csrf
    <input type="hidden" name="id" id="edit_id">
    <input type="hidden" name="_method" id="role_method" value="">
    <div class="row form-row">
        <div class="col-12">
            <div class="form-group">
                <label>Role</label>
                <input type="text" name="role" class="form-control edit_role">
            </div>
            <div class="form-group">
                <label>Select Permissions</label>
                <div class="row">
                    @foreach ($permissions as $permission)
                        <div class="col-md-6 mb-2">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input edit_perms"
                                    id="permission_{{ $loop->index }}" name="permission[]"
                                    value="{{ $permission->name }}">
                                <label class="custom-control-label" for="permission_{{ $loop->index }}">
                                    {{ $permission->name }}
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <button type="submit" class="btn btn-primary btn-block">Save Changes</button>
</form>