<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Islam Garments Ltd. | Invoice Print</title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 4 -->

    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{asset('backend/plugins/fontawesome-free/css/all.min.css')}}">
    <!-- Ionicons -->
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{asset('backend/dist/css/adminlte.min.css')}}">

    <!-- Google Font: Source Sans Pro -->
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
</head>
<body>
<div class="wrapper">
    <!-- Main content -->
    <section class="invoice">
        <!-- title row -->
        <div class="row">
            <div class="col-12">
                <h2 class="page-header">
                    <img src="{{asset('frontend/logo_sazidmart.png')}}">
                </h2>
            </div>
            <!-- /.col -->
        </div>
        <!-- info row -->
        <div class="row invoice-info">
            <div class="col-sm-4 invoice-col">
                <h5><b>Company Info:</b></h5>
                <address>
                    <b>Name :</b><strong> Islam Garments Ltd.</strong><br>
                    <b>Address :</b> Azad Plaza (4th Floor), TA-98/1, Gulshan, Badda Link Road, Gulshan-1, Dhaka-1212 <br>
                    <b>Phone :</b> +8801800000000<br>
                    <b>Email :</b> info@islam-garments.com<br>
                </address>
            </div>

            <!-- /.col -->
            <div class="col-sm-4 invoice-col">
                <h5><b>Vendor Info:</b></h5>
                <address>
                    <b>Vendor Name:</b> {{$vendor->name}}<br>
                    <b>Vendor Address:</b> {{$vendor->vendor_address}}<br>
                    <b>Vendor Email:</b> {{$vendor->email}}<br>
                    <b>Vendor Phone:</b> {{$vendor->phone}}<br>
                    <b>Company Name:</b> {{$vendor->company_name}}<br>
                    <b>Company Address:</b> {{$vendor->company_address}}<br>
                </address>
            </div>
            <!-- /.col -->
            <div class="col-sm-4 invoice-col">
                <b>Invoice NO:</b> {{$vehicleVendorRent->invoice_no}}<br>
                <br>
                <b>Date:</b> {{$vehicleVendorRent->date}}<br>
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->

        <!-- Table row -->
        <div class="row">
            <div class="col-12 table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Vehicle Name</th>
{{--                            <th>Driver Name</th>--}}
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Price (Tk.)</th>
                            <th>Quantity </th>
                            <th>Sub Total (Tk.)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{$vehicleVendorRentDetail->vehicle->vehicle_name}}</td>
{{--                            <td>{{$vehicleVendorRentDetail->driver->name}}</td>--}}
                            <td>{{$vehicleVendorRentDetail->start_date}}</td>
                            <td>{{$vehicleVendorRentDetail->end_date}}</td>
                            <td>{{number_format($vehicleVendorRentDetail->price, 2)}}</td>
                            <td>{{$vehicleVendorRentDetail->quantity}}</td>
                            <td>{{number_format($vehicleVendorRentDetail->price*$vehicleVendorRentDetail->quantity, 2)}}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->

        <div class="row mt-5">
            <!-- accepted payments column -->
            <div class="col-6">

            </div>
            <!-- /.col -->
            <div class="col-6">
{{--                <p class="lead">Amount Due 2/22/2014</p>--}}

                <div class="table-responsive">
                    <table class="table">
                        <tr>
                            <th style="width:50%">Subtotal:</th>
                            <td>Tk. {{number_format($vehicleVendorRentDetail->price*$vehicleVendorRentDetail->quantity, 2)}}</td>
                        </tr>
                        <tr>
                            <th>Discount :</th>
                            <td>
                                Tk. {{number_format($vehicleVendorRent->discount_amount, 2)}}
                            </td>
                        </tr>
                        <tr>
                            <th>Grand Total:</th>
                            <td>
                                Tk. {{number_format($vehicleVendorRent->grand_total, 2)}}
                            </td>
                        </tr>
                        <tr>
                            <th>Paid:</th>
                            <td>
                                Tk. {{number_format($vehicleVendorRent->paid, 2)}}
                            </td>
                        </tr>
                        <tr>
                            <th>Due:</th>
                            <td>
                                Tk. {{number_format($vehicleVendorRent->due_price, 2)}}
                            </td>
                        </tr>
                        <tr>
                            <th>Grand Total <small>(In Word)</small> :</th>
                            <td>
                                {{ucwords($digit->format($vehicleVendorRent->grand_total))}} Only.
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->
    </section>
    <!-- /.content -->
</div>
<!-- ./wrapper -->

<script type="text/javascript">
    window.addEventListener("load", window.print());
</script>
</body>
</html>
