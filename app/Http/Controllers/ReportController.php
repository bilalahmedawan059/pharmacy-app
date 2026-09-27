<?php

namespace App\Http\Controllers;

use App\Models\Sales;
use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(){
        $this->authorize('view-reports');
        if (request()->filled('resource')) {
            return $this->getData(request());
        }
        $title = "generate Reports";
        return view('reports.reports',compact(
            'title',
        ));
    }

    public function getData(Request $request){
        $this->authorize('view-reports');
        $this->validate($request,[
            'from_date'=>'required',
            'to_date'=>'required',
            'resource'=>'required',
        ]);
        $from_date = $request->from_date;
        $to_date = $request->to_date;
        $filters = $request->only(['from_date', 'to_date', 'resource']);
        if ($request->resource == 'sales'){
            $salesQuery = Sales::with('product.purchase')->whereBetween(DB::raw('DATE(created_at)'), [$from_date, $to_date]);
            $total_sales = (clone $salesQuery)->count();
            $total_cash = (clone $salesQuery)->sum('total_price');
            $sales = $salesQuery->paginate(10)->appends($filters);
            $title = "Sales Reports";
            return view('reports.reports',compact('sales','title','total_sales','total_cash'));
        }
        if($request->resource == "products"){
            $title = "Products Reports";
            $products = Product::with('purchase.category')->whereBetween(DB::raw('DATE(created_at)'), [$from_date, $to_date])->paginate(10)->appends($filters);
            return view('reports.reports',compact('title','products'));
        }
        if($request->resource == 'purchases'){
            $title = "Purchases Reports";
            $purchases = Purchase::with(['supplier', 'category'])->whereBetween(DB::raw('DATE(created_at)'), [$from_date, $to_date])->paginate(10)->appends($filters);
            return view('reports.reports',compact('title','purchases'));
        }
    }
}
