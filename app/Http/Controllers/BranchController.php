<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function create()
    {
        $this->authorize('view-branches');
        abort_unless(auth()->user()->pharmacy_id, 404);

        return view('branches.create', ['title' => 'Add Branch']);
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
}