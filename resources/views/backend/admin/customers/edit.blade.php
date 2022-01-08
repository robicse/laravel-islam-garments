@extends('backend.layouts.master')
@section("title","Edit Customer")
@push('css')

@endpush
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Edit Customer</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">Home</a></li>
                        <li class="breadcrumb-item active">Edit Customer</li>
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
                        <h3 class="card-title float-left">Edit Customer</h3>
                        <div class="float-right">
                            <a href="{{route('admin.customers.index')}}">
                                <button class="btn btn-success">
                                    <i class="fa fa-backward"> </i>
                                    Back
                                </button>
                            </a>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <!-- form start -->
                    <form role="form" action="{{route('admin.customers.update',$customer->id)}}" method="post" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="form-group">
                                <label for="name">Name <span>*</span></label>
                                <input type="text" class="form-control" name="name" id="name" value="{{$customer->name}}" required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone <span>*</span></label>
                                <input type="text" class="form-control" name="phone" id="phone"  value="{{$customer->phone}}" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="text" class="form-control" name="email" id="email"  value="{{$customer->email}}">
                            </div>
                            <div class="form-group">
                                <label for="customer_address">Customer Address</label>
                                <input type="text" class="form-control" name="customer_address" id="customer_address"  value="{{$customer->customer_address}}">
                            </div>
                            <div class="form-group">
                                <label for="status">Status <span>*</span></label>
                                <select name="status" id="status" class="form-control select2" required>
                                    <option value="1" {{$customer->status == 1 ? 'selected' : ''}}>Active</option>
                                    <option value="0" {{$customer->status == 0 ? 'selected' : ''}}>Inactive</option>
                                </select>
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

@endpush
