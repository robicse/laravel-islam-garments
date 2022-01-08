
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
                    <p style="margin: 0px"><b>All Customer Balance Sheet</b> </p>
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
                                <h5 align="center">Customer Balance Sheet from date {{ $date_from }} to date {{ $date_to }}</h5>
                                <table class="invoice">
                                    <tr>
                                        <th width="10%">SL</th>
                                        <th width="30%">Customer</th>
                                        <th width="10%">Paid Amount(TK)</th>
                                    </tr>
                                    @php
                                        $final_paid_amount = 0;
                                        $flag = 0;
                                        $first_day = date('Y-m-01',strtotime($date_from));
                                        $last_day = date('Y-m-t',strtotime($date_from));
                                        $sl = 0;
                                    @endphp
                                    @if(!empty($customer_cash_data_results))
                                        @foreach($customer_cash_data_results as $key => $cash_data_result)
                                            @php
                                                $customer = \App\Model\Customer::where('id',$cash_data_result->paid_user_id)->pluck('name')->first();
                                                $sl ++;
                                                $final_paid_amount += $cash_data_result->total_paid;
                                            @endphp
                                            <tr>
                                                <td>{{$sl}}</td>
                                                <td>{{$customer}}</td>
                                                <td style="text-align: right">{{ number_format($cash_data_result->total_paid,2,'.',',') }}</td>
                                            </tr>
                                        @endforeach
                                    @endif
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>Total</td>
                                        <td style="text-align: right">{{ number_format($final_paid_amount,2,'.',',') }}</td>
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



