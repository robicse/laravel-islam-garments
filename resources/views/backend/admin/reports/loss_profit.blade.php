@extends('backend.layouts.master')
@section("title","Payment List")
@push('css')
    <link rel="stylesheet" href="{{asset('backend/plugins/datatables/dataTables.bootstrap4.css')}}">
@endpush

@section('content')
    <section class="content-header">
        <div class="row">
            <div class="col-12">
            <div>
                <h1><i class=""></i>Loss/Profit</h1>
            </div>
{{--            <ul class="app-breadcrumb breadcrumb">--}}
{{--                <li class="breadcrumb-item">--}}
{{--                    @if($start_date != '' && $end_date != '')--}}
{{--                        <a class="btn btn-warning" href="{{ url('loss-profit-filter-export/'.$start_date."/".$end_date) }}">Export Data</a>--}}
{{--                    @else--}}
{{--                        <a class="btn btn-warning" href="{{ route('loss.profit.export') }}">Export Data</a>--}}
{{--                    @endif--}}
{{--                </li>--}}
{{--            </ul>--}}
            </div>

        <div class="col-md-12">
            <div class="tile">
{{--                <h3 class="tile-title">Loss/Profit Table</h3>--}}
                <form method="post" action="{{ route('admin.report-loss-profit') }}">
                    @csrf
                    <div class="form-group row">
                        <label class="control-label col-md-3 text-right">From</label>
                        <div class="col-md-8">
                            <input type="date" class="form-control-sm" name="date_from" value="{{ $date_from }}" id="demoDate" required>
                            @if ($errors->has('date_from'))
                                <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('date_from') }}</strong>
                                    </span>
                            @endif
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="control-label col-md-3 text-right">To</label>
                        <div class="col-md-8">
                            <input type="date" class="form-control-sm" name="date_to" value="{{ $date_to }}" required />
                            @if ($errors->has('date_to'))
                                <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('date_to') }}</strong>
                                    </span>
                            @endif
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="control-label col-md-3"></label>
                        <div class="col-md-8">
                            <button class="btn btn-primary" type="submit">
                                <i class="fa fa-fw fa-lg fa-check-circle"></i>View
                            </button>
                            <a href="{!! route('admin.get-report-loss-profit') !!}" class="btn btn-primary" type="button"><i class="fa fa-refresh"> Reset</i></a>
                        </div>
                    </div>
                </form>
                <div>&nbsp;</div>

                @php
                    $total_vendor_amount = getVendorTotalAmount($date_from, $date_to);
                    $total_customer_amount = getCustomerTotalAmount($date_from, $date_to);
                    $total_driver_amount = getDriverTotalAmount($date_from, $date_to);
                    $total_staff_amount = getStaffTotalAmount($date_from, $date_to);
                    $total_overall_cost_amount = getOverallCostTotalAmount($date_from, $date_to);

                    // expense
                    $total_expense = 0;
                    $sum_vendor_amount = 0;
                    if(!empty($total_vendor_amount)){
                        $total_expense += $total_vendor_amount->total_paid;
                        $sum_vendor_amount = $total_vendor_amount->total_paid;
                    }

                    $sum_driver_amount = 0;
                    if(!empty($total_driver_amount)){
                        $total_expense += $total_driver_amount->total_paid;
                        $sum_driver_amount = $total_driver_amount->total_paid;
                    }

                    $sum_staff_amount = 0;
                    if(!empty($total_staff_amount)){
                        $total_expense += $total_staff_amount->total_paid;
                        $sum_staff_amount = $total_staff_amount->total_paid;
                    }

                    $sum_overall_cost_amount = 0;
                    if(!empty($total_overall_cost_amount)){
                        $total_expense += $total_overall_cost_amount->total_paid;
                        $sum_overall_cost_amount = $total_overall_cost_amount->total_paid;
                    }

                    // income
                    $total_income = 0;
                    if(!empty($total_customer_amount)){
                        $total_income += $total_customer_amount->total_paid;
                    }
                @endphp

                <div class="col-md-12">
                    <div class="card-header">
                        <h3 class="card-title float-left">Loss/Profit Sheet @if(!empty($date_from)) from date {{ $date_from }} to date {{ $date_to }}@endif</h3>
                        <div class="float-right">
                            @if($date_from !== '' && $date_to !== '')
                                <a href="{{ url('/admin/report/loss-profit-print/'.$date_from.'/'.$date_to) }}" target="_blank" class="btn btn-sm btn-primary float-right">Print</a>
                            @else
                                <a href="{{ url('/admin/report/loss-profit-print/2020-11-01/'.date('Y-m-d')) }}" target="_blank" class="btn btn-sm btn-primary float-right">Print</a>
                            @endif
                        </div>
                    </div>

                    <table class="table table-bordered">
                        <thead>
                        <tr>
                            <th class="text-center">Expense</th>
                            <th class="text-center">Amount(TK.)</th>
                            <th class="text-center">Income</th>
                            <th class="text-center">Amount(TK.)</th>
                        </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Vendor</td>
                                <td class="text-right">{{number_format($sum_vendor_amount,2,'.',',')}}</td>
                                <td>Customer</td>
                                <td class="text-right">{{number_format($total_income,2,'.',',')}}</td>
                            </tr>
                            <tr>
                                <td>Driver</td>
                                <td class="text-right">{{number_format($sum_driver_amount,2,'.',',')}}</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                            </tr>
                            <tr>
                                <td>Staff</td>
                                <td class="text-right">{{number_format($sum_staff_amount,2,'.',',')}}</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                            </tr>
                            <tr>
                                <td>Total Overall Cost Amount</td>
                                <td class="text-right">{{number_format($sum_overall_cost_amount,2,'.',',')}}</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                            </tr>
                            <tr>
                                <td class="text-right">Total:</td>
                                <td class="text-right">{{number_format($total_expense,2,'.',',')}}</td>
                                <td class="text-right">Total:</td>
                                <td class="text-right">{{number_format($total_income,2,'.',',')}}</td>
                            </tr>
                            <?php
                            if($total_income > $total_expense){
                                $loss_profit_status = 'Profit';
                                $loss_profit_amount = $total_income - $total_expense;
                            }elseif($total_expense > $total_income){
                                $loss_profit_status = 'Loss';
                                $loss_profit_amount = $total_expense - $total_income;
                            }else{
                                $loss_profit_status = 'Loss/Profit';
                                $loss_profit_amount = 0;
                            }
                            ?>
                            <tr class="@if($loss_profit_status === 'Profit') bg-success @elseif($loss_profit_status === 'Loss') bg-danger @endif">
                                <td class="text-right" colspan="2">&nbsp;</td>
                                <td class="text-right">
                                    {{ $loss_profit_status }}
                                </td>
                                <td class="text-right">
                                    {{number_format($loss_profit_amount,2,'.',',')}}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        </div>
    </section>

@stop
@push('js')
    <script src="{{asset('backend/plugins/datatables/jquery.dataTables.js')}}"></script>
    <script src="{{asset('backend/plugins/datatables/dataTables.bootstrap4.js')}}"></script>
    <script src="https://unpkg.com/sweetalert2@7.19.1/dist/sweetalert2.all.js"></script>
    <script>
        $(function () {
            $("#example1").DataTable();
            $('#example2').DataTable({
                "paging": true,
                "lengthChange": false,
                "searching": false,
                "ordering": true,
                "info": true,
                "autoWidth": false
            });
        });

        //sweet alert
        function deleteVehicle(id) {
            swal({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'No, cancel!',
                confirmButtonClass: 'btn btn-success',
                cancelButtonClass: 'btn btn-danger',
                buttonsStyling: false,
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    document.getElementById('delete-form-'+id).submit();
                } else if (
                    // Read more about handling dismissals
                    result.dismiss === swal.DismissReason.cancel
                ) {
                    swal(
                        'Cancelled',
                        'Your Data is save :)',
                        'error'
                    )
                }
            })
        }
    </script>
@endpush





