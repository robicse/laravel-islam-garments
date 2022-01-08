@extends('backend.layouts.master')
@section("title","Edit Vehicle Vendor Rent")
@push('css')
    <link rel="stylesheet" href="{{asset('backend/plugins/bootstrap-datepicker/bootstrap-datepicker.css')}}">
@endpush
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Edit Vehicle Vendor Rent</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">Home</a></li>
                        <li class="breadcrumb-item active">Edit Vehicle Vendor Rent</li>
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
                        <h3 class="card-title float-left">Edit Vehicle Vendor Rent</h3>
                        <div class="float-right">
                            <a href="{{route('admin.vehicle-vendor-rent-list')}}">
                                <button class="btn btn-success">
                                    <i class="fa fa-backward"> </i>
                                    Back
                                </button>
                            </a>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <!-- form start -->
                    <form role="form" action="{{route('admin.vehicle-vendor-rent-update',$vehicleVendorRent->id)}}" method="post" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="form-group">
                                <label for="vendor_id">Vendor <span>*</span></label>
                                <select name="vendor_id" id="vendor_id" class="form-control select2" required>
                                    <option value="">Select</option>
                                    @foreach($vendors as $vendor)
                                        <option value="{{$vendor->id}}" {{$vehicleVendorRent->vendor_id == $vendor->id ? 'selected' : ''}}>{{$vendor->name}} ({{$vendor->vendor_code}})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="vehicle_id">Vehicle <span>*</span></label>
                                <select name="vehicle_id" id="vehicle_id" class="form-control select2" required>
                                    <option value="">Select</option>
                                    @foreach($vehicles as $vehicle)
                                        <option value="{{$vehicle->id}}" {{$vehicleVendorRentDetail->vehicle_id == $vehicle->id ? 'selected' : ''}}>{{$vehicle->vehicle_name}} ({{$vehicle->vehicle_code}})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="rent_type">Rent Type <span>*</span></label>
                                <select name="rent_type" id="rent_type" class="form-control select2" required>
                                    <option value="">Select</option>
                                    <option value="Daily" {{$vehicleVendorRentDetail->rent_type == 'Daily' ? 'selected' : ''}}>Daily</option>
                                    <option value="Monthly" {{$vehicleVendorRentDetail->rent_type == 'Monthly' ? 'selected' : ''}}>Monthly</option>
                                </select>
                            </div>
                            <div id="monthly_basis" style="@if($vehicleVendorRentDetail->rent_type == 'Daily') display: none @endif">
                                <div class="form-group">
                                    <label for="start_year_month">Start Year Month <span>*</span></label>
                                    <input type="text" class="datepicker2 form-control" name="start_year_month" id="start_year_month" value="{{$vehicleVendorRentDetail->start_year}}-{{$vehicleVendorRentDetail->start_month}}">
                                </div>
                                <div class="form-group">
                                    <label for="end_year_month">End Year Month <span>*</span></label>
                                    <input type="text" class="datepicker2 form-control" name="end_year_month" id="end_year_month" value="{{$vehicleVendorRentDetail->end_year}}-{{$vehicleVendorRentDetail->end_month}}">
                                </div>
                                <div class="form-group">
                                    <label for="rent_duration_month">Rent Duration Month <span>*</span></label>
                                    <input type="text" class="form-control" name="rent_duration_month" id="rent_duration_month" value="{{$vehicleVendorRentDetail->rent_duration_month}}" readonly>
                                </div>
                            </div>
                            <div id="daily_basis" style="@if($vehicleVendorRentDetail->rent_type == 'Monthly') display: none @endif">
                                <div class="form-group">
                                    <label for="start_date">Start Date <span>*</span></label>
                                    <input type="text" class="datepicker form-control" name="start_date" id="start_date" value="{{$vehicleVendorRentDetail->start_date}}" required>
                                </div>
                                <div class="form-group">
                                    <label for="end_date">End Date <span>*</span></label>
                                    <input type="text" class="datepicker form-control" name="end_date" id="end_date" value="{{$vehicleVendorRentDetail->end_date}}" required>
                                </div>
                                <div class="form-group">
                                    <label for="rent_duration_day">Rent Duration Day <span>*</span></label>
                                    <input type="text" class="form-control" name="rent_duration_day" id="rent_duration_day" value="{{$vehicleVendorRentDetail->rent_duration_day}}" readonly>
                                </div>
                            </div>
{{--                            <div class="form-group">--}}
{{--                                <label for="start_date">Start Date <span>*</span></label>--}}
{{--                                <input type="text" class="datepicker form-control" name="start_date" id="start_date" value="{{$vehicleVendorRentDetail->start_date}}">--}}
{{--                            </div>--}}
{{--                            <div class="form-group">--}}
{{--                                <label for="end_date">End Date <span>*</span></label>--}}
{{--                                <input type="text" class="datepicker form-control" name="end_date" id="end_date" value="{{$vehicleVendorRentDetail->end_date}}">--}}
{{--                            </div>--}}
{{--                            <div class="form-group">--}}
{{--                                <label for="rent_duration">Rent Duration <span>*</span></label>--}}
{{--                                <input type="text" class="form-control" name="rent_duration" id="rent_duration" value="{{$vehicleVendorRentDetail->rent_duration}}" readonly>--}}
{{--                            </div>--}}
                            <div class="form-group">
                                <label for="quantity">Quantity <span>*</span></label>
                                <input type="number" class="form-control" name="quantity" id="quantity" value="{{$vehicleVendorRentDetail->quantity}}">
                            </div>
                            <div class="form-group">
                                <label for="price">Price</label>
                                <input type="number" class="form-control" name="price" id="price" value="{{$vehicleVendorRentDetail->price}}" >
                            </div>
                            <div class="form-group">
                                <label for="sub_total">Sub Total</label>
                                <input type="number" class="form-control" name="sub_total" id="sub_total" value="{{$vehicleVendorRent->sub_total}}" readonly>
                            </div>
                            <div class="form-group">
                                <label for="discount_type">Discount Type</label>
                                <select name="discount_type" id="discount_type" class="form-control select2" required>
                                    <option value="">Select</option>
                                    <option value="Flat" {{$vehicleVendorRent->discount_type == 'Flat' ? 'selected' : ''}}>Flat</option>
                                    <option value="Percent" {{$vehicleVendorRent->discount_type == 'Percent' ? 'selected' : ''}}>Percent</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="discount_percent">Discount</label>
                                <input type="number" class="form-control" name="discount_percent" id="discount_percent" value="{{$vehicleVendorRent->discount_percent}}" onkeyup="discountPercent('')">
                            </div>
                            <div class="form-group">
                                <label for="discount_amount">Discount Amount</label>
                                <input type="number" class="form-control" name="discount_amount" id="discount_amount" value="{{$vehicleVendorRent->discount_amount}}" readonly>
                            </div>
                            <div class="form-group">
                                <label for="grand_total">Grand Total</label>
                                <input type="number" class="form-control" name="store_grand_total" id="store_grand_total" value="{{$vehicleVendorRent->grand_total}}" style="display: none">
                                <input type="number" class="form-control" name="grand_total" id="grand_total" value="{{$vehicleVendorRent->grand_total}}" readonly>
                            </div>
                            <div class="form-group">
                                <label for="payment_type_id">Payment Type <span>*</span></label>
                                <select name="payment_type_id" id="payment_type_id" class="form-control select2" required>
                                    <option value="">Select</option>
                                    @foreach($payment_types as $payment_type)
                                        <option value="{{$payment_type->id}}" {{$vehicleVendorRent->payment_type_id == $payment_type->id ? 'selected' : ''}}>{{$payment_type->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group" id="paid_div">
                                <label for="paid">Paid</label>
                                <input type="number" class="form-control" name="paid" id="paid" value="{{$vehicleVendorRent->paid}}">
                            </div>
                            <div class="form-group" id="due_price_div">
                                <label for="due_price">Due</label>
                                <input type="number" class="form-control" name="due_price" id="due_price"  value="{{$vehicleVendorRent->due_price}}" readonly>
                            </div>
                            {{--                        <div class="form-group">--}}
                            {{--                            <label for="exchange">Exchange</label>--}}
                            {{--                            <input type="number" class="form-control" name="exchange" id="exchange" >--}}
                            {{--                        </div>--}}
                            <div class="form-group">
                                <label for="note">Note</label>
                                <textarea type="text" class="form-control" name="note" id="note" >{{$vehicleVendorRentDetail->note}}</textarea>
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

        $('.datepicker2').datepicker({
            format: 'yyyy-mm',
            startDate: '-3d',
            //startDate: '-0d',
            todayBtn: "linked",
            autoclose: true,
            todayHighlight: true
        });

        $('#start_date').change(function (){
            var start_date = $('#start_date').val();
            //var end_date = $('#end_date').val();
            var vehicle_id = $('#vehicle_id').val();
            if(vehicle_id == ''){
                alert('Vehicle Select First!');
                $('#start_date').val('');
            }

            $.ajax({
                url: "{{URL('admin/check/already/vehicle/rent/or/not/this/date')}}",
                method:"POST",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    vehicle_id : vehicle_id,
                    start_date : start_date,
                    //end_date : end_date,
                },
                success:function (data){
                    console.log(data)
                    if(data > 0){
                        alert('Please select another vehicle, This vehicle already rent now.');
                        $('#vehicle_id').val('');
                    }else{
                        {{--$.ajax({--}}
                        {{--    url:"{{URL('/admin/get/vehicle/assigned/driver')}}",--}}
                        {{--    method:"POST",--}}
                        {{--    headers: {--}}
                        {{--        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')--}}
                        {{--    },--}}
                        {{--    data: {--}}
                        {{--        vehicle_id : vehicle_id,--}}
                        {{--        start_date : start_date,--}}
                        {{--        //end_date : end_date,--}}
                        {{--    },--}}
                        {{--    success:function (res){--}}
                        {{--        console.log(res)--}}
                        {{--        if(res == 0){--}}
                        {{--            alert('Please select another vehicle Or Date, This vehicle already rent now OR Not driver assign yet!');--}}
                        {{--            $('#vehicle_id').val('');--}}
                        {{--        }else{--}}
                        {{--            $('#driver_id').val(res);--}}
                        {{--        }--}}
                        {{--    },--}}
                        {{--    error:function (err){--}}
                        {{--        console.log(err)--}}
                        {{--    }--}}
                        {{--})--}}
                    }

                },
                error:function (err){
                    console.log(err)
                }
            })
        })

        {{--$('#rent_type').change(function (){--}}
        {{--    var vehicle_id = $('#vehicle_id').val();--}}
        {{--    if(vehicle_id == ''){--}}
        {{--        alert('Vehicle Select First!');--}}
        {{--        $('#rent_type').val('');--}}
        {{--    }--}}
        {{--    var rent_duration = $('#rent_duration').val();--}}
        {{--    if(rent_duration == ''){--}}
        {{--        alert('Start Date And End Date Select First!');--}}
        {{--        $('#rent_type').val('');--}}
        {{--    }--}}
        {{--    var quantity = $('#quantity').val();--}}
        {{--    var rent_type = $('#rent_type').val();--}}
        {{--    $.ajax({--}}
        {{--        url:"{{URL('/admin/get/vehicle/price')}}",--}}
        {{--        method:"POST",--}}
        {{--        headers: {--}}
        {{--            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')--}}
        {{--        },--}}
        {{--        data: {--}}
        {{--            vehicle_id : vehicle_id--}}
        {{--        },--}}
        {{--        success:function (result){--}}
        {{--            console.log(result)--}}
        {{--            $('#price').val(result)--}}
        {{--            $('#sub_total').val(result*quantity)--}}
        {{--            $('#grand_discount').val(0)--}}
        {{--            $('#grand_total').val(result*quantity)--}}
        {{--            $('#store_grand_total').val(result*quantity)--}}
        {{--            $('#paid').val(0)--}}
        {{--            $('#exchange').val(0)--}}
        {{--        },--}}
        {{--        error:function (err){--}}
        {{--            console.log(err)--}}
        {{--        }--}}
        {{--    })--}}
        {{--})--}}



        //$('#monthly_basis').hide();
        //$('#daily_basis').hide();
        $('#rent_type').change(function (){
            var vehicle_id = $('#vehicle_id').val();
            if(vehicle_id == ''){
                alert('Vehicle Select First!');
                $('#rent_type').val('');
            }
            var quantity = $('#quantity').val();
            var rent_type = $('#rent_type').val();

            if(rent_type == 'Daily'){
                $('#daily_basis').show();
                $('#monthly_basis').hide();
            }else{
                $('#monthly_basis').show();
                $('#daily_basis').hide();
            }


            {{--$.ajax({--}}
            {{--    url:"{{URL('/admin/get/vehicle/price')}}",--}}
            {{--    method:"POST",--}}
            {{--    headers: {--}}
            {{--        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')--}}
            {{--    },--}}
            {{--    data: {--}}
            {{--        vehicle_id : vehicle_id--}}
            {{--    },--}}
            {{--    success:function (result){--}}
            {{--        console.log(result)--}}
            {{--        $('#price').val(result)--}}
            {{--        $('#sub_total').val(result*quantity)--}}
            {{--        $('#grand_discount').val(0)--}}
            {{--        $('#grand_total').val(result*quantity)--}}
            {{--        $('#store_grand_total').val(result*quantity)--}}
            {{--        $('#paid').val(0)--}}
            {{--        $('#exchange').val(0)--}}
            {{--    },--}}
            {{--    error:function (err){--}}
            {{--        console.log(err)--}}
            {{--    }--}}
            {{--})--}}
        })

        var getDaysInMonth = function(month,year) {
            // Here January is 1 based
            //Day 0 is the last day in the previous month
            return new Date(year, month, 0).getDate();
            // Here January is 0 based
            // return new Date(year, month+1, 0).getDate();
        };

        $('#end_year_month').change(function (){
            var start_year_month = $('#start_year_month').val();
            var end_year_month = $('#end_year_month').val();
            if(start_year_month == ''){
                alert('Start Year Month Select First!');
                $('#end_year_month').val('');
            }

            var start_date = start_year_month+'-01';
            var dt = new Date(start_date);
            var month = dt.getMonth() + 1;
            var year = dt.getFullYear();
            var month_days = getDaysInMonth(month, year);
            console.log(month_days);
            var end_date = end_year_month+'-'+month_days;

            $('#start_date').val(start_date)
            $('#end_date').val(end_date)


            //alert(start_date);
            const oneDay = 24 * 60 * 60 * 1000; // hours*minutes*seconds*milliseconds
            const firstDate = new Date(start_date);
            const secondDate = new Date(end_date);

            const diffDays = Math.round(Math.abs((firstDate - secondDate) / oneDay));
            const diffMonths = Math.round(Math.abs((firstDate - secondDate) / (oneDay*month_days)));
            $('#rent_duration_day').val(diffDays);
            $('#rent_duration_month').val(diffMonths);
        })

        $('#end_date').change(function (){
            var start_date = $('#start_date').val();
            var end_date = $('#end_date').val();
            if(start_date == ''){
                alert('Start Date Select First!');
                $('#end_date').val('');
            }


            //alert(start_date);
            const oneDay = 24 * 60 * 60 * 1000; // hours*minutes*seconds*milliseconds
            const firstDate = new Date(start_date);
            const secondDate = new Date(end_date);

            const diffDays = Math.round(Math.abs((firstDate - secondDate) / oneDay));
            $('#rent_duration_day').val(diffDays);
        })

        $('#price').keyup(function (){
            var price = $('#price').val();
            var rent_type = $('#rent_type').val();
            var quantity = $('#quantity').val();

            var grand_total = 0
            if(rent_type == 'Daily'){
                var rent_duration_day = $('#rent_duration_day').val();
                grand_total = (price*rent_duration_day)*quantity;
            }else{
                var rent_duration_month = $('#rent_duration_month').val();
                grand_total = (price*rent_duration_month)*quantity;
            }

            $('#sub_total').val(grand_total);
            $('#grand_total').val(grand_total);
            var paid = $('#paid').val();
            var due_price = grand_total - paid;
            $('#due_price').val(due_price);
        })

        $('#paid').keyup(function (){
            var grand_total = $('#grand_total').val();
            var paid = $('#paid').val();
            var due_price = grand_total - paid;
            $('#due_price').val(due_price);
        })

        function discountAmount(){

            var store_grand_total = $('#store_grand_total').val();
            console.log('store_grand_total= ' + store_grand_total);
            console.log('store_grand_total= ' + typeof store_grand_total);

            var grand_discount = $('#grand_discount').val();
            console.log('grand_discount= ' + grand_discount);
            console.log('grand_discount= ' + typeof grand_discount);

            var grand_total = store_grand_total - grand_discount;
            console.log('grand_total=' + grand_total);
            console.log('grand_total=' + typeof grand_total);

            $('#grand_discount').val(grand_discount)
            $('#grand_total').val(grand_total);
            $('#due_price').val(grand_total);
        }

        // check cash or credit paid
        var current_payment_type_id = $('#payment_type_id').val();
        if(current_payment_type_id == 1){
            $('#paid_div').hide();
            $('#due_price_div').hide();
        }
        $('#payment_type_id').change(function (){
            var payment_type_id = $('#payment_type_id').val();
            if(payment_type_id == 2){
                $('#paid_div').show();
                $('#due_price_div').show();
                // $('#paid').val(0);
                // var grand_total = $('#grand_total').val();
                // $('#due_price').val(grand_total);
            }else{
                $('#paid_div').hide();
                $('#due_price_div').hide();
                $('#paid').val(0);
                $('#due_price').val(0);
            }
        })
    </script>
@endpush
