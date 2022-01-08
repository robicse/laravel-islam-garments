@extends('backend.layouts.master')
@section("title","Add Vendor")
@push('css')

@endpush
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Add Vendor</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">Home</a></li>
                        <li class="breadcrumb-item active">Add Vendor</li>
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
                    <h3 class="card-title float-left">Add Vendor</h3>
                    <div class="float-right">
                        <a href="{{route('admin.vendors.index')}}">
                            <button class="btn btn-success">
                                <i class="fa fa-backward"> </i>
                                Back
                            </button>
                        </a>
                    </div>
                </div>
                <!-- /.card-header -->
                <!-- form start -->
                <form role="form" action="{{route('admin.vendors.store')}}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="name">Name <span>*</span></label>
                            <input type="text" class="form-control" name="name" id="name" placeholder="" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone <span>*</span></label>
                            <input type="text" class="form-control" name="phone" id="phone" placeholder="" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="text" class="form-control" name="email" id="email" placeholder="">
                        </div>
                        <div class="form-group">
                            <label for="vendor_address">Vendor Address</label>
                            <input type="text" class="form-control" name="vendor_address" id="vendor_address" placeholder="">
                        </div>
                        <div class="form-group">
                            <label for="company_name">Company Name</label>
                            <input type="text" class="form-control" name="company_name" id="company_name" placeholder="">
                        </div>
                        <div class="form-group">
                            <label for="company_address">Company Address</label>
                            <input type="text" class="form-control" name="company_address" id="company_address" placeholder="">
                        </div>
                        <div class="form-group">
                            <label for="email">Vendor Image <small>(size: 120 * 80 pixel)</small> <span>*</span></label>
                            <input type="file" class="form-control" name="logo" id="logo" required>
                        </div>
{{--                        <div class="form-group">--}}
{{--                            <label for="meta_desc">Meta Description</label>--}}
{{--                            <textarea name="meta_description" id="meta_desc" class="form-control"  rows="3"></textarea>--}}
{{--                        </div>--}}
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

@endpush
