@extends('backend.layouts.master')
@section("title","Edit Overall Cost")
@push('css')
    <link rel="stylesheet" href="{{asset('backend/plugins/bootstrap-datepicker/bootstrap-datepicker.css')}}">
@endpush
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Edit Overall Cost</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">Home</a></li>
                        <li class="breadcrumb-item active">Edit Overall Cost</li>
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
                        <h3 class="card-title float-left">Edit Overall Cost</h3>
                        <div class="float-right">
                            <a href="{{route('admin.overall-cost.index')}}">
                                <button class="btn btn-success">
                                    <i class="fa fa-backward"> </i>
                                    Back
                                </button>
                            </a>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <!-- form start -->
                    <form role="form" action="{{route('admin.overall-cost.update',$overallCost->id)}}" method="post" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="form-group">
                                <label for="overall_cost_category_id">Overall Cost Category <span>*</span></label>
                                <select name="overall_cost_category_id" id="overall_cost_category_id" class="form-control select2" required>
                                    <option value="">Select</option>
                                    @foreach($overallCostCategories as $overallCostCategory)
                                        <option value="{{$overallCostCategory->id}}" {{$overallCost->overall_cost_category_id == $overallCostCategory->id ? 'selected' : ''}}>{{$overallCostCategory->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="payment_type">Payment Type <span>*</span></label>
                                <select name="payment_type" id="payment_type" class="form-control select2" required>
                                    <option value="">Select</option>
                                    <option value="Cash" {{$overallCost->payment_type == 'Cash' ? 'selected' : ''}} >Cash</option>
                                    <option value="Cheque" {{$overallCost->payment_type == 'Cheque' ? 'selected' : ''}} >Cheque</option>
                                </select>
                                <span>&nbsp;</span>
                                <input type="text" name="cheque_number" id="cheque_number" class="form-control" placeholder="Cheque Number">
                            </div>
                            <div class="form-group">
                                <label for="amount">Amount</label>
                                <input type="number" class="form-control" name="amount" id="amount" value="{{$overallCost->amount}}">
                            </div>
                            <div class="form-group">
                                <label for="date">Date <span>*</span></label>
                                <input type="text" class="datepicker form-control" name="date" id="start_date" required value="{{$overallCost->date}}">
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

        $(function() {
            $('#cheque_number').hide();
            $('#payment_type').change(function(){
                if($('#payment_type').val() == 'Cheque') {
                    $('#cheque_number').show();
                } else {
                    $('#cheque_number').val('');
                    $('#cheque_number').hide();
                }
            });
        });
    </script>
@endpush
