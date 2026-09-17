<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Pharmacy;
use App\Models\SaleTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index()
    {
        $this->authorize('view-branches');

        $isSuperAdmin = auth()->user()->hasRole('super-admin');
        $pharmacyId = auth()->user()->pharmacy_id;

        $pharmacies = Pharmacy::query()
            ->with(['branches' => function ($query) {
                $query->withCount('users')->orderBy('name');
            }])
            ->when(!$isSuperAdmin, function ($query) use ($pharmacyId) {
                $query->whereKey($pharmacyId);
            })
            ->orderBy('business_name')
            ->get();

        $salesByPharmacy = SaleTransaction::withoutGlobalScopes()
            ->join('users', 'users.id', '=', 'sale_transactions.user_id')
            ->select(
                'users.pharmacy_id',
                DB::raw('COUNT(sale_transactions.id) as transaction_count'),
                DB::raw('COALESCE(SUM(sale_transactions.total), 0) as sales_total')
            )
            ->when(!$isSuperAdmin, function ($query) use ($pharmacyId) {
                $query->where('users.pharmacy_id', $pharmacyId);
            })
            ->groupBy('users.pharmacy_id')
            ->get()
            ->keyBy('pharmacy_id');

        $salesByBranch = SaleTransaction::withoutGlobalScopes()
            ->join('users', 'users.id', '=', 'sale_transactions.user_id')
            ->select(
                'users.branch_id',
                DB::raw('COUNT(sale_transactions.id) as transaction_count'),
                DB::raw('COALESCE(SUM(sale_transactions.total), 0) as sales_total')
            )
            ->when(!$isSuperAdmin, function ($query) use ($pharmacyId) {
                $query->where('users.pharmacy_id', $pharmacyId);
            })
            ->whereNotNull('users.branch_id')
            ->groupBy('users.branch_id')
            ->get()
            ->keyBy('branch_id');

        return view('branches.index', compact(
            'pharmacies',
            'salesByPharmacy',
            'salesByBranch',
            'isSuperAdmin'
        ));
    }

    public function create()
    {
        $this->authorize('view-branches');
        abort_unless(auth()->user()->pharmacy_id, 404);

        return view('branches.create', ['title' => 'Add Branch']);
    }

    public function edit(Branch $branch)
    {
        $this->authorize('update-branch');
        $this->ensureBranchAccess($branch);

        return view('branches.edit', compact('branch'));
    }

    public function store(Request $request)
    {
        $this->authorize('create-branch');
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'contact' => ['required', 'string', 'max:30'],
            'license_number' => ['required', 'string', 'max:80', 'unique:branches,license_number'],
        ]);

        Branch::create(array_merge($validated, [
            'pharmacy_id' => auth()->user()->pharmacy_id,
            'status' => 'active',
        ]));

        return redirect()->route('branches.create')->with('message', 'Branch added successfully.');
    }

    public function update(Request $request, Branch $branch)
    {
        $this->authorize('update-branch');
        $this->ensureBranchAccess($branch);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'contact' => ['required', 'string', 'max:30'],
            'license_number' => [
                'required',
                'string',
                'max:80',
                Rule::unique('branches', 'license_number')->ignore($branch->id),
            ],
        ]);

        $branch->update($validated);

        return redirect()->route('branches.index')->with('message', 'Branch updated successfully.');
    }

    private function ensureBranchAccess(Branch $branch)
    {
        abort_unless(
            auth()->user()->hasRole('super-admin')
            || $branch->pharmacy_id === auth()->user()->pharmacy_id,
            403
        );
    }
}