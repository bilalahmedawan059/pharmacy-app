<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{

    public function index()
    {
        $this->authorize('view-purchase');
        $title = "purchases";
        $purchases = Purchase::with('category')->get();
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
            'quantity' => 'required|min:1',
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
            $purchase = Purchase::create([
                'name' => $request->name,
                'category_id' => $request->category,
                'supplier_id' => $request->supplier,
                'price' => $request->price,
                'quantity' => $request->quantity,
                'expiry_date' => $request->expiry_date,
                'image' => $imageName,
            ]);
            $notifications = array(
                'success' =>  $purchase->name . '  ' ." added successfully!",
                // 'alert-type' => 'success',
            );
        } catch (\Throwable $th) {
            $notifications = array(
                'error' => "Opps!! Something got wrong, Please check and try again",
                // 'alert-type' => 'error',
            );
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
                'image' => $imageName??$request->update_image,
            ]);
            $notifications = array(
                'success' =>  $purchase->name . '  ' ." updated successfully!",
            );
        } catch (\Throwable $th) {
            $notifications = array(
                'error' => "Opps!! Something got wrong, Please check and try again",
            );
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
}
