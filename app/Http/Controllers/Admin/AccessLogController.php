<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Model\AccessLog;
use App\Model\Customer;
use App\User;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class AccessLogController extends Controller
{
//    function __construct()
//    {
//        $this->middleware('permission:access-log-list|access-log-create|access-log-edit|access-log-delete', ['only' => ['index','store']]);
//        $this->middleware('permission:access-log-create', ['only' => ['create','store']]);
//        $this->middleware('permission:access-log-edit', ['only' => ['edit','update']]);
//        $this->middleware('permission:access-log-delete', ['only' => ['destroy']]);
//    }

    public function index()
    {
        $accessLogs = AccessLog::latest()->get();
        return view('backend.admin.access_logs.index', compact('accessLogs'));
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        $accessLog = AccessLog::find($id);
        $accessLog->delete();

        Toastr::success('Access Log deleted successfully','Success');
        return back();
    }

}
