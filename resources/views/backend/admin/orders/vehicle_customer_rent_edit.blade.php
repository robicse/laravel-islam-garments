@extends('backend.layouts.master')
@section("title","Edit Vehicle Customer Rent")
@push('css')
    <link rel="stylesheet" href="{{asset('backend/plugins/bootstrap-datepicker/bootstrap-datepicker.css')}}">
@endpush
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Edit Vehicle Customer Rent</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">Home</a></li>
                        <li class="breadcrumb-item active">Edit Vehicle Customer Rent</li>
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
                        <h3 class="card-title float-left">Edit Vehicle Customer Rent</h3>
                        <div class="float-right">
                            <a href="{{route('admin.vehicle-customer-rent-list')}}">
                                <button class="btn btn-success">
                                    <i class="fa fa-backward"> </i>
                                    Back
                                </button>
                            </a>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <!-- form start -->
                    <form role="form" action="{{route('admin.vehicle-customer-rent-update',$vehicleCustomerRent->id)}}" method="post" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="form-group">
                                <label for="customer_id">Customer <span>*</span></label>
                                <select name="customer_id" id="customer_id" class="form-control select2" required>
                                    <option value="">Select</option>
                                    @foreach($customers as $customer)
                                        <option value="{{$customer->id}}" {{$vehicleCustomerRent->customer_id == $customer->id ? 'selected' : ''}}>{{$customer->name}} ({{$customer->customer_code}})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="vehicle_id">Vehicle <span>*</span></label>
                                <select name="vehicle_id" id="vehicle_id" class="form-control select2" required>
                                    <option value="">Select</option>
                                    @foreach($vehicles as $vehicle)
                                        <option class="@if($vehicle->driver_id === NULL) bg bg-danger @endif" value="{{$vehicle->id}}" {{$vehicleCustomerRentDetail->vehicle_id == $vehicle->id ? 'selected' : ''}}>{{$vehicle->vehicle_name}} ({{$vehicle->vehicle_code}}) @if($vehicle->driver_id !== NULL) ({{$vehicle->driver->name}}) @endif</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="rent_type">Rent Type <span>*</span></label>
                                <select name="rent_type" id="rent_type" class="form-control select2" required>
                                    <option value="">Select</option>
                                    <option value="Daily" {{$vehicleCustomerRentDetail->rent_type == 'Daily' ? 'selected' : ''}}>Daily</option>
                                    <option value="Monthly" {{$vehicleCustomerRentDetail->rent_type == 'Monthly' ? 'selected' : ''}}>Monthly</option>
                                </select>
                            </div>
                            <div id="monthly_basis" style="@if($vehicleCustomerRentDetail->rent_type == 'Daily') display: none @endif">
                                <div class="form-group">
                                    <label for="start_year_month">Start Year Month <span>*</span></label>
                                    <input type="text" class="datepicker2 form-control" name="start_year_month" id="start_year_month" value="{{$vehicleCustomerRentDetail->start_year}}-{{$vehicleCustomerRentDetail->start_month}}">
                                </div>
                                <div class="form-group">
                                    <label for="end_year_month">End Year Month <span>*</span></label>
                                    <input type="text" class="datepicker2 form-control" name="end_year_month" id="end_year_month" value="{{$vehicleCustomerRentDetail->end_year}}-{{$vehicleCustomerRentDetail->end_month}}">
                                </div>
                                <div class="form-group">
                                    <label for="rent_duration_month">Rent Duration Month <span>*</span></label>
                                    <input type="text" class="form-control" name="rent_duration_month" id="rent_duration_month" value="{{$vehicleCustomerRentDetail->rent_duration_month}}" readonly>
                                </div>
                            </div>
                            <div id="daily_basis" style="@if($vehicleCustomerRentDetail->rent_type == 'Monthly') display: none @endif">
                                <div class="form-group">
                                    <label for="start_date">Start Date <span>*</span></label>
                                    <input type="text" class="datepicker form-control" name="start_date" id="start_date" value="{{$vehicleCustomerRentDetail->start_date}}" required>
                                </div>
                                <div class="form-group">
                                    <label for="end_date">End Date <span>*</span></label>
                                    <input type="text" class="datepicker form-control" name="end_date" id="end_date" value="{{$vehicleCustomerRentDetail->end_date}}" required>
                                </div>
                                <div class="form-group">
                                    <label for="rent_duration_day">Rent Duration Day <span>*</span></label>
                                    <input type="text" class="form-control" name="rent_duration_day" id="rent_duration_day" value="{{$vehicleCustomerRentDetail->rent_duration_day}}" readonly>
                                </div>
                            </div>
                            {{--                        <div class="form-group">--}}
                            {{--                            <label for="driver_id">Driver <span>*</span></label>--}}
                            {{--                            <input type="text" class="form-control" name="driver_id" id="driver_id" required readonly>--}}
                            {{--                        </div>--}}
                            <div class="form-group">
                                <label for="quantity">Quantity <span>*</span></label>
                                <input type="number" class="form-control" name="quantity" id="quantity" value="{{$vehicleCustomerRentDetail->quantity}}">
                            </div>
                            <div class="form-group">
                                <label for="price">Price</label>
                                <input type="number" class="form-control" name="price" id="price" value="{{$vehicleCustomerRentDetail->price}}" >
                            </div>
                            <div class="form-group">
                                <label for="sub_total">Sub Total</label>
                                <input type="number" class="form-control" name="sub_total" id="sub_total" value="{{$vehicleCustomerRent->sub_total}}" readonly>
                            </div>
                            <div class="form-group">
                                <label for="discount_type">Discount Type</label>
                                <select name="discount_type" id="discount_type" class="form-control select2" required>
                                    <option value="">Select</option>
                                    <option value="Flat" {{$vehicleCustomerRent->discount_type == 'Flat' ? 'selected' : ''}}>Flat</option>
                                    <option value="Percent" {{$vehicleCustomerRent->discount_type == 'Percent' ? 'selected' : ''}}>Percent</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="discount_percent">Discount</label>
                                <input type="number" class="form-control" name="discount_percent" id="discount_percent" value="{{$vehicleCustomerRent->discount_percent}}" onkeyup="discountPercent('')">
                            </div>
                            <div class="form-group">
                                <label for="discount_amount">Discount Amount</label>
                                <input type="number" class="form-control" name="discount_amount" id="discount_amount" value="{{$vehicleCustomerRent->discount_amount}}" readonly>
                            </div>
                            <div class="form-group">
                                <label for="grand_total">Grand Total</label>
                                <input type="number" class="form-control" name="grand_total" id="grand_total" value="{{$vehicleCustomerRent->grand_total}}" readonly>
                            </div>
                            <div class="form-group">
                                <label for="payment_type_id">Payment Type <span>*</span></label>
                                <select name="payment_type_id" id="payment_type_id" class="form-control select2" required>
                                    <option value="">Select</option>
                                    @foreach($payment_types as $payment_type)
                                        <option value="{{$payment_type->id}}" {{$vehicleCustomerRent->payment_type_id == $payment_type->id ? 'selected' : ''}}>{{$payment_type->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group" id="paid_div">
                                <label for="paid">Paid</label>
                                <input type="number" class="form-control" name="paid" id="paid" value="{{$vehicleCustomerRent->paid}}">
                            </div>
                            <div class="form-group" id="due_price_div">
                                <label for="due_price">Due</label>
                                <input type="number" class="form-control" name="due_price" id="due_price" value="{{$vehicleCustomerRent->due_price}}" readonly>
                            </div>
                            <div class="form-group">
                                <label for="note">Note</label>
                                <textarea type="text" class="form-control" name="note" id="note" >{{$vehicleCustomerRentDetail->note}}</textarea>
                            </div>
                            <!-- /.card-body -->
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </div>
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

        $('#vehicle_id').change(function (){
            //alert();
            var vehicle_id = $('#vehicle_id').val();
            $.ajax({
                //url:"{{URL('/admin/get/vehicle/assigned/driver')}}/" + vehicle_id,
                url:"{{URL('/admin/check/already/vehicle/assigned/to/driver')}}/" + vehicle_id,
                method:"GET",
                success:function (data){
                    console.log(data)
                    if(data == ''){
                        //alert('Please select another vehicle, This vehicle already assigned.');
                        alert('No driver found this vehicle, Please select another vehicle.');
                        $('#vehicle_id').val('');
                    }
                },
                error:function (err){
                    console.log(err)
                }
            })
        })

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

        function discountPercent(){
            var discount_type = $('#discount_type').val();
            var sub_total = $('#sub_total').val();
            var discount = $('#discount_percent').val();
            if(discount_type == 'Percent'){
                var discount_percent = (sub_total*discount)/100;
                var discount_amount = discount_percent;
                var grand_total = sub_total - discount_percent;
            }else{
                var discount_amount = discount;
                var grand_total = sub_total - discount;
            }
            $('#discount_amount').val(discount_amount);
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
