<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Purchase;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{

    public function index()
    {
        $this->authorize('view-purchase');
        $title = "purchases";
        $purchases = Purchase::with(['category', 'supplier', 'batches' => function ($query) {
            $query->orderBy('expiry_date')->orderBy('id');
        }])->paginate(10)->withQueryString();
        return view('purchases.purchases', compact(
            'title',
            'purchases'
        ));
    }

    public function create()
    {
        $this->authorize('create-purchase');
        $title = "add Purchase";
        $categories = Category::get();
        $suppliers = Supplier::get();
        $user = request()->user();
        $canSelectBranch = $user->hasAnyRole(['super-admin', 'admin', 'branch-manager']);
        $branches = $this->availableBranches($user, $canSelectBranch);
        $branch = $this->resolveBranch(request(), $branches, $canSelectBranch);
        return view('purchases.add-purchase', compact(
            'title',
            'categories',
            'suppliers',
            'branches',
            'branch',
            'canSelectBranch'
        ));
    }


    public function store(Request $request)
    {
        $this->authorize('create-purchase');
        $this->validate($request, [
            'name' => 'required|max:200',
            'category' => 'required',
            'price' => 'required|min:1',
            'quantity' => 'required|integer|min:1',
            'batch_number' => 'required|string|max:80',
            'expiry_date' => ['required', 'regex:/^(0[1-9]|1[0-2])\\/\\d{4}$/'],
            'branch_id' => 'nullable|integer',
            'supplier' => 'required',
            'image' => 'file|image|mimes:jpg,jpeg,png,gif',
        ]);

        $imageName = null;
        Category::findOrFail($request->category);
        Supplier::findOrFail($request->supplier);
        $user = $request->user();
        $canSelectBranch = $user->hasAnyRole(['super-admin', 'admin', 'branch-manager']);
        $branches = $this->availableBranches($user, $canSelectBranch);
        $branch = $this->resolveBranch($request, $branches, $canSelectBranch);
        if ($branches->isNotEmpty() && !$branch) {
            return back()->withInput()->withErrors(['branch_id' => 'Select a branch before adding stock.']);
        }

        if ($request->hasFile('image')) {
            $imageName = time() . '.' . $request->image->extension();
            $request->image->move(public_path('storage/purchases'), $imageName);
        }

        try {
            $purchase = DB::transaction(function () use ($request, $imageName, $branch) {
                $purchase = Purchase::where('name', trim($request->name))->lockForUpdate()->first();
                $expiryDate = $this->parseExpiryMonthYear($request->expiry_date);

                if (!$purchase) {
                    $purchase = Purchase::create([
                        'name' => trim($request->name),
                        'category_id' => $request->category,
                        'supplier_id' => $request->supplier,
                        'price' => $request->price,
                        'quantity' => 0,
                        'expiry_date' => $expiryDate->toDateString(),
                        'image' => $imageName,
                        'pharmacy_id' => optional($branch)->pharmacy_id ?: optional($request->user())->pharmacy_id,
                    ]);
                }

                $batchQuery = Batch::where('purchase_id', $purchase->id)
                    ->where('batch_number', trim($request->batch_number))
                    ->when($branch, function ($query) use ($branch) {
                        $query->where('branch_id', $branch->id);
                    }, function ($query) {
                        $query->whereNull('branch_id');
                    });
                $batch = $batchQuery->lockForUpdate()->first();

                if (!$batch) {
                    $batch = Batch::create([
                        'purchase_id' => $purchase->id,
                        'branch_id' => optional($branch)->id,
                        'batch_number' => trim($request->batch_number),
                        'expiry_date' => $expiryDate->toDateString(),
                        'quantity_received' => (int) $request->quantity,
                        'quantity_available' => (int) $request->quantity,
                        'pharmacy_id' => $purchase->pharmacy_id ?: optional($branch)->pharmacy_id,
                    ]);
                } else {
                    if ($batch->expiry_date->toDateString() !== $expiryDate->toDateString()) {
                        throw new \DomainException('This batch number already exists with a different expiry date.');
                    }

                    $batch->increment('quantity_received', (int) $request->quantity);
                    $batch->increment('quantity_available', (int) $request->quantity);
                }

                $purchase->refreshTotals();

                return $purchase;
            });

            $notifications = [
                'success' => $purchase->name . ' added successfully!',
            ];
        } catch (\Throwable $th) {
            return redirect()->back()->withInput()->withErrors(['batch_number' => $th->getMessage()]);
        }

        return redirect()->route('purchases')->with($notifications);
    }


    public function show(Request $request, $id)
    {
        $this->authorize('update-purchase');
        $title = "Edit Purchase";
        $purchase = Purchase::with(['batches' => function ($query) {
            $query->withCount('saleAllocations')->orderBy('expiry_date')->orderBy('id');
        }])->findOrFail($id);
        $categories = Category::get();
        $suppliers = Supplier::get();
        return view('purchases.edit-purchase', compact(
            'title',
            'purchase',
            'categories',
            'suppliers'
        ));
    }

    public function batchShow(Purchase $purchase, Batch $batch)
    {
        $this->authorize('view-purchase');
        abort_unless((int) $batch->purchase_id === (int) $purchase->id, 404);
        $batch->load(['purchase', 'branch', 'saleAllocations.sale.transaction']);
        return view('purchases.batch', compact('purchase', 'batch'));
    }

    public function update(Request $request, Purchase $purchase)
    {
        $this->authorize('update-purchase');
        $this->validate($request, [
            'name' => 'required|max:200',
            'category' => 'required',
            'price' => 'required|numeric|min:0',
            'supplier' => 'required',
            'image' => 'file|image|mimes:jpg,jpeg,png,gif',
            'batches' => 'nullable|array',
            'batches.*.batch_number' => 'required|string|max:80',
            'batches.*.expiry_date' => 'required|date',
            'batches.*.quantity_received' => 'required|integer|min:0',
        ]);
        try {
            Category::findOrFail($request->category);
            Supplier::findOrFail($request->supplier);
            DB::transaction(function () use ($request, $purchase) {
                $lockedPurchase = Purchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();
                $lockedPurchase->update([
                    'name' => $request->name,
                    'category_id' => $request->category,
                    'supplier_id' => $request->supplier,
                    'price' => $request->price,
                    'image' => $request->hasFile('image')
                        ? $this->storePurchaseImage($request)
                        : $request->input('update_image', $lockedPurchase->image),
                ]);

                $batches = $lockedPurchase->batches()->orderBy('id')->lockForUpdate()->get();
                foreach ($batches as $batch) {
                    if (!isset($request->input('batches', [])[$batch->id])) {
                        continue;
                    }

                    if ($batch->quantity_available !== $batch->quantity_received || $batch->saleAllocations()->exists()) {
                        throw new \DomainException('Batch ' . $batch->batch_number . ' cannot be edited because it has sale allocations.');
                    }

                    $changes = $request->input('batches')[$batch->id];
                    $batchNumber = trim($changes['batch_number']);
                    $duplicateBatch = $lockedPurchase->batches()
                        ->where('id', '!=', $batch->id)
                        ->where('batch_number', $batchNumber)
                        ->when($batch->branch_id, function ($query) use ($batch) {
                            $query->where('branch_id', $batch->branch_id);
                        }, function ($query) {
                            $query->whereNull('branch_id');
                        })
                        ->exists();
                    if ($duplicateBatch) {
                        throw new \DomainException('This batch number already exists for this branch.');
                    }

                    $batch->batch_number = $batchNumber;
                    $batch->expiry_date = $changes['expiry_date'];
                    $batch->quantity_received = (int) $changes['quantity_received'];
                    $batch->quantity_available = (int) $changes['quantity_received'];
                    $batch->save();
                }

                $lockedPurchase->refreshTotals();
            });
            $notifications = ['success' => $purchase->name . ' updated successfully!'];
        } catch (\Throwable $th) {
            return redirect()->back()->withInput()->withErrors(['purchase' => $th->getMessage()]);
        }
        return redirect()->route('purchases')->with($notifications);
    }


    public function destroy(Request $request)
    {
        $this->authorize('destroy-purchase');
        $purchase = Purchase::findOrFail($request->id);
        try {
            DB::transaction(function () use ($purchase) {
                $lockedPurchase = Purchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();
                $batches = $lockedPurchase->batches()->orderBy('id')->lockForUpdate()->get();
                if ($batches->contains(function (Batch $batch) {
                    return $batch->saleAllocations()->exists();
                })) {
                    throw new \DomainException('This purchase cannot be deleted because one or more batches have sales history.');
                }

                foreach ($batches as $batch) {
                    $batch->delete();
                }
                $lockedPurchase->delete();
            });
        } catch (\Throwable $exception) {
            return back()->withErrors(['purchase' => $exception->getMessage()]);
        }
        $notification = array(
            'message' => "Purchase has been deleted",
            'alert-type' => 'success'
        );
        return back()->with($notification);
    }

    private function parseExpiryMonthYear(string $value): Carbon
    {
        $parts = preg_split('/\s*\/\s*/', trim($value));

        if (count($parts) !== 2 || ! is_numeric($parts[0]) || ! is_numeric($parts[1])) {
            throw new \InvalidArgumentException('Expiry date must be in MM/YYYY format.');
        }

        return Carbon::createFromDate((int) $parts[1], (int) $parts[0], 1)->endOfMonth();
    }

    private function availableBranches($user, bool $canSelectBranch)
    {
        $query = Branch::query();
        if (!$user->hasRole('super-admin')) {
            $query->where('pharmacy_id', $user->pharmacy_id ?: 0);
        }
        if (!$canSelectBranch) {
            $query->whereKey($user->branch_id ?: 0);
        }
        return $query->orderBy('name')->get();
    }

    private function resolveBranch(Request $request, $branches, bool $canSelectBranch): ?Branch
    {
        $user = $request->user();
        $branchId = $canSelectBranch
            ? ($request->input('branch_id') ?: $user->branch_id ?: optional($branches->first())->id)
            : $user->branch_id;
        return $branchId ? $branches->firstWhere('id', (int) $branchId) ?: abort(404) : null;
    }

    private function storePurchaseImage(Request $request): string
    {
        $imageName = time() . '.' . $request->image->extension();
        $request->image->move(public_path('storage/purchases'), $imageName);
        return $imageName;
    }
}
