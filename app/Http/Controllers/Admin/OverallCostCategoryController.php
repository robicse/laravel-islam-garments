<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Model\OverallCostCategory;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OverallCostCategoryController extends Controller
{
//    function __construct()
//    {
//        $this->middleware('permission:overall-cost-category-list|overall-cost-category-create|overall-cost-category-edit|overall-cost-category-delete', ['only' => ['index','store']]);
//        $this->middleware('permission:overall-cost-category-create', ['only' => ['create','store']]);
//        $this->middleware('permission:overall-cost-category-edit', ['only' => ['edit','update']]);
//        $this->middleware('permission:overall-cost-category-delete', ['only' => ['destroy']]);
//    }

    public function index()
    {
        $overallCostCategories = OverallCostCategory::latest()->get();
        return view('backend.admin.overallCostCategory.index', compact('overallCostCategories'));
    }

    public function create()
    {
        return view('backend.admin.overallCostCategory.create');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required'
        ]);

        $overallCostCategory = new OverallCostCategory();
        $overallCostCategory->name = $request->name;
        $overallCostCategory->slug = Str::slug($request->name);
        $overallCostCategory->save();

        Toastr::success('Overall Cost Category Created Successfully');
        return redirect()->route('admin.overall-cost-categories.index');
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $officeCostingCategory = OverallCostCategory::find($id);
        return view('backend.admin.overallCostCategory.edit', compact('officeCostingCategory'));
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required'
        ]);

        $overallCostCategory = OverallCostCategory::find($id);
        $overallCostCategory->name = $request->name;
        $overallCostCategory->slug = Str::slug($request->name);
        $overallCostCategory->save();

        Toastr::success('Overall Cost Category Updated Successfully');
        return redirect()->route('admin.overall-cost-categories.index');
    }

    public function destroy($id)
    {
        //OfficeCostingCategory::destroy($id);
        Toastr::warning('Overall Cost Category not deleted possible, Please contact with administrator!');
        return redirect()->route('admin.overall-cost-categories.index');
    }
}
