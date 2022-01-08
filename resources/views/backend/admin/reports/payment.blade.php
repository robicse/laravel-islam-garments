@extends('backend.layouts.master')
@section("title","Payment List")
@push('css')
    <link rel="stylesheet" href="{{asset('backend/plugins/datatables/dataTables.bootstrap4.css')}}">
@endpush
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Payment List</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">Home</a></li>
                        <li class="breadcrumb-item active">Payment List</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-12">
                @if(session('response'))
                    <div class="alert alert-success">
                        {{ session('response') }}
                    </div>
                @endif
                <form method="post" action="{{ route('admin.report-payment') }}">
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
                            <a href="{!! route('admin.get-report-payment') !!}" class="btn btn-primary" type="button"><i class="fa fa-refresh"> Reset</i></a>
                        </div>
                    </div>
                </form>
                <div class="card card-info card-outline">
                    <div class="card-header">
                        <h3 class="card-title float-left">Payment Lists</h3>
                        <div class="float-right">
{{--                            <a href="{{route('admin.vehicle-customer-rent-create')}}">--}}
{{--                                <button class="btn btn-success">--}}
{{--                                    <i class="fa fa-plus-circle"></i>--}}
{{--                                    Add--}}
{{--                                </button>--}}
{{--                            </a>--}}
                            @if($date_from !== '' && $date_to !== '')
                                <a href="{{ url('/admin/report/payments-print/'.$date_from.'/'.$date_to) }}" target="_blank" class="btn btn-sm btn-primary float-right">Print</a>
                            @endif
                        </div>
                    </div>

                    <!-- /.card-header -->
                    <div class="card-body table-responsive">
                        <table id="example1" class="table table-bordered table-striped">
                            <thead>
                            <tr>
                                <th>#SL</th>
                                <th>Invoice NO</th>
                                <th>Transaction Type</th>
                                <th>Date Time</th>
                                <th>Paid To</th>
                                <th>Method</th>
                                <th>Cash(TK)</th>
                                <th>Credit(TK)</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php
                                $cashTotal = 0;
                                $creditTotal = 0;
                                $balance = 0;
                            @endphp
                            @foreach($payments as $key => $payment)
                                @php
                                    if($payment->payment_type_id == 1){
                                        $cashTotal += $payment->paid;
                                    }
                                    if($payment->payment_type_id == 2){
                                        $creditTotal += $payment->paid;
                                    }
                                @endphp
                            <tr>
                                <td>{{$key + 1}}</td>
                                <td>{{$payment->order->invoice_no}}</td>
                                <td>{{$payment->transaction_type}}</td>
                                <td>{{$payment->created_at}}</td>
                                <td>
                                    {{getPaidToName($payment->paid_user_id, $payment->transaction_type)}}
                                </td>
                                <td>{{$payment->payment_type->name}}</td>
                                <td class="text-right">
                                    @if($payment->payment_type_id == 1)
                                        {{ number_format($payment->paid,2,'.',',') }}
                                    @endif
                                </td>
                                <td class="text-right">
                                    @if($payment->payment_type_id == 2)
                                        {{ number_format($payment->paid,2,'.',',') }}
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                            </tbody>
                            <tfoot>
                            <tr>
                                <th>#SL</th>
                                <th>Invoice NO</th>
                                <th>Transaction Type</th>
                                <th>Date Time</th>
                                <th>Paid To</th>
                                <th>Method</th>
                                <th>Cash(TK)</th>
                                <th>Credit(TK)</th>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                    <!-- /.card-body -->
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
