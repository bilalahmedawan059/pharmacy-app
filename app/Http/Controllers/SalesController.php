<?php

namespace App\Http\Controllers;

use App\Events\MedicineOutStock;
use App\Models\Sales;
use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Http\Request;
use App\Events\PurchaseOutStock;


class SalesController extends Controller
{

    public function getProductByBarcode(Request $request)
    {
        $request->validate([
            'barcode' => 'required|string',
            'quantity' => 'required|integer',
        ]);
//
        $product = Product::where('product_code', $request->barcode)->first();
//
//        $sold_product = Product::find($product);
//
        $purchased_item = Purchase::find($product->purchase->id);
//
        if (!empty($request->edit_id)) {
            $sales_quantity = Sales::find($request->edit_id)->quantity;
            $purchased_item->increment('quantity',  $sales_quantity);
        }

        $new_quantity = ($purchased_item->quantity) - ($request->quantity);
        $notification = '';

        if (!($new_quantity < 0)) {

            Sales::updateOrCreate(
                ['id' => $request->edit_id],
                [
                    'product_id' => $product->id,
                    'quantity' => $request->quantity,
                    'total_price' => ($request->quantity) * ($product->price),
                ]
            );

            $purchased_item->update([
                'quantity' => $new_quantity,
            ]);

            $notification = "Medicine sold successfully!!";

            if ($new_quantity <= 1 || $new_quantity == 0) {

                event(new MedicineOutStock($purchased_item));
//                // end of notification
                $notification = "Medicine is running out of stock!!!";
            }
        }elseif ($request->quantity > $purchased_item->quantity) {
            $notification = array(
                'error' => "Medicine request quantity can not be grater than available quantity!!!  " . ' Available Quantity is ' . ($purchased_item->quantity),
            );

            if (!empty($request->edit_id)) {
                $sales_quantity = Sales::find($request->edit_id)->quantity;
                $purchased_item->decrement('quantity',  $sales_quantity);
            }
            return back()->with($notification);
        }

//        return back()->with($notification);
        return response()->json([
            'success' => true,
            'message' => 'Product data saved successfully.',
            'notification' => $notification
        ]);

//        if ($product && $product->purchase && $product->purchase->quantity > 0) {
//            return response()->json([
//                'success' => true,
//                'product' => [
//                    'id' => $product->id,
//                    'name' => $product->purchase->name,
//                    'quantity' => 1
//                ]
//            ]);
//        }

//        return response()->json(['success' => false, 'message' => 'Product not found or out of stock']);
//        return response()->json(['success' => false, 'message' => $request->barcode]);
    }



    public function index()
    {
        $title = "sales";
        $products = Product::get();
        $sales = Sales::with('product')->latest()->get();

        return view('sales.sales', compact(
            'title',
            'products',
            'sales'
        ));
    }


    public function index_Auto()
    {
        $title = "sales";
        $products = Product::get();
        $sales = Sales::with('product')->latest()->get();

        return view('auto_sales.sales', compact(
            'title',
            'products',
            'sales'
        ));
    }


    public function store(Request $request)
    {
        $this->validate($request, [
            'product' => 'required',
            'quantity' => 'required|integer|min:1'
        ]);
        $sold_product = Product::find($request->product);

        $purchased_item = Purchase::find($sold_product->purchase->id);

            if (!empty($request->edit_id)) {
                $sales_quantity = Sales::find($request->edit_id)->quantity;
                $purchased_item->increment('quantity',  $sales_quantity);
            }

            $new_quantity = ($purchased_item->quantity) - ($request->quantity);
            $notification = '';

            if (!($new_quantity < 0)) {

                Sales::updateOrCreate(
                    [
                        'product_id' => $request->product,
                        'quantity' => $request->quantity,
                        'total_price' => ($request->quantity) * ($sold_product->price),
                    ]
                );

                $purchased_item->update([
                    'quantity' => $new_quantity,
                ]);

                $notification = array(
                    'success' => "Medicine sold successfully!!",
                );

                if ($new_quantity <= 1 || $new_quantity == 0) {

                    event(new MedicineOutStock($purchased_item));
                    // end of notification
                    $notification = array(
                        'error' => "Medicine is running out of stock!!!",
                    );
                }
        }elseif ($request->quantity > $purchased_item->quantity) {
                $notification = array(
                    'error' => "Medicine request quantity can not be grater than available quantity!!!  " . ' Available Quantity is ' . ($purchased_item->quantity),
                );

                if (!empty($request->edit_id)) {
                    $sales_quantity = Sales::find($request->edit_id)->quantity;
                    $purchased_item->decrement('quantity',  $sales_quantity);
                }
                return back()->with($notification);
            }

        return back()->with($notification);
    }


    public function destroy(Request $request)
    {
        $sale = Sales::find($request->id);
        $sale->delete();
        $notification = array(
            'message' => "Sales has been deleted",
            'alert-type' => 'success'
        );
        return back()->with($notification);
    }
}
