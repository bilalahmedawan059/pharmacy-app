<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{

    public function index()
    {
        $this->authorize('view-category');
        $title = "categories";
        $categories = Category::get();
        return view('categories.categories',compact(
            'title','categories',
        ));
    }


    public function store(Request $request)
    {
        $this->authorize('create-category');
        $this->validate($request,[
            'name'=>'required|max:100',
        ]);
        Category::create(['name' => $request->name]);
        $notification=array(
            'message'=>"Category has been added",
            'alert-type'=>'success',
        );
        return back()->with($notification);
    }


    public function update(Request $request)
    {
        $this->authorize('update-category');
        $this->validate($request,['name'=>'required|max:100']);
        $category = Category::findOrFail($request->id);
        $category->update([
            'name'=>$request->name,
        ]);
        $notification=array(
            'message'=>"Category has been updated",
            'alert-type'=>'success',
        );
        return back()->with($notification);
    }


    public function destroy(Request $request)
    {
        $this->authorize('destroy-category');
        $category = Category::findOrFail($request->id);
        $category->delete();
        $notification=array(
            'message'=>"Category has been deleted",
            'alert-type'=>'success',
        );
        return back()->with($notification);
    }
}
