
<div id="printArea">
    <style>
        /* Styles go here */

        .page-header, .page-header-space {
            height: 150px;
        }

        .page-footer, .page-footer-space {
            height: 100px;
        }

        .page-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            border-top: 1px solid black; /* for demo */
            /*background: yellow;*/ /* for demo */
            /*padding: 5px 20px;*/
        }

        .page-header {
            position: fixed;
            top: 0mm;
            left: 0;
            width: 100%;
            /*height: 100px;*/
            border-bottom: 1px solid black; /* for demo */
            /*background: yellow;*/ /* for demo */
        }

        .page {
            page-break-after: always;
        }

        @page {
            /*margin: 20mm;*/
            /*size: A4;
            margin: 11mm 17mm 17mm 17mm;*/
        }

        @media screen {
            .page-header {display: none;}
            .page-footer {display: none;}
        }

        @media print {
            table { page-break-inside:auto }
            tr    { page-break-inside:auto; page-break-after:auto }
            thead { display:table-header-group }
            tfoot { display:table-footer-group }

            button {display: none;}

            body {margin: 0;}
        }

        /*custom part start*/
        .invoice {
            border-collapse: collapse;
            /*width: 100%;*/
            width: 280mm;
            text-align: center
        }
        .invoice th, .invoice td {
            border: 1px solid #000;
        }
        /*custom part end*/

    </style>
    <main class="app-content">
        <div class="row">
            <div class="col-md-12">

{{--                <div class="page-header" style="text-align: left">--}}
{{--                    <img src="{{ asset('backend/2020-11-21.png') }}" width="200px" height="150px" alt="header img">--}}
{{--                </div>--}}
                <div class="col-md-8" style="text-align: center; margin-left: 100px">
                    <h2>StarIT LTD</h2>
                    <p style="margin: 0px"><b>SHASCHAND PARIBAHAN SONGTHA</b></p>
                    <p style="margin: 0px"><b>Loss Profit</b> </p>
                    <p style="margin: 0px"><b>From Date: {{$date_from}}</b> </p>
                    <p style="margin: 0px"><b>To Date: {{$date_to}}</b> </p>

                </div>
{{--                <div class="page-footer">--}}
{{--                    <img src="{{ asset('footer.png') }}" width="100%" height="auto" alt="footer img">--}}
{{--                </div>--}}
                <table>
{{--                    <thead>--}}
{{--                    <tr>--}}
{{--                        <td>--}}
{{--                            <!--place holder for the fixed-position header-->--}}
{{--                            <div class="page-header-space"></div>--}}
{{--                        </td>--}}
{{--                    </tr>--}}
{{--                    </thead>--}}
                    <tbody>
                    <tr>
                        <td>
                            <!--*** CONTENT GOES HERE ***-->
                            <div class="page" style="padding: 10px;">
                                <h5 align="center">Loss Profit @if(!empty($date_from)) from date {{ $date_from }} to date {{ $date_to }}@endif</h5>
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
                                <table class="invoice">
                                    <tr>
                                        <th width="25%">Expense</th>
                                        <th width="25%">Amount(TK.)</th>
                                        <th width="25%">Income</th>
                                        <th width="25%">Amount(TK.)</th>
                                    </tr>
                                    <tr>
                                        <td>Vendor</td>
                                        <td style="text-align: right">{{number_format($sum_vendor_amount,2,'.',',')}}</td>
                                        <td>Customer</td>
                                        <td style="text-align: right">{{number_format($total_income,2,'.',',')}}</td>
                                    </tr>
                                    <tr>
                                        <td>Driver</td>
                                        <td style="text-align: right">{{number_format($sum_driver_amount,2,'.',',')}}</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td>Staff</td>
                                        <td style="text-align: right">{{number_format($sum_staff_amount,2,'.',',')}}</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td>Total Overall Cost Amount</td>
                                        <td style="text-align: right">{{number_format($sum_overall_cost_amount,2,'.',',')}}</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td style="text-align: right">Total:</td>
                                        <td style="text-align: right">{{number_format($total_expense,2,'.',',')}}</td>
                                        <td style="text-align: right">Total:</td>
                                        <td style="text-align: right">{{number_format($total_income,2,'.',',')}}</td>
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
                                    <tr style="@if($loss_profit_status === 'Profit') background-color:green; @elseif($loss_profit_status === 'Loss') background-color:red; @endif">
                                        <td style="text-align: right" colspan="2">&nbsp;</td>
                                        <td style="text-align: right">
                                            {{ $loss_profit_status }}
                                        </td>
                                        <td style="text-align: right">
                                            {{number_format($loss_profit_amount,2,'.',',')}}
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                    </tr>
                    </tbody>

{{--                    <tfoot>--}}
{{--                    <tr>--}}
{{--                        <td>--}}
{{--                            <!--place holder for the fixed-position footer-->--}}
{{--                            <div class="page-footer-space"></div>--}}
{{--                        </td>--}}
{{--                    </tr>--}}
{{--                    </tfoot>--}}
                </table>
            </div>
        </div>
    </main>
</div>

<!-- select2-->
<script src="{!! asset('backend/js/plugins/select2.min.js') !!}"></script>
<script src="{!! asset('backend/js/plugins/bootstrap-datepicker.min.js') !!}"></script>
<script>
    window.print();
</script>



