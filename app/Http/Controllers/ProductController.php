<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;


class ProductController extends Controller
{

    public function productCodeExists($number) {
        return Product::where('product_code', $number)->exists();
    }


    public function index()
    {
        $this->authorize('view-products');
        $title = "products";
        $products = Product::with('purchase')->get();

        return view('products.products',compact(
            'title','products',
        ));
    }

    public function create(){
        $this->authorize('create-product');
        $title= "Add Product";
        $products = Purchase::get();

        return view('products.add-product',compact(
            'title','products',
        ));
    }

    public function expired(){
        $this->authorize('view-expired-products');
        $title = "expired Products";
        $products = Purchase::whereDate('expiry_date', '<', Carbon::now())->get();

        return view('products.expired',compact(
            'title','products'
        ));
    }


    public function outstock(){
        $this->authorize('view-outstock-products');
        $title = "outstocked Products";
        $products = Purchase::where('quantity', '<=', 0)->get();
        $product = Purchase::where('quantity', '<=', 0)->first();

        return view('products.outstock',compact(
            'title','products',
        ));
    }

    public function store(Request $request)
    {
        $this->authorize('create-product');
        $this->validate($request,[
            'product'=>'required|max:200',
            'price'=>'required|min:1',
            'discount'=>'nullable',
            'description'=>'nullable|max:200',
        ]);

        $price = $request->price;
        if($request->discount >0){
           $price = $request->discount * $request->price;
        }
       try {

           $number =  mt_rand(1000000000 , 9999999999);
           if($this->productCodeExists($number)){
               $number =  mt_rand(1000000000 , 9999999999);
           }


           Product::create([
            'purchase_id'=>$request->product,
            'price'=>$price,
            'product_code' => $number,
            'discount'=>$request->discount,
            'description'=>$request->description,
        ]);

        $notification=array(
            'success'=> "Medicine added successfully!",
        );
       } catch (\Throwable $th) {
        $notification = array(
            'error' => "Opps!! Something got wrong, Please check and try again",
        );
       }
        return redirect()->route('products')->with($notification);
    }

    public function show(Request $request, $id)
    {
        $this->authorize('update-product');
        $title = "Edit Product";
        $product = Product::findOrFail($id);
        $purchased_products = Purchase::get();
        return view('products.edit-product',compact(
            'title','product','purchased_products'
        ));
    }

    public function update(Request $request,Product $product)
    {
        $this->authorize('update-product');
        $this->validate($request,[
            'product'=>'required|max:200',
            'price'=>'required',
            'discount'=>'nullable',
            'description'=>'nullable|max:200',
        ]);

        $price = $request->price;
        if($request->discount >0){
           $price = $request->discount * $request->price;
        }
       try {
        $product->update([
            'purchase_id'=>$request->product,
            'price'=>$price,
            'discount'=>$request->discount,
            'description'=>$request->description,
        ]);
        $notification=array(
            'success'=>"Medicine updated successfully!",
        );
       } catch (\Throwable $th) {
        $notifications = array(
            'error' => "Opps!! Something got wrong, Please check and try again",
        );
       }
        return redirect()->route('products')->with($notification);
    }

    public function destroy(Request $request)
    {
        $this->authorize('destroy-product');
        $product = Product::findOrFail($request->id);
        $product->delete();
        $notification = array(
            'success'=>"Product has been deleted",
        );
        return back()->with($notification);
    }
}
