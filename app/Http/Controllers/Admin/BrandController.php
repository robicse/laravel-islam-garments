<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Model\AccessLog;
use App\Model\Brand;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class BrandController extends Controller
{
//    function __construct()
//    {
//        $this->middleware('permission:brand-list|brand-create|brand-edit|brand-delete', ['only' => ['index','store']]);
//        $this->middleware('permission:brand-create', ['only' => ['create','store']]);
//        $this->middleware('permission:brand-edit', ['only' => ['edit','update']]);
//        $this->middleware('permission:brand-delete', ['only' => ['destroy']]);
//    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $brands = Brand::all();
        return view('backend.admin.brands.index', compact('brands'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('backend.admin.brands.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name'=> 'required|unique:brands,name',
        ]);

        $brand = new Brand;
        $brand->name = $request->name;
        $brand->slug = Str::slug($request->name);
        //$brand->meta_title = $request->meta_title;
        //$brand->meta_description = $request->meta_description;
        $image = $request->file('logo');
        if (isset($image)) {
            //make unique name for image
            $currentDate = Carbon::now()->toDateString();
            $imagename = $currentDate . '-' . uniqid() . '.' . $image->getClientOriginalExtension();
//            resize image for hospital and upload
            $proImage = Image::make($image)->resize(120, 80)->save($image->getClientOriginalExtension());
            Storage::disk('public')->put('uploads/brands/' . $imagename, $proImage);

        }else {
            $imagename = "default.png";
        }
        $brand->logo = $imagename;
        $brand->save();
        $insert_id = $brand->id;
        if($insert_id){
            $accessLog = new AccessLog();
            $accessLog->user_id=Auth::user()->id;
            $accessLog->action_module='Brand';
            $accessLog->action_done='Create';
            $accessLog->action_remarks='Brand ID: '.$insert_id;
            $accessLog->action_date=date('Y-m-d');
            $accessLog->save();
        }
        Toastr::success('Brand Created Successfully');
        return redirect()->route('admin.brands.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $brand = Brand::find($id);
        return view('backend.admin.brands.edit',compact('brand'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name'=> 'required|unique:brands,name,'.$id,
        ]);

        $brand = Brand::find($id);
        $brand->name = $request->name;
        $brand->slug = Str::slug($request->name);
        //$brand->meta_title = $request->meta_title;
        //$brand->meta_description = $request->meta_description;
        $image = $request->file('logo');
        if (isset($image)) {
            //make unique name for image
            $currentDate = Carbon::now()->toDateString();
            $imagename = $currentDate . '-' . uniqid() . '.' . $image->getClientOriginalExtension();

            if(Storage::disk('public')->exists('uploads/brands/'.$brand->logo))
            {
                Storage::disk('public')->delete('uploads/brands/'.$brand->logo);
            }
//            resize image for hospital and upload
            $proImage = Image::make($image)->resize(120, 80)->save($image->getClientOriginalExtension());
            Storage::disk('public')->put('uploads/brands/' . $imagename, $proImage);

        }else {
            $imagename = $brand->logo;
        }
        $brand->logo = $imagename;
        $updated_row = $brand->save();
        if($updated_row){
            $accessLog = new AccessLog();
            $accessLog->user_id=Auth::user()->id;
            $accessLog->action_module='Brand';
            $accessLog->action_done='Update';
            $accessLog->action_remarks='Brand ID: '.$id;
            $accessLog->action_date=date('Y-m-d');
            $accessLog->save();
        }
        Toastr::success('Brand updated successfully','Success');
        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
//    public function destroy($id)
//    {
//        $brand = Brand::find($id);
//        if(Storage::disk('public')->exists('uploads/brands/'.$brand->logo))
//        {
//            Storage::disk('public')->delete('uploads/brands/'.$brand->logo);
//        }
//        $deleted_row = $brand->delete();
//        if($deleted_row){
//            $accessLog = new AccessLog();
//            $accessLog->user_id=Auth::user()->id;
//            $accessLog->action_module='Brand';
//            $accessLog->action_done='Delete';
//            $accessLog->action_remarks='Brand ID: '.$id;
//            $accessLog->action_date=date('Y-m-d');
//            $accessLog->save();
//        }
//        Toastr::success('Brand deleted successfully','Success');
//        return back();
//    }
}
