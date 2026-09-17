<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Pharmacy;
use App\Models\User;
use App\Models\Role;
use App\Notifications\StaffInviteNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class RegisterController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function index(Request $request)
    {
        $data = $request->session()->get('onboarding', []);
        $step = (int) $request->query('step', $request->session()->get('onboarding_step', 1));
        $step = max(1, min($step, 4));
        $roles = Role::query()->orderBy('name')->pluck('name');

        return view('auth.onboarding', compact('data', 'roles', 'step'));
    }

    public function step(Request $request, $step)
    {
        $step = (int) $step;
        abort_unless(in_array($step, [1, 2, 3], true), 404);

        $validated = $step === 1
            ? $this->validatePharmacy($request)
            : ($step === 2 ? $this->validateBranches($request) : $this->validateStaff($request));

        $onboarding = $request->session()->get('onboarding', []);
        $onboarding[$step === 1 ? 'pharmacy' : ($step === 2 ? 'branches' : 'staff')] = $validated;
        $request->session()->put('onboarding', $onboarding);
        $request->session()->put('onboarding_step', min($step + 1, 4));

        return redirect()->route('register');
    }

    public function launch(Request $request)
    {
        $data = $request->session()->get('onboarding', []);
        abort_unless(isset($data['pharmacy'], $data['branches'], $data['staff']), 422);
        $request->validate(['confirm' => ['accepted']]);

        $owner = null;
        $invites = [];
        DB::transaction(function () use ($data, &$owner, &$invites) {
            $pharmacy = Pharmacy::create([
                'business_name' => $data['pharmacy']['business_name'],
                'owner_name' => $data['pharmacy']['owner_name'],
                'owner_email' => $data['pharmacy']['owner_email'],
                'tax_type' => $data['pharmacy']['tax_type'],
                'tax_id' => preg_replace('/[-\s]/', '', $data['pharmacy']['tax_id']),
                'address' => $data['pharmacy']['address'],
                'contact_address' => $data['pharmacy']['address'],
                'city' => $data['pharmacy']['city'],
                'phone_number' => $data['pharmacy']['phone'],
                'status' => 'active',
                'launched_at' => now(),
                'is_manufacturing' => false,
                'max_employees' => count($data['staff']) + count($data['branches']),
            ]);

            $branches = [];
            foreach ($data['branches'] as $branchData) {
                $branches[] = Branch::create(array_merge($branchData, [
                    'pharmacy_id' => $pharmacy->id,
                    'status' => 'active',
                ]));
            }

            $owner = User::create([
                'pharmacy_id' => $pharmacy->id,
                'branch_id' => $branches[0]->id,
                'name' => $data['pharmacy']['owner_name'],
                'email' => $data['pharmacy']['owner_email'],
                'phone' => $data['pharmacy']['phone'],
                'password' => Hash::make($data['pharmacy']['password']),
            ]);
            $this->assignRole($owner, 'admin');

            foreach ($data['staff'] as $staffData) {
                $temporaryPassword = Str::random(12);
                $staff = User::create([
                    'pharmacy_id' => $pharmacy->id,
                    'branch_id' => $branches[$staffData['branch_index']]->id,
                    'name' => $staffData['name'],
                    'email' => $staffData['email'],
                    'cnic' => $staffData['cnic'],
                    'phone' => $staffData['phone'],
                    'password' => Hash::make($temporaryPassword),
                    'is_invited' => true,
                    'invited_at' => now(),
                ]);
                $this->assignRole($staff, $staffData['role']);
                $invites[] = [$staff, $pharmacy, $temporaryPassword];
            }
        });

        $failedInvites = 0;
        foreach ($invites as [$staff, $pharmacy, $temporaryPassword]) {
            try {
                $staff->notify(new StaffInviteNotification($pharmacy, $temporaryPassword));
            } catch (\Throwable $exception) {
                $failedInvites++;
                Log::warning('Staff invitation could not be sent.', [
                    'user_id' => $staff->id,
                    'email' => $staff->email,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        $request->session()->forget('onboarding');
        $request->session()->forget('onboarding_step');
        auth()->login($owner);

        $message = $failedInvites
            ? 'Pharmacy launched successfully, but ' . $failedInvites . ' staff invitation(s) could not be sent. Check your mail settings and resend them.'
            : 'Pharmacy launched successfully. Staff invitations have been sent.';

        return redirect()->route('dashboard')->with('message', $message);
    }

    private function validatePharmacy(Request $request)
    {
        return $request->validate([
            'business_name' => ['required', 'string', 'max:150'],
            'owner_name' => ['required', 'string', 'max:100'],
            'owner_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'tax_type' => ['required', 'in:NTN,STRN'],
            'tax_id' => ['required', 'string', 'max:30', 'unique:pharmacies,tax_id', function ($attribute, $value, $fail) use ($request) {
                $pattern = $request->input('tax_type') === 'NTN' ? '/^\d{7}$/' : '/^\d{13}$/';
                if (!preg_match($pattern, preg_replace('/[-\s]/', '', $value))) {
                    $fail($request->input('tax_type') . ' must contain ' . ($request->input('tax_type') === 'NTN' ? '7' : '13') . ' digits.');
                }
            }],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    private function validateBranches(Request $request)
    {
        return $request->validate([
            'branches' => ['required', 'array', 'min:1'],
            'branches.*.name' => ['required', 'string', 'max:120'],
            'branches.*.address' => ['required', 'string', 'max:500'],
            'branches.*.city' => ['required', 'string', 'max:100'],
            'branches.*.contact' => ['required', 'string', 'max:30'],
            'branches.*.license_number' => ['required', 'string', 'max:80', 'distinct', 'unique:branches,license_number'],
        ])['branches'];
    }

    private function validateStaff(Request $request)
    {
        $validated = $request->validate([
            'staff' => ['required', 'array'],
            'staff.*.name' => ['required', 'string', 'max:100'],
            'staff.*.email' => ['required', 'email', 'max:255', 'distinct', 'unique:users,email'],
            'staff.*.cnic' => ['required', 'string', 'max:25'],
            'staff.*.phone' => ['required', 'string', 'max:30'],
            'staff.*.role' => ['required', 'string', 'max:100'],
            'staff.*.branch_index' => ['required', 'integer', 'min:0'],
        ])['staff'];

        $branchCount = count($request->session()->get('onboarding.branches', []));
        foreach (range(0, max(0, $branchCount - 1)) as $branchIndex) {
            if (!collect($validated)->contains(function ($staff) use ($branchIndex) {
                return (int) $staff['branch_index'] === $branchIndex && strtolower($staff['role']) === 'admin';
            })) {
                abort(422, 'Each branch requires an Admin account.');
            }
        }

        return $validated;
    }

    private function assignRole(User $user, $roleName)
    {
        $roleQuery = Role::withoutGlobalScopes()
            ->where('name', $roleName)
            ->where('guard_name', 'web');
        $role = $roleQuery->whereNull('pharmacy_id')->first();
        if (!$role) {
            $role = Role::withoutGlobalScopes()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
                'pharmacy_id' => $user->pharmacy_id,
            ]);
        }
        if (strtolower($roleName) === 'admin') {
            $role->syncPermissions(Permission::all());
        }
        $user->assignRole($role);
    }
}
