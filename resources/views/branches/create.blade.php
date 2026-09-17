@extends('layouts.app')

@section('content')
<div class="sale-topbar">
    <div class="sale-topbar__title"><h2>Add Branch</h2></div>
    <div class="sale-topbar__crumbs"><a href="{{ route('dashboard') }}">Dashboard</a><span>/</span><span>Add Branch</span></div>
</div>
<div class="card"><div class="card-body">
    <p class="text-muted">Add a new active location to your pharmacy after launch.</p>
    @if (session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
    <form method="POST" action="{{ route('branches.store') }}">
        @csrf
        <div class="row">
            <div class="col-md-6 form-group"><label>Branch name</label><input class="form-control" name="name" value="{{ old('name') }}" required></div>
            <div class="col-md-6 form-group"><label>City</label><input class="form-control" name="city" value="{{ old('city') }}" required></div>
            <div class="col-md-6 form-group"><label>Contact</label><input class="form-control" name="contact" value="{{ old('contact') }}" required></div>
            <div class="col-md-6 form-group"><label>License number</label><input class="form-control" name="license_number" value="{{ old('license_number') }}" required></div>
            <div class="col-12 form-group"><label>Address</label><textarea class="form-control" name="address" required>{{ old('address') }}</textarea></div>
        </div>
        <button class="btn btn-primary">Add Branch</button>
    </form>
</div></div>
@endsection