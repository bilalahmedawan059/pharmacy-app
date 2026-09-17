<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{

    public function index()
    {
        abort_unless(auth()->user()->hasRole('super-admin') || auth()->user()->can('view-role'), 403);
        $title = "user Roles";
        $roles = auth()->user()->hasRole('super-admin')
            ? Role::with('permissions')->get()
            : Role::with('permissions')->where('pharmacy_id', auth()->user()->pharmacy_id)->get();
        $permissions = Permission::get();
        return view('roles.roles',compact(
            'title','roles','permissions'
        ));
    }


    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasRole('super-admin') || auth()->user()->can('create-role'), 403);
        $this->validate($request,[
            'role'=>'required|max:100',
            'permission'=>'nullable|array',
        ]);
        $role = Role::create(['name' => $request->role, 'guard_name' => 'web']);
        $permissions = array_values(array_filter($request->input('permission', [])));
        $role->syncPermissions($permissions);
        $notification = array(
            'message'=>"Role Created Successfully!!",
            'alert-type'=>"success"
        );
        return back()->with($notification);
    }


    public function update(Request $request)
    {
        abort_unless(auth()->user()->hasRole('super-admin') || auth()->user()->can('update-role'), 403);
        $this->validate($request,[
            'id'=>'required|integer',
            'role'=>'required|max:100',
            'permission'=>'nullable|array',
        ]);
        $role = Role::withoutGlobalScopes()->findOrFail($request->id);
        abort_unless($role->pharmacy_id === auth()->user()->pharmacy_id || auth()->user()->hasRole('super-admin'), 403);
        $role->update([
            'name'=>$request->role,
        ]);
        $permissions = array_values(array_filter($request->input('permission', [])));
        $role->syncPermissions($permissions);
        $notification = array(
            'message'=>"Role Updated Successfully!!",
            'alert-type'=>"success"
        );
        return back()->with($notification);
    }


    public function destroy(Request $request)
    {
        abort_unless(auth()->user()->hasRole('super-admin') || auth()->user()->can('destroy-role'), 403);
        $role = Role::findOrFail($request->id);
        abort_unless($role->pharmacy_id === auth()->user()->pharmacy_id || auth()->user()->hasRole('super-admin'), 403);
        $role->delete();
        $notification = array(
            'message'=>"Role deleted successfully!!.",
            'alert-type'=>'success'
        );
        return back()->with($notification);
    }
}
