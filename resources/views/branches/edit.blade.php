@extends('layouts.app')

@section('content')
<div class="sale-topbar">
    <div class="sale-topbar__title"><h2>Edit Branch</h2></div>
    <div class="sale-topbar__crumbs"><a href="{{ route('branches.index') }}">Branches</a><span>/</span><span>Edit Branch</span></div>
</div>
<div class="card"><div class="card-body">
    <form method="POST" action="{{ route('branches.update', $branch) }}">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-md-6 form-group"><label>Branch name</label><input class="form-control" name="name" value="{{ old('name', $branch->name) }}" required></div>
            <div class="col-md-6 form-group"><label>City</label><input class="form-control" name="city" value="{{ old('city', $branch->city) }}" required></div>
            <div class="col-md-6 form-group"><label>Contact</label><input class="form-control" name="contact" value="{{ old('contact', $branch->contact) }}" required></div>
            <div class="col-md-6 form-group"><label>License number</label><input class="form-control" name="license_number" value="{{ old('license_number', $branch->license_number) }}" required></div>
            <div class="col-12 form-group"><label>Address</label><textarea class="form-control" name="address" required>{{ old('address', $branch->address) }}</textarea></div>
        </div>
        <a class="btn btn-light" href="{{ route('branches.index') }}">Cancel</a>
        <button class="btn btn-primary">Update Branch</button>
    </form>
</div></div>
@endsection