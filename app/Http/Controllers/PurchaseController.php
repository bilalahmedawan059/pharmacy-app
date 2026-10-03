<?php

namespace App\Http\Controllers;

use App\Models\Batch;
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
        $purchases = Purchase::with('category')->paginate(10)->withQueryString();
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
        return view('purchases.add-purchase', compact(
            'title',
            'categories',
            'suppliers'
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
            'supplier' => 'required',
            'image' => 'file|image|mimes:jpg,jpeg,png,gif',
        ]);

        $imageName = null;
        Category::findOrFail($request->category);
        Supplier::findOrFail($request->supplier);

        if ($request->hasFile('image')) {
            $imageName = time() . '.' . $request->image->extension();
            $request->image->move(public_path('storage/purchases'), $imageName);
        }

        try {
            $purchase = DB::transaction(function () use ($request, $imageName) {
                $purchase = Purchase::where('name', trim($request->name))->first();
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
                    ]);
                }

                $batch = Batch::where('purchase_id', $purchase->id)
                    ->where('batch_number', trim($request->batch_number))
                    ->whereNull('branch_id')
                    ->first();

                if (!$batch) {
                    $batch = Batch::create([
                        'purchase_id' => $purchase->id,
                        'branch_id' => null,
                        'batch_number' => trim($request->batch_number),
                        'expiry_date' => $expiryDate->toDateString(),
                        'quantity_received' => (int) $request->quantity,
                        'quantity_available' => (int) $request->quantity,
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
        $purchase = Purchase::findOrFail($id);
        $categories = Category::get();
        $suppliers = Supplier::get();
        return view('purchases.edit-purchase', compact(
            'title',
            'purchase',
            'categories',
            'suppliers'
        ));
    }

    public function update(Request $request, Purchase $purchase)
    {
        $this->authorize('update-purchase');
        $this->validate($request, [
            'name' => 'required|max:200',
            'category' => 'required',
            'price' => 'required',
            'quantity' => 'required',
            'expiry_date' => 'required',
            'supplier' => 'required',
            'image' => 'file|image|mimes:jpg,jpeg,png,gif',
        ]);
        $imageName = null;
        Category::findOrFail($request->category);
        Supplier::findOrFail($request->supplier);
        if ($request->hasFile('image')) {
            $imageName = time() . '.' . $request->image->extension();
            $request->image->move(public_path('storage/purchases'), $imageName);
        }

        try {
            $purchase->update([
                'name' => $request->name,
                'category_id' => $request->category,
                'supplier_id' => $request->supplier,
                'price' => $request->price,
                'quantity' => $request->quantity,
                'expiry_date' => $request->expiry_date,
                'image' => $imageName ?? $request->update_image,
            ]);
            $notifications = array(
                'success' =>  $purchase->name . '  ' ." updated successfully!",
            );
        } catch (\Throwable $th) {
            return redirect()->back()->withInput()->withErrors(['name' => $th->getMessage()]);
        }
        return redirect()->route('purchases')->with($notifications);
    }


    public function destroy(Request $request)
    {
        $this->authorize('destroy-purchase');
        $purchase = Purchase::findOrFail($request->id);
        $purchase->delete();
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
}
