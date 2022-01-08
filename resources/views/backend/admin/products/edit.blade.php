@extends('backend.layouts.master')
@section("title","Edit Vehicle")
@push('css')
    <link rel="stylesheet" href="{{asset('backend/plugins/bootstrap-datepicker/bootstrap-datepicker.css')}}">
@endpush
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Edit Vehicle</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">Home</a></li>
                        <li class="breadcrumb-item active">Edit Vehicle</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-8 offset-2">
                <!-- general form elements -->
                <div class="card card-info card-outline">
                    <div class="card-header">
                        <h3 class="card-title float-left">Edit Vehicle</h3>
                        <div class="float-right">
                            <a href="{{route('admin.vehicles.index')}}">
                                <button class="btn btn-success">
                                    <i class="fa fa-backward"> </i>
                                    Back
                                </button>
                            </a>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <!-- form start -->
                    <form role="form" action="{{route('admin.vehicles.update',$vehicle->id)}}" method="post" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="form-group">
                                <label for="own_vehicle_status">Own Vehicle Status <span>*</span></label>
                                <select name="own_vehicle_status" id="own_vehicle_status" class="form-control select2" required>
                                    <option value="">Select</option>
                                    <option value="Own" {{$vehicle->own_vehicle_status == 'Own' ? 'selected' : ''}}>Own</option>
                                    <option value="Rent" {{$vehicle->own_vehicle_status == 'Rent' ? 'selected' : ''}}>Rent</option>
                                </select>
                            </div>
                            <div class="form-group" id="vendor_div" style="@if($vehicle->own_vehicle_status == 'Own') display:none; @endif">
                                <label for="vendor_id">Vendor <span>*</span></label>
                                <select name="vendor_id" id="vendor_id" class="form-control select2">
                                    <option value="">Select</option>
                                    @foreach($vendors as $vendor)
                                        <option value="{{$vendor->id}}" {{$vendor->id == $vehicle->vendor_id ? 'selected' : ''}}>{{$vendor->name}} ({{$vendor->vendor_code}})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group" id="owner_name_div">
                                <label for="owner_name">Vehicle Owner Name <span>*</span></label>
                                <input type="text" class="form-control" name="owner_name" id="owner_name" value="{{$vehicle->owner_name}}">
                            </div>
                            <div class="form-group">
                                <label for="vehicle_name">Vehicle Name <span>*</span></label>
                                <input type="text" class="form-control" name="vehicle_name" id="vehicle_name" value="{{$vehicle->vehicle_name}}" required>
                            </div>
                            <div class="form-group">
                                <label for="brand_id">Brand <span>*</span></label>
                                <select name="brand_id" id="brand_id" class="form-control select2" required>
                                    @foreach($brands as $brand)
                                        <option value="{{$brand->id}}" {{$vehicle->brand_id == $brand->id ? 'selected' : ''}}>{{$brand->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="category_id">Category <span>*</span></label>
                                <select name="category_id" id="category_id" class="form-control select2" required>
                                    @foreach($categories as $category)
                                        <option value="{{$category->id}}" {{$vehicle->category_id == $brand->id ? 'selected' : ''}}>{{$category->name}}</option>
                                    @endforeach
                                </select>
                            </div>
{{--                            <div class="form-group">--}}
{{--                                <label for="rent_type">Rent Type <span>*</span></label>--}}
{{--                                <select name="rent_type" id="rent_type" class="form-control select2" required>--}}
{{--                                    <option value="">Select</option>--}}
{{--                                    <option value="Daily" {{$vehicle->rent_type == 'Daily' ? 'selected' : ''}}>Daily</option>--}}
{{--                                    <option value="Monthly" {{$vehicle->rent_type == 'Monthly' ? 'selected' : ''}}>Monthly</option>--}}
{{--                                </select>--}}
{{--                            </div>--}}
{{--                            <div class="form-group">--}}
{{--                                <label for="price">Rent Price</label>--}}
{{--                                <input type="number" class="form-control" name="price" id="price" value="{{$vehicle->price}}">--}}
{{--                            </div>--}}
                            <div class="form-group">
                                <label for="model">Model</label>
                                <input type="text" class="form-control" name="model" id="model" value="{{$vehicle->model}}">
                            </div>
                            <div class="form-group">
                                <label for="registration_no">Registration NO</label>
                                <input type="text" class="form-control" name="registration_no" id="registration_no" value="{{$vehicle->registration_no}}">
                            </div>
                            <div class="form-group">
                                <label for="registration_date">Registration Date</label>
                                <input type="text" class="datepicker form-control" name="registration_date" id="registration_date" value="{{$vehicle->registration_date}}">
                            </div>
                            <div class="form-group">
                                <label for="chassis_no">Chassis NO</label>
                                <input type="text" class="form-control" name="chassis_no" id="chassis_no" value="{{$vehicle->chassis_no}}">
                            </div>

                            <div class="form-group">
                                <label for="engine_no">Engine NO</label>
                                <input type="text" class="form-control" name="engine_no" id="engine_no" value="{{$vehicle->engine_no}}">
                            </div>
                            <div class="form-group">
                                <label for="vehicle_class">Vehicle Class</label>
                                <input type="text" class="form-control" name="vehicle_class" id="vehicle_class" value="{{$vehicle->vehicle_class}}">
                            </div>
                            <div class="form-group">
                                <label for="fuel_type">Fuel Type</label>
                                <input type="text" class="form-control" name="fuel_type" id="fuel_type" value="{{$vehicle->fuel_type}}">
                            </div>
                            <div class="form-group">
                                <label for="chassis_no">Chassis NO</label>
                                <input type="text" class="form-control" name="chassis_no" id="chassis_no" value="{{$vehicle->chassis_no}}">
                            </div>
                            <div class="form-group">
                                <label for="fitness">Fitness</label>
                                <input type="text" class="form-control" name="fitness" id="fitness" value="{{$vehicle->fitness}}">
                            </div>
                            <div class="form-group">
                                <label for="rc_status">RC Status</label>
                                <input type="text" class="form-control" name="rc_status" id="rc_status" value="{{$vehicle->rc_status}}">
                            </div>
                            <img src="{{asset('uploads/vehicles/'.$vehicle->image)}}" width="80" height="50" alt="">
                            <div class="form-group">
                                <label for="email">Vehicle Image <small>(size: 120 * 80 pixel)</small></label>
                                <input type="file" class="form-control" name="image" id="logo" >
                            </div>
                            <div class="form-group">
                                <label for="status">Status <span>*</span></label>
                                <select name="status" id="status" class="form-control select2" required>
                                    <option value="1" {{$vehicle->status == 1 ? 'selected' : ''}}>Active</option>
                                    <option value="0" {{$vehicle->status == 0 ? 'selected' : ''}}>Inactive</option>
                                </select>
                            </div>
                        </div>
                        <!-- /.card-body -->
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

@stop
@push('js')
    <script src="{{asset('backend/plugins/bootstrap-datepicker/bootstrap-datepicker.js')}}"></script>
    <script>
        $('.demo-select2').select2();
        // $("#demo-dp-range .input-daterange").datepicker({
        //     startDate: "-0d",
        //     todayBtn: "linked",
        //     autoclose: true,
        //     todayHighlight: true,
        // });
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            startDate: '-3d',
            //startDate: '-0d',
            todayBtn: "linked",
            autoclose: true,
            todayHighlight: true
        });

        //$('#vendor_div').hide();
        $('#owner_name_div').hide();
        $('#own_vehicle_status').change(function (){
            var own_vehicle_status = $('#own_vehicle_status').val();
            if(own_vehicle_status == 'Rent'){
                $('#vendor_div').show();
            }else{
                $('#vendor_div').hide();
            }
        })
    </script>
@endpush
