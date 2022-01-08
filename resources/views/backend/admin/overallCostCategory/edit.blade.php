@extends('backend.layouts.master')
@section("title","Edit Overall Cost Category")
@push('css')

@endpush
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Edit Overall Cost Category</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">Home</a></li>
                        <li class="breadcrumb-item active">Edit Overall Cost Category</li>
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
                        <h3 class="card-title float-left">Edit Overall Cost Category</h3>
                        <div class="float-right">
                            <a href="{{route('admin.overall-cost-categories.index')}}">
                                <button class="btn btn-success">
                                    <i class="fa fa-backward"> </i>
                                    Back
                                </button>
                            </a>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <!-- form start -->
                    <form role="form" action="{{route('admin.overall-cost-categories.update',$officeCostingCategory->id)}}" method="post" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="form-group">
                                <label for="name">Name <span>*</span></label>
                                <input type="text" class="form-control" name="name" id="name" value="{{$officeCostingCategory->name}}" required>
                            </div>
                            <div class="form-group">
                                <label for="status">Status <span>*</span></label>
                                <select name="status" id="status" class="form-control select2" required>
                                    <option value="1" {{$officeCostingCategory->status == 1 ? 'selected' : ''}}>Active</option>
                                    <option value="0" {{$officeCostingCategory->status == 0 ? 'selected' : ''}}>Inactive</option>
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
