<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{

    public function index()
    {
        abort_unless(auth()->user()->hasRole('super-admin'), 403);
        $title = "Permissions";
        $permissions =Permission::get();
        return view('permissions.permissions',compact(
            'title','permissions'
        ));
    }


    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasRole('super-admin'), 403);
        $this->validate($request,[
            'permission'=>'required|max:200',
        ]);
        foreach (explode(',',$request->permission) as  $perm) {
            $permission = Permission::create(['name' => $perm]);
            $permission->assignRole('super-admin');
        }
        $notification = notify('Permission Created Successfully!!');
        return back()->with($notification);
    }


    public function update(Request $request)
    {
        abort_unless(auth()->user()->hasRole('super-admin'), 403);
        $this->validate($request,[
            'permission'=>'required|max:200',
        ]);
        $permission = Permission::findOrFail($request->id);
        $permission->update([
            'name' => $request->permission,
        ]);
        $notification = notify('permission updated');
        return back()->with($notification);
    }


    public function destroy(Request $request)
    {
        abort_unless(auth()->user()->hasRole('super-admin'), 403);
        $permission = Permission::find($request->id);
        $permission->delete();
        $notification = notify('Permission has been deleted');
        return back()->with($notification);
    }
}
