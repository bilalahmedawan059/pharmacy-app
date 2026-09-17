<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Branch;
use App\Models\Pharmacy;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{

    public function index()
    {
        $title = "users";
        $this->authorize('view-users');
        $usersQuery = User::with('roles');
        if (!auth()->user()->hasRole('super-admin')) {
            $usersQuery->where('pharmacy_id', auth()->user()->pharmacy_id);
        }
        $users = $usersQuery->get();
        $roles = Role::query()->where('name', '!=', 'super-admin')->orderBy('name')->get();
        $pharmacies = auth()->user()->hasRole('super-admin')
            ? Pharmacy::orderBy('business_name')->get()
            : Pharmacy::whereKey(auth()->user()->pharmacy_id)->get();
        return view('users.users',compact(
            'title','users','roles','pharmacies'
        ));
    }

    public function store(Request $request){
        $this->authorize('create-user');

        $isSuperAdmin = auth()->user()->hasRole('super-admin');
        $pharmacyId = $isSuperAdmin
            ? $request->input('pharmacy_id')
            : auth()->user()->pharmacy_id;

        $notification = null;
        
        $this->validate($request,[
            'name'=>'required|max:100',
            'email'=>'required|email',
            'role'=>'required',
            'pharmacy_id'=>[$isSuperAdmin ? 'required' : 'nullable', 'integer', 'exists:pharmacies,id'],
            'password'=>'required|confirmed|max:200',
            'avatar'=>'file|image|mimes:jpg,jpeg,gif,png',
        ]);
        $role = Role::where('name', $request->role)->firstOrFail();
        if ($request->role === 'super-admin' && !auth()->user()->hasRole('super-admin')) {
            abort(403);
        }
        $imageName = null;
        $branch = $request->branch_id
            ? Branch::where('pharmacy_id', $pharmacyId)->findOrFail($request->branch_id)
            : null;
        if($request->hasFile('avatar')){
            $imageName = time().'.'.$request->avatar->extension();
            $request->avatar->move(public_path('storage/users'), $imageName);
        }
            try {
                $user = User::create([
                    'pharmacy_id'=>$pharmacyId,
                    'branch_id'=>optional($branch)->id,
                    'name'=>$request->name,
                    'email'=>$request->email,
                    'password'=>Hash::make($request->password),
                    'avatar'=>$imageName
                ]);
                $user->assignRole($role);
                $notification =array(
                    'message'=>"User has been added!!!",
                    'alert-type'=>'success'
                );
            } catch (\Throwable $th) {
                $notifications = array(
                    'message' => "Opps!! Something got wrong, Please check and try again",
                    'alert-type' => 'error',
                );
            }
        return back()->with($notification);
    }

    public function profile()
    {
        $title = "profile";
        $roles = Role::query()->where('name', '!=', 'super-admin')->orderBy('name')->get();
        return view('users.profile',compact(
            'title','roles'
        ));
    }

    public function updateProfile(Request $request)
    {
        $this->validate($request,[
            'name'=>'required|max:100',
            'email'=>'required|email',
            'avatar'=>'file|image|mimes:jpg,jpeg,gif,png',
        ]);
        $role = Role::where('name', $request->role)->firstOrFail();
        if ($request->role === 'super-admin' && !auth()->user()->hasRole('super-admin')) {
            abort(403);
        }
        if($request->hasFile('avatar')){
            $imageName = time().'.'.$request->avatar->extension();
            $request->avatar->move(public_path('storage/users'), $imageName);
        }else{
            $imageName = auth()->user()->avatar;
        }
            try {
                auth()->user()->update([
                    'name'=>$request->name,
                    'email'=>$request->email,
                    'avatar'=>$imageName,
                ]);
                $notification =array(
                    'message'=>"User profile has been updated !!!",
                    'alert-type'=>'success'
                );
            } catch (\Throwable $th) {
                $notifications = array(
                    'message' => "Opps!! Something got wrong, Please check and try again",
                    'alert-type' => 'error',
                );
            }
        return back()->with($notification);
    }

    public function updatePassword(Request $request)
    {
        $this->validate($request,[
            'old_password'=>'required',
            'password'=>'required|max:200|confirmed',
        ]);

        if (password_verify($request->old_password,auth()->user()->password)){
            auth()->user()->update(['password'=>Hash::make($request->password)]);
            $notification = array(
                'message'=>"User password updated successfully!!!",
                'alert-type'=>'success'
            );
            $logout = auth()->logout();
            return back()->with($notification,$logout);
        }else{
            $notification = array(
                'message'=>"Old Password do not match!!!",
                'alert-type'=>'danger'
            );
            return back()->with($notification);
        }
    }


    public function update(Request $request)
    {
        $actor = auth()->user();
        $this->authorize('update-user');

        $isSuperAdmin = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_id', $actor->id)
            ->where('model_has_roles.model_type', User::class)
            ->where('roles.name', 'super-admin')
            ->where('roles.guard_name', 'web')
            ->exists();

        $userQuery = User::withoutGlobalScopes();
        if (!$isSuperAdmin) {
            $userQuery->where('pharmacy_id', $actor->pharmacy_id);
        }

        $user = $request->filled('id')
            ? $userQuery->find($request->id)
            : $userQuery->where('email', $request->input('original_email'))->first();

        if (!$user) {
            return back()->withInput()->withErrors([
                'id' => 'The selected user could not be found in your permitted pharmacy.',
            ]);
        }

        $request->merge(['id' => $user->id]);

        $this->validate($request, [
            'id' => 'required|integer',
            'name' => 'required|max:100',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($request->id),
            ],
            'role' => 'nullable|string',
            'password' => 'nullable|confirmed|max:200',
            'avatar' => 'nullable|file|image|mimes:jpg,jpeg,gif,png',
        ]);

        if ($request->filled('role')) {
            abort_unless($isSuperAdmin || $actor->can('update-role'), 403);

            if ($request->role === 'super-admin' && !$isSuperAdmin) {
                abort(403);
            }
        }

        $role = null;
        if ($request->filled('role')) {
            $role = Role::withoutGlobalScopes()
                ->where('name', $request->role)
                ->where('guard_name', 'web')
                ->first();

            if (!$role) {
                return back()->withInput()->withErrors([
                    'role' => 'The selected role does not exist.',
                ]);
            }
        }

        $updates = [
            'name' => $request->name,
            'email' => $request->email,
            'avatar' => $user->avatar,
        ];

        if ($request->filled('password')) {
            $updates['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('avatar')) {
            $avatarName = time() . '.' . $request->avatar->extension();
            $request->avatar->move(public_path('storage/users'), $avatarName);
            $updates['avatar'] = $avatarName;
        }

        $user->update($updates);

        if ($role) {
            $user->syncRoles([$role]);
        }
        $notification =array(
            'message'=>"User has been updated!!!",
            'alert-type'=>'success'
        );
        return back()->with($notification);
    }


    public function destroy(Request $request)
    {
        $this->authorize('destroy-user');
        $user = User::where('pharmacy_id', auth()->user()->pharmacy_id)->findOrFail($request->id);
        if($user->hasRole('super-admin')){
            $notification=array(
                'message'=>"Super admin cannot be deleted",
                'alert-type'=>'warning',
            );
            return back()->with($notification);
        }
        $user->delete();
        $notification=array(
            'message'=>"User has been deleted",
            'alert-type'=>'success',
        );
        return back()->with($notification);
    }
}
