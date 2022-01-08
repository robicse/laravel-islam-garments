@extends('backend.layouts.master')
@section("title","Staff Salary Detail")
@push('css')
    <link rel="stylesheet" href="{{asset('backend/plugins/bootstrap-datepicker/bootstrap-datepicker.css')}}">
@endpush
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Staff Salary Detail</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">Home</a></li>
                        <li class="breadcrumb-item active">Staff Salary Detail</li>
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
                        <h3 class="card-title float-left">Staff Salary Detail</h3>
                        <div class="float-right">
                            <a href="{{route('admin.staff-salary-list')}}">
                                <button class="btn btn-success">
                                    <i class="fa fa-backward"> </i>
                                    Back
                                </button>
                            </a>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <!-- form start -->
                    <div class="card-body">
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <span><b>Invoice Code:</b> {{$staffSalary->invoice_no}}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <span><b>Date:</b> {{$staffSalary->date}}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <span><b>Staff:</b> {{$staffSalary->user->name}}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <span><b>Payment Type:</b> {{$staffSalary->payment_type->name}}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <span><b>Year:</b> {{$staffSalary->year}}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group" id="paid_div">
                                        <span><b>Month:</b> {{$staffSalary->month}}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <span><b>Salary:</b> {{$staffSalary->salary}}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group" id="paid_div">
                                        <span><b>Paid:</b> Tk.{{$staffSalary->paid}}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group" id="due_price_div">
                                        <span><b>Due:</b> Tk.{{$staffSalary->due}}</span>
                                    </div>
                                    {{--                        <div class="form-group">--}}
                                    {{--                            <label for="exchange">Exchange</label>--}}
                                    {{--                            <input type="number" class="form-control" name="exchange" id="exchange" >--}}
                                    {{--                        </div>--}}
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <span><b>Note:</b> {{$staffSalary->note}}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
{{--                    <div class="card-footer">--}}
{{--                        <a class="btn btn-success float-right" href="{{route('admin.driver-salary-print',$driverSalary->id)}}" style="margin-right: 5px">--}}
{{--                            <i class="fa fa-print"></i>--}}
{{--                        </a>--}}
{{--                    </div>--}}
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

        $('#rent_type').change(function (){
            //alert();
            var vehicle_id = $('#vehicle_id').val();
            if(vehicle_id == ''){
                alert('Vehicle Select First!');
                $('#rent_type').val('');
            }
            var rent_duration = $('#rent_duration').val();
            if(rent_duration == ''){
                alert('Start Date And End Date Select First!');
                $('#rent_type').val('');
            }
            var quantity = $('#quantity').val();
            var rent_type = $('#rent_type').val();
            $.ajax({
                url:"{{URL('/admin/get/vehicle/price')}}",
                method:"POST",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    vehicle_id : vehicle_id
                },
                success:function (result){
                    console.log(result)
                    $('#price').val(result)
                    $('#sub_total').val(result*quantity)
                    $('#grand_discount').val(0)
                    $('#grand_total').val(result*quantity)
                },
                error:function (err){
                    console.log(err)
                }
            })
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
            $('#rent_duration').val(diffDays);
        })

        $('#paid').keyup(function (){
            var grand_total = $('#grand_total').val();
            var paid = $('#paid').val();
            var due_price = grand_total - paid;
            $('#due_price').val(due_price);
        })

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
