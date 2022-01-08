@extends('backend.layouts.master')
@section("title","Add Vehicle")
@push('css')
    <link rel="stylesheet" href="{{asset('backend/plugins/bootstrap-datepicker/bootstrap-datepicker.css')}}">
@endpush
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Add Vehicle</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">Home</a></li>
                        <li class="breadcrumb-item active">Add Vehicle</li>
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
                    <h3 class="card-title float-left">Add Vehicle</h3>
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
                <form role="form" action="{{route('admin.vehicles.store')}}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="own_vehicle_status">Own Vehicle Status <span>*</span></label>
                            <select name="own_vehicle_status" id="own_vehicle_status" class="form-control select2" required>
                                <option value="">Select</option>
                                <option value="Own">Own</option>
                                <option value="Rent">Rent</option>
                            </select>
                        </div>
                        <div class="form-group" id="vendor_div">
                            <label for="vendor_id">Vendor <span>*</span></label>
                            <select name="vendor_id" id="vendor_id" class="form-control select2">
                                <option value="">Select</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{$vendor->id}}">{{$vendor->name}} ({{$vendor->vendor_code}})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" id="owner_name_div">
                            <label for="owner_name">Vehicle Owner Name <span>*</span></label>
                            <input type="text" class="form-control" name="owner_name" id="owner_name" placeholder="">
                        </div>
                        <div class="form-group">
                            <label for="vehicle_name">Vehicle Name <span>*</span></label>
                            <input type="text" class="form-control" name="vehicle_name" id="vehicle_name" placeholder="" required>
                        </div>
                        <div class="form-group">
                            <label for="brand_id">Brand <span>*</span></label>
                            <select name="brand_id" id="brand_id" class="form-control select2" required>
                                <option value="">Select</option>
                                @foreach($brands as $brand)
                                    <option value="{{$brand->id}}">{{$brand->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="category_id">Category <span>*</span></label>
                            <select name="category_id" id="category_id" class="form-control select2" required>
                                <option value="">Select</option>
                                @foreach($categories as $category)
                                    <option value="{{$category->id}}">{{$category->name}}</option>
                                @endforeach
                            </select>
                        </div>
{{--                        <div class="form-group">--}}
{{--                            <label for="rent_type">Rent Type <span>*</span></label>--}}
{{--                            <select name="rent_type" id="rent_type" class="form-control select2" required>--}}
{{--                                <option value="">Select</option>--}}
{{--                                <option value="Daily">Daily</option>--}}
{{--                                <option value="Monthly">Monthly</option>--}}
{{--                            </select>--}}
{{--                        </div>--}}
{{--                        <div class="form-group">--}}
{{--                            <label for="price">Rent Price</label>--}}
{{--                            <input type="number" class="form-control" name="price" id="price" placeholder="">--}}
{{--                        </div>--}}
                        <div class="form-group">
                            <label for="model">Model <span>*</span></label>
                            <input type="text" class="form-control" name="model" id="model" placeholder="" required>
                        </div>
                        <div class="form-group">
                            <label for="registration_no">Registration NO <span>*</span></label>
                            <input type="text" class="form-control" name="registration_no" id="registration_no" placeholder="" required>
                        </div>
                        <div class="form-group">
                            <label for="registration_date">Registration Date <span>*</span></label>
                            <input type="text" class="datepicker form-control" name="registration_date" id="registration_date" placeholder="" required>
                        </div>
                        <div class="form-group">
                            <label for="chassis_no">Chassis NO <span>*</span></label>
                            <input type="text" class="form-control" name="chassis_no" id="chassis_no" placeholder="" required>
                        </div>

                        <div class="form-group">
                            <label for="engine_no">Engine NO <span>*</span></label>
                            <input type="text" class="form-control" name="engine_no" id="engine_no" placeholder="" required>
                        </div>
                        <div class="form-group">
                            <label for="vehicle_class">Vehicle Class <span>*</span></label>
                            <input type="text" class="form-control" name="vehicle_class" id="vehicle_class" placeholder="" required>
                        </div>
                        <div class="form-group">
                            <label for="fuel_type">Fuel Type <span>*</span></label>
                            <input type="text" class="form-control" name="fuel_type" id="fuel_type" placeholder="" required>
                        </div>
{{--                        <div class="form-group">--}}
{{--                            <label for="chassis_no">Chassis NO <span>*</span></label>--}}
{{--                            <input type="text" class="form-control" name="chassis_no" id="chassis_no" placeholder="" required>--}}
{{--                        </div>--}}
                        <div class="form-group">
                            <label for="fitness">Fitness <span>*</span></label>
                            <input type="text" class="form-control" name="fitness" id="fitness" placeholder="" required>
                        </div>
                        <div class="form-group">
                            <label for="rc_status">RC Status <span>*</span></label>
                            <input type="text" class="form-control" name="rc_status" id="rc_status" placeholder="" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Vehicle Image <small>(size: 120 * 80 pixel)</small> <span>*</span></label>
                            <input type="file" class="form-control" name="image" id="logo" required>
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

        $('#vendor_div').hide();
        $('#owner_name_div').hide();
        $('#own_vehicle_status').change(function (){
            var own_vehicle_status = $('#own_vehicle_status').val();
            if(own_vehicle_status == 'Rent'){
                $('#vendor_div').show();
                //$('#owner_name_div').hide();
            }else{
                //$('#owner_name').val('Mr.');
                //$('#owner_name').show();
                $('#vendor_div').hide();
            }
        })
    </script>
@endpush
